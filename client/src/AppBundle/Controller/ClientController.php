<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Entity\Report\Report;
use AppBundle\Entity\User;
use AppBundle\Form\ClientType;
use AppBundle\Service\Redirector;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class ClientController extends AbstractController
{
    /**
     * @Route("/deputyship-details/your-client", name="client_show")
     * @Template("AppBundle:Client:show.html.twig")
     */
    public function showAction(Redirector $redirector)
    {
        // redirect if user has missing details or is on wrong page
        $user = $this->getUserWithData();

        $route = $redirector->getCorrectRouteIfDifferent($user, 'client_show');

        if (is_string($route)) {
            return $this->redirectToRoute($route);
        }

        $client = $this->getFirstClient();

        return [
            'client' => $client,
        ];
    }

    /**
     * @Route("/deputyship-details/your-client/edit", name="client_edit")
     * @Template("AppBundle:Client:edit.html.twig")
     */
    public function editAction(Request $request)
    {
        $from = $request->get('from');
        $client = $this->getFirstClient();

        if (is_null($client)) {
            /** @var User $user */
            $user = $this->getUser();
            $userId = $user->getId();
            throw new \RuntimeException("User $userId does not have a client");
        }

        $form = $this->createForm(ClientType::class, $client, [
            'action' => $this->generateUrl('client_edit', ['action' => 'edit', 'from' => $from]),
            'validation_groups' => ['lay-deputy-client-edit']
        ]);

        $form->handleRequest($request);

        // edit client form
        if ($form->isSubmitted() && $form->isValid()) {
            $clientUpdated = $form->getData();
            $clientUpdated->setId($client->getId());
            $this->getRestClient()->put('client/upsert', $clientUpdated, ['edit']);
            $this->addFlash('notice', htmlentities($client->getFirstname()) . "'s data edited");

            $user = $this->getUserWithData(['user-clients', 'client']);

            if ($user->isLayDeputy()) {
                $updateClientDetailsEmail = $this->getMailFactory()->createUpdateClientDetailsEmail($clientUpdated);
                $this->getMailSender()->send($updateClientDetailsEmail, ['html']);
            }

            $activeReport = $client->getActiveReport();

            if ($from === 'declaration' && $activeReport instanceof Report) {
                return $this->redirect($this->generateUrl('report_declaration', ['reportId' => $activeReport->getId()]));
            }

            return $this->redirect($this->generateUrl('client_show'));
        }

        return [
            'client' => $client,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/client/add", name="client_add")
     * @Template("AppBundle:Client:add.html.twig")
     */
    public function addAction(Request $request, Redirector $redirector)
    {
        // redirect if user has missing details or is on wrong page
        $user = $this->getUserWithData();

        $route = $redirector->getCorrectRouteIfDifferent($user, 'client_add');

        if (is_string($route)) {
            return $this->redirectToRoute($route);
        }

        $client = $this->getFirstClient();
        if (!empty($client)) {
            // update existing client
            $client = $this->getRestClient()->get('client/' . $client->getId(), 'Client', ['client', 'report-id', 'current-report']);
            $method = 'put';
            $client_validated = true;
        } else {
            // new client
            $client = new Client();
            $method = 'post';
            $client_validated = false;
        }

        $form = $this->createForm(ClientType::class, $client);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // validate against casRec
                $this->getRestClient()->apiCall('post', 'casrec/verify', $client, 'array', []);

                // $method is set above to either post or put
                $response =  $this->getRestClient()->$method('client/upsert', $form->getData());

                /** @var User $currentUser */
                $currentUser = $this->getUser();

                $url = $currentUser->isNdrEnabled()
                    ? $this->generateUrl('ndr_index')
                    : $this->generateUrl('report_create', ['clientId' => $response['id']]);
                return $this->redirect($url);
            } catch (\Throwable $e) {
                /** @var TranslatorInterface $translator */
                $translator = $this->get('translator');

                /** @var LoggerInterface $logger */
                $logger = $this->get('logger');

                switch ((int) $e->getCode()) {
                    case 400:
                        $form->addError(new FormError($translator->trans('formErrors.matching', [], 'register')));
                        break;

                    default:
                        $form->addError(new FormError($translator->trans('formErrors.generic', [], 'register')));
                }

                $logger->error(__METHOD__ . ': ' . $e->getMessage() . ', code: ' . $e->getCode());
            }
        }

        return [
            'form' => $form->createView(),
            'client_validated' => $client_validated,
            'client' => $client
        ];
    }
}
