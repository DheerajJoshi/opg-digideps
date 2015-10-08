<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Service\Auth\HeaderTokenAuthenticator;
use AppBundle\Service\Auth\UserProvider;
use AppBundle\Service\Auth\AuthService;

/**
 * @Route("/auth")
 */
class AuthController extends RestController
{
    
    /**
     * Return the user by email&password or token
     * expected keys in body: 'token' or ('email' and 'password')
     * 
     * 
     * @Route("/login")
     * @Method({"POST"})
     */
    public function login(Request $request)
    {
        if (!$this->getAuthService()->isSecretValid($request)) {
            throw new \RuntimeException('client secret not accepted.', 403);
        }
        $data = $this->deserializeBodyContent($request);
        
        // load user by credentials (token or usernae&password)
        if (array_key_exists('token', $data)) {
            $user = $this->getAuthService()->getUserByToken($data['token']);
        } else {
            $user = $this->getAuthService()->getUserByEmailAndPassword(strtolower($data['email']), $data['password']);
        }
        if (!$user) {
            throw new \RuntimeException('Cannot find user with the given credentials', 498);
        }
         if (!$this->getAuthService()->isSecretValidForUser($user, $request)) {
            throw new \RuntimeException($user->getRole()->getRole() . ' user role not allowed from this client.', 403);
        }
        
        $randomToken = $this->getProvider()->generateRandomTokenAndStore($user);
        $user->setLastLoggedIn(new \DateTime);
        $this->get('em')->flush($user);
        
        // add token into response
        $this->get('kernel.listener.responseConverter')->addResponseModifier(function ($request) use ($randomToken) {
            $request->headers->set(HeaderTokenAuthenticator::HEADER_NAME, $randomToken);
        });
        
        return $user;
    }
    
    /**
     * @return UserProvider
     */
    private function getProvider()
    {
        return $this->container->get('user_provider');
    }
    
    /**
     * Return the user by email and hashed password (or exception if not found)
     * 
     * 
     * @Route("/logout")
     * @Method({"POST"})
     */
    public function logout(Request $request)
    {
        $authToken = HeaderTokenAuthenticator::getTokenFromRequest($request);
       
        return $this->getProvider()->removeToken($authToken);
    }
    
    /**
     * Test endpoint used for testing to check auth permissions
     * 
     * @Route("/get-logged-user")
     * @Method({"GET"})
     */
    public function getLoggedUser()
    {
        return $this->get('security.token_storage')->getToken()->getUser();
    }
   
}