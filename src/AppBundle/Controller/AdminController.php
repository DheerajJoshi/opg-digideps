<?php

namespace AppBundle\Controller;

use AppBundle\Entity as EntityDir;
use AppBundle\Exception\DisplayableException;
use AppBundle\Form as FormDir;
use AppBundle\Model\Email;
use AppBundle\Service\Client\RestClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Service\DataImporter\CsvToArray;
use Symfony\Component\Form\FormError;
use AppBundle\Exception\RestClientException;

/**
* @Route("/admin")
*/
class AdminController extends AbstractController
{
    /**
     * @Route("/", name="admin_homepage")
     * @Template
     */
    public function indexAction(Request $request)
    {
        $orderBy = $request->query->has('order_by')? $request->query->get('order_by'): 'firstname';
        $sortOrder = $request->query->has('sort_order')? $request->query->get('sort_order'): 'ASC';
        
        $form = $this->createForm(new FormDir\AddUserType([
            'roles' => $this->getRestClient()->get('role', 'Role[]'),
            'roleIdEmptyValue' => $this->get('translator')->trans('roleId.defaultOption', [], 'admin')
        ]), new EntityDir\User());
        
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // add user
                $response = $this->getRestClient()->post('user', $form->getData(), [
                    'deserialise_group' => 'admin_add_user' //only serialise the properties modified by this form)
                ]);
                $user = $this->getRestClient()->get('user/' . $response['id'], 'User');

                $request->getSession()->getFlashBag()->add(
                    'notice', 
                    'An activation email has been sent to the user.'
                );
                
                $this->get('auditLogger')->log(EntityDir\AuditLogEntry::ACTION_USER_ADD, $user);
                
                return $this->redirect($this->generateUrl('admin_homepage'));
            } 
        }
        
        $limit = $request->query->get('limit') ?: 50;
        $offset = $request->query->get('offset') ?: 0;
        $userCount = $this->getRestClient()->get("user/count", 'array');
        $users = $this->getRestClient()->get("user/get-all/{$orderBy}/{$sortOrder}/$limit/$offset", 'User[]');
        $newSortOrder = $sortOrder == "ASC"? "DESC": "ASC";
        
        return [
            'users'=>$users, 
            'userCount'=> $userCount,
            'limit' => $limit,
            'offset' => $offset,
            'form'=>$form->createView(),
            'newSortOrder' => $newSortOrder
        ];
    }
    
    /**
     * @Route("/edit-user/{id}", name="admin_editUser")
     * @Method({"GET", "POST"})
     * @Template
     * 
     * @param Request $request
     */
    public function editUserAction(Request $request, $id)
    {
        $user = $this->getRestClient()->get("user/{$id}", 'User');
       
        if(empty($user)){
            throw new \Exception('User does not exists');
        }
        
        $form = $this->createForm(new FormDir\AddUserType([
            'roles' => $this->getRestClient()->get('role', 'Role[]'),
            'roleIdEmptyValue' => $this->get('translator')->trans('roleId.defaultOption', [], 'admin'),
            'roleIdDisabled' => $user->getId() == $this->getUser()->getId()
        ]), $user );

        if($request->getMethod() == "POST"){
            $form->handleRequest($request);
            
            if($form->isValid()){
                $updateUser = $form->getData();
                $this->getRestClient()->put('user/' . $user->getId(), $updateUser);
                
                $request->getSession()->getFlashBag()->add('action', 'action.message');
                
                $this->redirect($this->generateUrl('admin_editUser', [ 'id' => $user->getId() ]));
            }
        }
        
        return [ 'form' => $form->createView(), 'action' => 'edit', 'id' => $id, 'user' => $user ];
    }
    
    /**
     * @Route("/delete-confirm/{id}", name="admin_delete_confirm")
     * @Method({"GET"})
     * @Template()
     *
     *
     * @param type $id
     */
    public function deleteConfirmAction($id)
    {
        $userToDelete = $this->getRestClient()->get("user/{$id}", 'User');

        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            throw new DisplayableException('Only Admin can delete users');
        }

        if ($this->getUser()->getId() == $userToDelete->getId()) {
            throw new DisplayableException('Cannot delete logged user');
        }

        return [ 'user' => $userToDelete ];
    }
    
    /**
     * @Route("/delete/{id}", name="admin_delete")
     * @Method({"GET"})
     * @Template()
     * 
     * @param integer $id
     */
    public function deleteAction($id)
    {
        $user = $this->getRestClient()->get("user/{$id}", 'User'); 
        
        $this->get('auditLogger')->log(EntityDir\AuditLogEntry::ACTION_USER_DELETE, $user);
        
        $this->getRestClient()->delete('user/' . $id);
        
        return $this->redirect($this->generateUrl('admin_homepage'));
    }
    
    /**
     * @Route("/upload", name="admin_upload")
     * @Template
     */
    public function uploadUsersAction(Request $request)
    {
        $form = $this->createForm(new FormDir\UploadCsvType(), null, [
            'action' => $this->generateUrl('admin_upload'),
            'method'=> 'POST'
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isValid()) {
            $fileName = $form->get('file')->getData();
            try {
                $data = (new CsvToArray($fileName, true))
                    ->setExpectedColumns(['Case', 'Surname', 'Deputy No', 'Dep Surname', 'Dep Postcode'])
                    ->getData();
                
                $count = count($data);
                if ($count > 30000) {
                    throw new \RuntimeException("$count records found in the file, only 30000 allowed for each upload.");
                }
                
                $compressedData = base64_encode(gzcompress(json_encode($data), 9));
                
                $ret = $this->getRestClient()->setTimeout(600)->post('casrec/bulk-add/1', $compressedData);
                $request->getSession()->getFlashBag()->add(
                    'notice', 
                    sprintf('%d record uploaded, %d failed', $ret['added'], count($ret['errors']))
                );
                foreach ($ret['errors'] as $error) {
                   $request->getSession()->getFlashBag()->add('notice', $error); 
                }
                
                return $this->redirect($this->generateUrl('admin_upload'));
                
            } catch (\Exception $e) {
                $message = $e->getMessage();
                if ($e instanceof RestClientException && isset($e->getData()['message'])) {
                    $message = $e->getData()['message'];
                }
                $form->get('file')->addError(new FormError($message));
            }
        }
        
        return [
            'currentRecords' => $this->getRestClient()->get("casrec/count", 'array'),
            'form' => $form->createView(),
            'maxUploadSize' => min([ini_get('upload_max_filesize'), ini_get('post_max_size')])
        ];
    }
}
