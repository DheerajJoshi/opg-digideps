<?php

namespace AppBundle\Controller;

use AppBundle\Entity as EntityDir;
use AppBundle\Exception\RestClientException;
use AppBundle\Form as FormDir;
use AppBundle\Model\SelfRegisterData;
use AppBundle\Service\DeputyProvider;
use AppBundle\Service\Redirector;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    /**
     * Landing page to let the user access the app and selecting a password.
     *
     * Used for both user activation (Step1) or password reset. The controller logic is very similar
     *
     * @Route("/user/{action}/{token}", name="user_activate", defaults={ "action" = "activate"}, requirements={
     *   "action" = "(activate|password-reset)"
     * })
     */
    public function activateUserAction(
        Request $request,
        Redirector $redirector,
        DeputyProvider $deputyProvider,
        string $action,
        string $token
    ): Response
    {
        /** @var TranslatorInterface */
        $translator = $this->get('translator');
        $isActivatePage = 'activate' === $action;

        // check $token is correct
        try {
            $user = $this->getRestClient()->loadUserByToken($token);
            /* @var $user EntityDir\User */
        } catch (\Throwable $e) {
            return $this->renderError('This link is not working or has already been used');
        }

        // token expired
        if (!$user->isTokenSentInTheLastHours(EntityDir\User::TOKEN_EXPIRE_HOURS)) {
            $template = $isActivatePage ? 'AppBundle:User:activateTokenExpired.html.twig' : 'AppBundle:User:passwordResetTokenExpired.html.twig';

            return $this->render($template, [
                'token'            => $token,
                'tokenExpireHours' => EntityDir\User::TOKEN_EXPIRE_HOURS,
            ]);
        }

        // PA must agree to terms before activating the account
        // this check happens before activating the account, therefore no need to set an ACL on all the actions
        if ($isActivatePage
            && $user->hasRoleOrgNamed()
            && !$user->getAgreeTermsUse()) {
            return $this->redirectToRoute('user_agree_terms_use', ['token' => $token]);
        }

        // define form and template that differs depending on the action (activate or password-reset)
        if ($isActivatePage) {
            $passwordMismatchMessage = $translator->trans('password.validation.passwordMismatch', [], 'user-activate');
            $form = $this->createForm(FormDir\SetPasswordType::class, $user, [ 'passwordMismatchMessage' => $passwordMismatchMessage, 'showTermsAndConditions'  => $user->isDeputy()
                                       ]
                                     );
            $template = 'AppBundle:User:activate.html.twig';
        } else { // 'password-reset'
            $passwordMismatchMessage = $translator->trans('form.password.validation.passwordMismatch', [], 'password-reset');
            $form = $this->createForm(FormDir\ResetPasswordType::class, $user, ['passwordMismatchMessage' => $passwordMismatchMessage]);
            $template = 'AppBundle:User:passwordReset.html.twig';
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // login user into API
            try {
                $deputyProvider->login(['token' => $token]);
            } catch (UsernameNotFoundException $e) {
                return $this->renderError('This activation link is not working or has already been used');
            }

            /** @var string */
            $data = json_encode([
                'password_plain' => $user->getPassword(),
                'set_active'     => true,
            ]);

            // set password for user
            $this->getRestClient()->put('user/' . $user->getId() . '/set-password', $data);

            // log in
            $clientToken = new UsernamePasswordToken($user, null, 'secured_area', $user->getRoles());

            /** @var TokenStorageInterface */
            $tokenStorage = $this->get('security.token_storage');
            $tokenStorage->setToken($clientToken); //now the user is logged in

            /** @var SessionInterface */
            $session = $this->get('session');
            $session->set('_security_secured_area', serialize($clientToken));

            if ($isActivatePage) {
                $request->getSession()->set('login-context', 'password-create');
                $route = $user->getIsCoDeputy() ? 'codep_verification' : 'user_details';
                return $this->redirectToRoute($route);
            } else {
                $request->getSession()->set('login-context', 'password-update');

                return $this->redirect($redirector->getFirstPageAfterLogin($request->getSession()));
            }
        }

        return $this->render($template, [
            'token'  => $token,
            'form'   => $form->createView(),
            'user'   => $user
        ]);
    }

    /**
     * @Route("/user/activate/password/send/{token}", name="activation_link_send")
     * @Template("AppBundle:User:activateLinkSend.html.twig")
     */
    public function activateLinkSendAction(string $token): Response
    {
        // check $token is correct
        $user = $this->getRestClient()->loadUserByToken($token);
        /* @var $user EntityDir\User */

        // recreate token
        // the endpoint will also send the activation email
        $this->getRestClient()->userRecreateToken($user->getEmail(), 'activate');

        $activationEmail = $this->getMailFactory()->createActivationEmail($user);
        $this->getMailSender()->send($activationEmail);

        return $this->redirect($this->generateUrl('activation_link_sent', ['token' => $token]));
    }

    /**
     * @return array<mixed>
     * @Route("/user/activate/password/sent/{token}", name="activation_link_sent")
     * @Template("AppBundle:User:activateLinkSent.html.twig")
     */
    public function activateLinkSentAction(string $token): array
    {
        return [
            'token'            => $token,
            'tokenExpireHours' => EntityDir\User::TOKEN_EXPIRE_HOURS,
        ];
    }

    /**
     * Page to edit user details.
     * For :
     * - admin
     * - AD
     * - Lay
     * - PA
     *
     * @return array<mixed>|Response
     * @Route("/user/details", name="user_details")
     * @Template("AppBundle:User:details.html.twig")
     */
    public function detailsAction(Request $request, Redirector $redirector)
    {
        $user = $this->getUserWithData();

        $client_validated = $this->getFirstClient() instanceof EntityDir\Client && !$user->isDeputyOrg();

        list($formType, $jmsPutGroups) = $this->getFormAndJmsGroupBasedOnUserRole($user);
        $form = $this->createForm($formType, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getRestClient()->put('user/' . $user->getId(), $form->getData(), $jmsPutGroups);

            // lay deputies are redirected to adding a client (Step.3)
            if ($user->isLayDeputy()) {
                return $this->redirectToRoute('client_add');
            }


            // all other users go to their homepage (dashboard for PROF/PA), or /admin for Admins
            return $this->redirect($redirector->getHomepageRedirect());
        }

        return [
            'client_validated' => $client_validated,
            'form' => $form->createView(),
            'user' => $user
        ];
    }

    /**
     * @return array<mixed>|Response
     * @Route("/password-managing/forgotten", name="password_forgotten")
     * @Template("AppBundle:User:passwordForgotten.html.twig")
     **/
    public function passwordForgottenAction(Request $request)
    {
        /** @var LoggerInterface */
        $logger = $this->get('logger');

        $user = new EntityDir\User();
        $form = $this->createForm(FormDir\PasswordForgottenType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $emailAddress = $user->getEmail();
            $disguisedEmail = '***' . substr($emailAddress, 3);
            $logger->warning('Reset password request for : ' . $emailAddress);

            try {
                $user = $this->getRestClient()->userRecreateToken($user->getEmail(), 'pass-reset');

                $logger->warning('Sending reset email to ' . $disguisedEmail);

                $resetPasswordEmail = $this->getMailFactory()->createResetPasswordEmail($user);

                $this->getMailSender()->send($resetPasswordEmail);
                $logger->warning('Email sent to ' . $disguisedEmail);
            } catch (RestClientException $e) {
                $logger->warning('Email ' . $emailAddress . ' not found');
            }

            // after details are added, admin users to go their homepage, deputies go to next step
            return $this->redirect($this->generateUrl('password_sent'));
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @return array<mixed>
     * @Route("/password-managing/sent", name="password_sent")
     * @Template("AppBundle:User:passwordSent.html.twig")
     */
    public function passwordSentAction(): array
    {
        return [];
    }

    /**
     * @return array<mixed>|Response
     * @Route("/register", name="register")
     * @Template("AppBundle:User:register.html.twig")
     */
    public function registerAction(Request $request)
    {
        $selfRegisterData = new SelfRegisterData();
        $form = $this->createForm(FormDir\SelfRegisterDataType::class, $selfRegisterData);

        /** @var TranslatorInterface */
        $translator = $this->get('translator');
        $vars = [];

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $user = $this->getRestClient()->registerUser($data);
                $activationEmail = $this->getMailFactory()->createActivationEmail($user);
                $this->getMailSender()->send($activationEmail);

                $bodyText = $translator->trans('thankyou.body', [], 'register');
                $email = $data->getEmail();
                $bodyText = str_replace('{{ email }}', $email, $bodyText);

                $signInText = $translator->trans('signin', [], 'register');
                $signIn = '<a href="' . $this->generateUrl('login') . '">' . $signInText . '</a>';
                $bodyText = str_replace('{{ sign_in }}', $signIn, $bodyText);

                return $this->render('AppBundle:User:registration-thankyou.html.twig', [
                    'bodyText' => $bodyText,
                    'email'    => $email,
                ]);
            } catch (\Throwable $e) {
                switch ((int) $e->getCode()) {
                    case 403:
                        $form->addError(new FormError($translator->trans('formErrors.coDepCaseAlreadyRegistered', [], 'register')));
                        break;

                    case 422:
                        $form->addError(new FormError(
                            $translator->trans('email.first.existingError', [], 'register')));
                        break;

                    case 400:
                        $form->addError(new FormError($translator->trans('formErrors.matching', [], 'register')));
                        break;

                    case 424:
                        $form->get('postcode')->addError(new FormError($translator->trans('postcode.matchingError', [], 'register')));
                        break;

                    case 425:
                        $form->addError(new FormError($translator->trans('formErrors.caseNumberAlreadyUsed', [], 'register')));
                        break;

                    default:
                        $form->addError(new FormError($translator->trans('formErrors.generic', [], 'register')));
                }

                /** @var LoggerInterface */
                $logger = $this->get('logger');
                $logger->error(__METHOD__ . ': ' . $e->getMessage() . ', code: ' . $e->getCode());
            }
        }

        // send different URL to google analytics
        if (count($form->getErrors())) {
            $vars['gaCustomUrl'] = '/register/form-errors';
        }

        return $vars + [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/user/agree-terms-use/{token}", name="user_agree_terms_use")
     */
    public function agreeTermsUseAction(Request $request, string $token): Response
    {
        $user = $this->getRestClient()->loadUserByToken($token);

        $form = $this->createForm(FormDir\User\AgreeTermsType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->getRestClient()->agreeTermsUse($token);

            return $this->redirectToRoute('user_activate', ['token' => $token, 'action' => 'activate']);
        }

        if ($user->getRoleName() == EntityDir\User::ROLE_PA_NAMED) {
            $view = 'AppBundle:User:agreeTermsUsePa.html.twig';
        } elseif ($user->getRoleName() ==EntityDir\User::ROLE_PROF_NAMED) {
            $view = 'AppBundle:User:agreeTermsUseProf.html.twig';
        } else {
            throw new \RuntimeException('terms page not implemented');
        }

        return $this->render($view, [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param EntityDir\User $user
     * @return array<mixed> [string FormType, array of JMS groups]
     */
    private function getFormAndJmsGroupBasedOnUserRole(EntityDir\User $user): array
    {
        // define form, route, JMS groups
        switch ($user->getRoleName()) {
            case EntityDir\User::ROLE_LAY_DEPUTY:
                return [FormDir\User\UserDetailsFullType::class, ['user_details_full']];

            case EntityDir\User::ROLE_PA_NAMED:
            case EntityDir\User::ROLE_PA_ADMIN:
            case EntityDir\User::ROLE_PA_TEAM_MEMBER:
            case EntityDir\User::ROLE_PROF_NAMED:
            case EntityDir\User::ROLE_PROF_ADMIN:
            case EntityDir\User::ROLE_PROF_TEAM_MEMBER:
                return [FormDir\User\UserDetailsPaType::class, ['user_details_org']];

            case EntityDir\User::ROLE_ADMIN:
            case EntityDir\User::ROLE_AD:
            case EntityDir\User::ROLE_SUPER_ADMIN:
            default:
                return [FormDir\User\UserDetailsBasicType::class, ['user_details_basic']];
        }
    }
}
