<?php

namespace AppBundle\Controller;
use AppBundle\Entity\User;
use AppBundle\Entity\Role;

class AuthControllerTest extends AbstractTestController
{
     /**
     * @test
     */
    public function endpointAuthChecks()
    {
        $this->assertEndpointReturnAuthError('GET', '/auth/get-logged-user');
    }
    
    public function testLoginFailWrongSecret()
    {
        $return = $this->assertRequest('POST', '/auth/login', [
            'mustFail' => true,
            'ClientSecret' => '123abc-deputyWRONG'
        ]);
        $this->assertContains('client secret not accepted', $return['message']);
        
        // assert I'm still not logged
        $this->assertRequest('GET','/auth/get-logged-user', [
            'mustFail' => true
        ]);
    }
    
    public function testLoginFailWrongPassword()
    {
        $return = $this->assertRequest('POST', '/auth/login', [
            'mustFail' => true,
            'data' => [
                'email' => 'user@mail.com-WRONG',
                'password' => 'password-WRONG',
            ],
            'ClientSecret' => '123abc-deputy'
        ]);
        $this->assertContains('not found', $return['message']);
        
        // assert I'm still not logged
        $this->assertRequest('GET','/auth/get-logged-user', [
            'mustFail' => true
        ]);
    }
    
    public function testLoginFailWhenAdminUSesDeputySecret()
    {
        $return = $this->assertRequest('POST', '/auth/login', [
            'mustFail' => true,
            'data' => [
                'email' => 'admin@example.org',
                'password' => 'Abcd1234',
            ],
            'ClientSecret' => '123abc-deputy'
        ]);
        $this->assertContains('client secret only allows', $return['message']);
        
        // assert I'm still not logged
        $this->assertRequest('GET','/auth/get-logged-user', [
            'mustFail' => true
        ]);
    }
    
    public function testLoginSuccess()
    {
        $authToken = $this->login('deputy@example.org');
        
        $this->assertTrue(strlen($authToken)> 5, "Token $authToken not valid");
        
        // assert fail without token
        $data = $this->assertRequest('GET', '/auth/get-logged-user', [
            'mustFail' => true
        ])['data'];
        
        // assert succeed with token
        $data = $this->assertRequest('GET', '/auth/get-logged-user', [
            'mustSucceed' => true,
            'AuthToken' => $authToken
        ])['data'];
        $this->assertEquals('deputy@example.org', $data['email']);
        
        return $authToken;
    }
    
    /**
     * @depends testLoginSuccess
     */
    public function testLogout($authToken)
    {
        $this->assertRequest('POST', '/auth/logout', [
            'mustSucceed' => true,
            'AuthToken' => $authToken
        ]);

        // assert the request with the old token fails
        $this->assertEndpointReturnAuthError('GET', '/auth/get-logged-user');
    }
}
