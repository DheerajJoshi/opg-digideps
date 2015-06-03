<?php

namespace AppBundle\EventListener;

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use AppBundle\Entity\AuditLogEntry;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use AppBundle\Service\AuditLogger;

class LogoutListener implements LogoutSuccessHandlerInterface
{
    /**
     * @var SecurityContext 
     */
    private $security;
    
    /**
     * @var Router 
     */
    private $router;
    
    /**
     * @var AuditLogger 
     */
    private $auditLogger;


    public function __construct(SecurityContext $security, Router $router, AuditLogger $auditLogger)
    {
        $this->security = $security;
        $this->router = $router;
        $this->auditLogger = $auditLogger;
    }

    public function onLogoutSuccess(Request $request)
    {
        $this->auditLogger->log(AuditLogEntry::ACTION_LOGOUT);
        
        $request->getSession()->set('loggedOutFrom', 'logoutPage');
        
        $response = new RedirectResponse($this->router->generate('login'));

        return $response;
    }

}