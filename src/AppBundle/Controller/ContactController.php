<?php
namespace AppBundle\Controller;

use AppBundle\Entity as EntityDir;
use AppBundle\Form as FormDir;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use AppBundle\Service\ReportStatusService;



class ContactController extends AbstractController{

    /**
     * @Route("/report/{reportId}/contacts/delete-reason", name="delete_reason_contacts")
     */
    public function deleteReasonAction($reportId)
    {
        //just do some checks to make sure user is allowed to update this report
        $report = $this->getReport($reportId, [ "basic" ,'transactions']);

        if(!empty($report)){
            $report->setReasonForNoContacts(null);
            $this->get('restClient')->put('report/'.$report->getId(),$report);
        }
        
        return $this->redirect($this->generateUrl('contacts', ['reportId' => $report->getId()]));
    }
    
    
    /**
     * @Route("/report/{reportId}/contacts/delete/{id}", name="delete_contact")
     */
    public function deleteAction($reportId,$id)
    {
        //just do some checks to make sure user is allowed to delete this contact
        $report = $this->getReport($reportId, ['basic']);

        if(!empty($report) && in_array($id, $report->getContacts())){
            $this->get('restClient')->delete('report/contact/' . $id);
        }
        return $this->redirect($this->generateUrl('contacts', [ 'reportId' => $reportId ]));
    }

    /**
     * --action[ list, add, edit, delete-confirm, delete, delete-reason-confirm ] #default is list
     *
     * @Route("/report/{reportId}/contacts/{action}/{id}", name="contacts", defaults={ "action" = "list", "id" = " "})
     * @Template()
     */
    public function indexAction($reportId,$action,$id)
    {
        $report = $this->getReport($reportId, ['basic']);
        if ($report->getSubmitted()) {
            throw new \RuntimeException("Report already submitted and not editable.");
        }
        $client = $this->getClient($report->getClient());

        $request = $this->getRequest();

        $restClient = $this->get('restClient');
        $contacts = $restClient->get('report/' . $reportId . '/contacts', 'Contact[]');

        if(in_array($action, [ 'edit', 'delete-confirm'])){
            if (!in_array($id, $report->getContacts())) {
               throw new \RuntimeException("Contact not found.");
            }
            $contact = $restClient->get('report/contact/' . $id, 'Contact');
            $form = $this->createForm(new FormDir\ContactType(), $contact, [ 'action' => $this->generateUrl('contacts', [ 'reportId' => $reportId, 'action' => 'edit', 'id' => $id ])]);
        }else{
             //set up add contact form
            $contact = new EntityDir\Contact();
            $form = $this->createForm(new FormDir\ContactType(), $contact, [ 'action' => $this->generateUrl('contacts',[ 'reportId' => $reportId, 'action' => 'add' ]) ]);
        }
        
        //set up add reason for no contact form
        $noContact = $this->createForm(new FormDir\ReasonForNoContactType(), null, [ 'action' => $this->generateUrl('contacts', [ 'reportId' => $reportId])."#pageBody" ]);
        $reason = $report->getReasonForNoContacts();
        $mode = empty($reason)? 'add':'edit';
        $noContact->setData([ 'reason' => $reason, 'mode' => $mode ]);

        if($request->getMethod() == 'POST'){
            $forms = [ 'contactForm' => $form,
                       'noContact' => $noContact ];

            $processedForms = $this->handleContactsFormSubmit($forms,$reportId,$action);

            if($processedForms instanceof RedirectResponse){
                return $processedForms;
            }
            $form = $processedForms['contactForm'];
            $noContact = $processedForms['noContact'];
        }

        $reportStatusService = new ReportStatusService($report, $this->get('translator'));
        
        return [
            'form' => $form->createView(),
            'contacts' => $contacts,
            'action' => $action,
            'report' => $report,
            'reportStatus' => $reportStatusService,
            'client' => $client,
            'no_contact' => $noContact->createView() ];
    }

    /**
    * @param array $forms
    * @return array $forms
    */
    private function handleContactsFormSubmit(array $forms, $reportId, $action='add')
    {
        $request = $this->getRequest();
        $restClient = $this->get('restClient');

        $form = $forms['contactForm'];
        $noContact    = $forms['noContact'];

        $form->handleRequest($request);
        $noContact->handleRequest($request);

        $report = $this->getReport($reportId, [ 'transactions', 'basic']);
        
        //check if contacts form was submitted
        if($form->get('save')->isClicked()){
            if($form->isValid()){
                $contact = $form->getData();
                $contact->setReport($reportId);

                if($action == 'add'){
                    $restClient->post('report/contact', $contact);
                    
                    //lets clear any reason for no decisions they might have added previously
                    $report->setReasonForNoContacts(null);
                    $restClient->put('report/'.$report->getId(),$report);
            
                }else{
                    $restClient->put('report/contact', $contact);
                }

                return $this->redirect($this->generateUrl('contacts', [ 'reportId' => $reportId ]));
            }

         //check if add reason for no contact form was submitted
        }elseif($noContact->get('saveReason')->isClicked()){
            if($noContact->isValid()){
                 $formData = $noContact->getData();

                 $report->setReasonForNoContacts($formData['reason']);

                 $restClient->put('report/'.$report->getId(),$report);

                 return $this->redirect($this->generateUrl('contacts',[ 'reportId' => $report->getId()]));
            }

        //the above 2 forms test false then submission was for the overall report submit
        }
        $forms['contactForm'] = $form;
        $forms['noContact'] = $noContact;

        return $forms;
    }
}
