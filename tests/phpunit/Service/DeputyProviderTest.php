<?php
namespace AppBundle\Service;

use Symfony\Bridge\Monolog\Logger;
use AppBundle\Service\Client\RestClient;

use Mockery as m;

class DeputyProviderTest extends \PHPUnit_Framework_TestCase
{
     /**
     * @var DeputyProvider
     */
    private $object;
    
    /**
     * @var RestClient
     */
    private $restClient;

     /**
     * @var Logger
     */
    private $logger;
    
    public function setUp()
    {
        $this->restClient = m::mock('AppBundle\Service\Client\RestClient');
        $this->logger = m::mock('Symfony\Bridge\Monolog\Logger');
        
        $this->object = new DeputyProvider($this->restClient, $this->logger);
    }
    
    public function testLogin() 
    {
        $credentials = ['email'=>'Peter', 'password'=>'p'];
        
        $this->restClient->shouldReceive('login')->once()->with($credentials);
        
        $this->object->login($credentials);
    }
    
    /**
     * @expectedException Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoginFail() 
    {
        $credentials = ['email'=>'Peter', 'password'=>'p'];
        
        $this->restClient->shouldReceive('login')->once()->with($credentials)->andThrow(new \Exception('e'));
        $this->logger->shouldReceive('info')->once();
        
        $this->object->login($credentials);
    }
    
    
    public function testLoadUserByUsername() 
    {
        $this->restClient->shouldReceive('get')->with('user/1', 'User')->andReturn('user');
        
        $this->assertEquals('user', $this->object->LoadUserByUsername(1));
    }
    
    
    
    public function testSupportsClass()
    {
        $this->assertTrue($this->object->supportsClass("AppBundle\Entity\User"));
        $this->assertFalse($this->object->supportsClass("AppBundle\Entity\Report"));
    }
    
    public function tearDown() {
        m::close();
    }
}