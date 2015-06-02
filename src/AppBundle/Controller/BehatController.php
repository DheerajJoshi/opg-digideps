<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Service\ApiClient;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/behat")
 */
class BehatController extends Controller
{
    private function checkIsBehatBrowser()
    {
        $expectedSecretParam = md5('behat-dd-' . $this->container->getParameter('secret'));
        $secret = $this->getRequest()->get('secret');
        
        if ($secret !== $expectedSecretParam) {
            
            // log access
            $this->get('logger')->error($this->getRequest()->getPathInfo(). ": $expectedSecretParam secret expected. 404 will be returned.");
            
            throw $this->createNotFoundException('Not found');
        }
    }
    
    /**
     * @Route("/{secret}/email-get-last")
     * @Method({"GET"})
     */
    public function getLastEmailAction()
    {
        $this->checkIsBehatBrowser();
        $content = $this->get('apiclient')->get('behat/email')->getBody();
        
        $contentArray = json_decode($content, 1);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new Response("Error decoding email: body:" . $content);
        }
        
        return new Response($contentArray['data']);
    }
    
    /**
     * @Route("/{secret}/email-reset")
     * @Method({"GET"})
     */
    public function resetAction()
    {
        $this->checkIsBehatBrowser();
        $content = $this->get('apiclient')->delete('behat/email')->getBody();
        
        return new Response($content);
    }
    
    /**
     * @Route("/{secret}/report/{reportId}/change-report-cot/{cot}")
     * @Method({"GET"})
     */
    public function reportChangeReportCot($reportId, $cot)
    {
        $this->checkIsBehatBrowser();
        $this->get('apiclient')->putC('report/'  .$reportId, json_encode([
            'cot_id' => $cot
        ]));
        
        return new Response('done');
    }
    
    /**
     * @Route("/{secret}/report/{reportId}/set-sumbmitted/{value}")
     * @Method({"GET"})
     */
    public function reportChangeSubmitted($reportId, $value)
    {
        $this->checkIsBehatBrowser();
        $this->get('apiclient')->putC('report/'  .$reportId, json_encode([
            'submitted' => ($value == 'true' || $value == 1)
        ]));
        
        return new Response('done');
    }
    
    
    /**
     * @Route("/{secret}/delete-behat-users")
     * @Method({"GET"})
     */
    public function deleteBehatUser()
    {
        $this->checkIsBehatBrowser();
        
        $this->get('apiclient')->delete('behat/users/behat-users');
        
        return new Response('done');
    }
    
    /**
     * @Route("/{secret}/report/{reportId}/change-report-end-date/{dateYmd}")
     * @Method({"GET"})
     */
    public function accountChangeReportDate($reportId, $dateYmd)
    {
        $this->get('apiclient')->putC('report/' . $reportId, json_encode([
            'end_date' => $dateYmd
        ]));
        
        return new Response('done');
    }
    
    /**
     * @Route("/{secret}/delete-behat-data")
     * @Method({"GET"})
     */
    public function resetBehatData()
    {
       return new Response('done');
        
       //TODO implement and test, including corrupted data sets, or password changed
//       $apiClient = $this->get('apiclient');
//       
//       // delete behat data and related records
//       $apiClient->delete('behat/behat-data');
//       
//        // re-add beaht-admin user
//        $user = (new User)
//                ->setFirstname('Be')
//                ->setLastname('Hat')
//                ->setEmail('behat-admin@publicguardian.gsi.gov.uk')
//                ->setRoleId(1); //admin
//
//        // add user
//        $response = $apiClient->postC('add_user', $user, [
//            'deserialise_group' => 'admin_add_user' //only serialise the properties modified by this form)
//        ]);
//            // refresh from aPI and get salt
//        $user = $apiClient->getEntity('User', 'user/' . $response['id']);
//        // set password and activate
//        $apiClient->putC('user/' . $user->getId(), json_encode([
//            'password' => $this->encodePassword($user, 'Abcd1234'),
//            'active' => true
//        ]));
//       
//       return new Response('done');
    }
    
    /**
     * @Route("/{secret}/view-audit-log")
     * @Method({"GET"})
     * @Template()
     */
    public function viewAuditLogAction()
    {
        $this->checkIsBehatBrowser();
        
        $entities = $this->get('apiclient')->getEntities('AuditLogEntry', 'audit-log');
        
        return ['entries' => $entities];
    }
}
