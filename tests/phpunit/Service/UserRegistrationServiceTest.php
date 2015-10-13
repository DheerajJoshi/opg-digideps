<?php
namespace AppBundle\Service;

use AppBundle\Entity\Client;
use AppBundle\Model\SelfRegisterData;
use AppBundle\Entity\User;
use Doctrine\ORM\ORMInvalidArgumentException;
use Mockery as m;


class UserRegistrationServiceTest extends \PHPUnit_Framework_TestCase
{

    /**
     *
     * @var UserRegistrationService
     */
    private $userRegistrationService;

    private $mockRole;

    public function setup()
    {
        $mockUserRepository = m::mock('\Doctrine\ORM\EntityRepository')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $this->mockRole = m::mock('\AppBundle\Entity\Role')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getId')->andReturn(99)
            ->getMock();

        $mockRoleRepository = m::mock('\Doctrine\ORM\EntityRepository')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('findOneBy')->with(['role'=>'ROLE_LAY_DEPUTY'])->andReturn($this->mockRole)
            ->getMock();


        $em = m::mock('\Doctrine\Common\Persistence\ObjectManager')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getRepository')->with('AppBundle\Entity\User')->andReturn($mockUserRepository)
            ->shouldReceive('getRepository')->with('AppBundle\Entity\Role')->andReturn($mockRoleRepository)
            ->getMock();

        $mailFactory = m::mock('\AppBundle\Services\MailFactory')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $mailSender = m::mock('\AppBundle\Services\MailSender')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $this->userRegistrationService = new UserRegistrationService($em, $mailFactory, $mailSender);
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @test
     */
    public function populateUser()
    {
        $data = new SelfRegisterData();

        $data->setFirstname('Zac');
        $data->setLastname('Tolley');
        $data->setEmail('zac@thetolleys.com');
        $data->setClientLastname('Cross-Tolley');
        $data->setCaseNumber('12341234');

        $user = new User();
        $user->recreateRegistrationToken();
        $this->userRegistrationService->populateUser($user, $data);

        $this->assertEquals($this->mockRole, $user->getRole());
        $this->assertEquals('Zac', $user->getFirstname());
        $this->assertEquals('Tolley', $user->getLastname());
        $this->assertEquals('zac@thetolleys.com', $user->getEmail());
        $this->assertFalse($user->getActive());
        $this->assertFalse($user->getEmailConfirmed());
        $this->assertNotEmpty($user->getRegistrationToken());
        $this->assertNotNull($user->getTokenDate());

        $token_time = $user->getTokenDate();
        $now = new \DateTime();
        $diffInSeconds =  $now->getTimestamp() - $token_time->getTimestamp();

        $this->assertLessThan(60, $diffInSeconds);  // time was set to just now
    }

    /**
     * @test
     */
    public function populateClient()
    {
        $data = new SelfRegisterData();

        $data->setFirstname('Zac');
        $data->setLastname('Tolley');
        $data->setEmail('zac@thetolleys.com');
        $data->setClientLastname('Cross-Tolley');
        $data->setCaseNumber('12341234');

        $client = new Client();
        $this->userRegistrationService->populateClient($client, $data);

        $this->assertEquals('Cross-Tolley', $client->getLastname());
        $this->assertEquals('12341234', $client->getCaseNumber());
    }

    /**
     * @test
     */
    public function saveUserAndClientAndJoinThem()
    {

        $mockUser = m::mock('\AppBundle\Entity\User')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getId')->andReturn(1)
            ->getMock();

        $mockClient = m::mock('\AppBundle\Entity\Client')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('addUser')->once()->with($mockUser)
            ->getMock();

        $mockConnection = m::mock('\Doctrine\Common\Persistence\Connection')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('beginTransaction')->once()
            ->shouldReceive('commit')->once()
            ->getMock();

        $em = m::mock('\Doctrine\Common\Persistence\ObjectManager')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getConnection')->andReturn($mockConnection)
            ->shouldReceive('flush')->twice()
            ->shouldReceive('persist')->with($mockUser)->once()
            ->shouldReceive('persist')->with($mockClient)->once()
            ->getMock();

        $mailFactory = m::mock('\AppBundle\Services\MailFactory')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $mailSender = m::mock('\AppBundle\Services\MailSender')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $this->userRegistrationService = new UserRegistrationService($em, $mailFactory, $mailSender);

        $this->userRegistrationService->saveUserAndClient($mockUser, $mockClient);

    }

    /**
     * @test
     * @expectedException \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function rollbackWhenSavingUserWithError()
    {
        $mockUser = m::mock('\AppBundle\Entity\User')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getId')->andReturn(1)
            ->getMock();

        $mockClient = m::mock('\AppBundle\Entity\Client')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('addUser')->with($mockUser)
            ->getMock();

        $mockConnection = m::mock('\Doctrine\Common\Persistence\Connection')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('beginTransaction')->once()
            ->shouldReceive('rollback')->once()
            ->getMock();

        $exception = ORMInvalidArgumentException::invalidObject('EntityManager#persist()' , $mockUser);

        $em = m::mock('\Doctrine\Common\Persistence\ObjectManager')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getConnection')->andReturn($mockConnection)
            ->shouldReceive('persist')->with($mockUser)->once()->andThrow($exception)
            ->getMock();

        $mailFactory = m::mock('\AppBundle\Services\MailFactory')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $mailSender = m::mock('\AppBundle\Services\MailSender')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $this->userRegistrationService = new UserRegistrationService($em, $mailFactory, $mailSender);

        $this->userRegistrationService->saveUserAndClient($mockUser, $mockClient);

    }

    /**
     * @test
     * @expectedException \Doctrine\ORM\ORMInvalidArgumentException
     */
    public function rollbackWhenSavingClientWithError()
    {
        $mockUser = m::mock('\AppBundle\Entity\User')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getId')->andReturn(1)
            ->getMock();

        $mockClient = m::mock('\AppBundle\Entity\Client')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('addUser')->with($mockUser)
            ->getMock();

        $mockConnection = m::mock('\Doctrine\Common\Persistence\Connection')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('beginTransaction')->once()
            ->shouldReceive('rollback')->once()
            ->getMock();

        $exception = ORMInvalidArgumentException::invalidObject('EntityManager#persist()' , $mockUser);

        $em = m::mock('\Doctrine\Common\Persistence\ObjectManager')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getConnection')->andReturn($mockConnection)
            ->shouldReceive('persist')->with($mockUser)->once()
            ->shouldReceive('persist')->with($mockClient)->once()->andThrow($exception)
            ->getMock();

        $mailFactory = m::mock('\AppBundle\Services\MailFactory')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $mailSender = m::mock('\AppBundle\Services\MailSender')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $this->userRegistrationService = new UserRegistrationService($em, $mailFactory, $mailSender);

        $this->userRegistrationService->saveUserAndClient($mockUser, $mockClient);

    }

    /**
     * @test
     */
    public function userIsNotUnique()
    {
        $user = new User();
        $user->setFirstname("zac");
        $user->setLastname("tolley");
        $user->setEmail("zac@thetolleys.com");

        $mockUserRepository = m::mock('\Doctrine\ORM\EntityRepository')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('findOneBy')->with(['email'=>'zac@thetolleys.com'])->andReturn($user)
            ->getMock();

        $em = m::mock('\Doctrine\Common\Persistence\ObjectManager')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getRepository')->with('AppBundle\Entity\User')->andReturn($mockUserRepository)
            ->getMock();

        $mailFactory = m::mock('\AppBundle\Services\MailFactory')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $mailSender = m::mock('\AppBundle\Services\MailSender')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $this->userRegistrationService = new UserRegistrationService($em, $mailFactory, $mailSender);

        $result = $this->userRegistrationService->userIsUnique($user);

        $this->assertFalse($result);

    }

    /**
     * @test
     */
    public function userIsUnique()
    {

        $mockUserRepository = m::mock('\Doctrine\ORM\EntityRepository')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('findOneBy')->with(['email'=>'zaz@thetolleys.com'])->andReturn(null)
            ->getMock();

        $em = m::mock('\Doctrine\Common\Persistence\ObjectManager')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getRepository')->with('AppBundle\Entity\User')->andReturn($mockUserRepository)
            ->getMock();

        $mailFactory = m::mock('\AppBundle\Services\MailFactory')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $mailSender = m::mock('\AppBundle\Services\MailSender')
            ->shouldIgnoreMissing(true)
            ->getMock();

        $this->userRegistrationService = new UserRegistrationService($em, $mailFactory, $mailSender);


        $user2 = new User();
        $user2->setFirstname("zac");
        $user2->setLastname("tolley");
        $user2->setEmail("zaz@thetolleys.com");

        $result = $this->userRegistrationService->userIsUnique($user2);

        $this->assertTrue($result);

    }

    /** @test */
    public function renderRegistrationHtmlEmail()
    {
        $data = new SelfRegisterData();

        $data->setFirstname('Zac');
        $data->setLastname('Tolley');
        $data->setEmail('zac@thetolleys.com');
        $data->setClientLastname('Cross-Tolley');
        $data->setCaseNumber('12341234');

        $mockUser = m::mock('\AppBundle\Entity\User')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getId')->andReturn(1)
            ->getMock();

        $mockClient = m::mock('\AppBundle\Entity\Client')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('addUser')->with($mockUser)
            ->getMock();

        $mockConnection = m::mock('\Doctrine\Common\Persistence\Connection')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('beginTransaction')
            ->shouldReceive('commit')
            ->getMock();

        $mockUserRepository = m::mock('\Doctrine\ORM\EntityRepository')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('findOneBy')->with(['email'=>'zac@thetolleys.com'])->andReturn(null)
            ->getMock();

        $mockRoleRepository = m::mock('\Doctrine\ORM\EntityRepository')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('findOneBy')->with(['role'=>'ROLE_LAY_DEPUTY'])->andReturn($this->mockRole)
            ->getMock();

        $em = m::mock('\Doctrine\Common\Persistence\ObjectManager')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('getConnection')->andReturn($mockConnection)
            ->shouldReceive('flush')->twice()
            ->shouldReceive('persist')->with($mockUser)
            ->shouldReceive('persist')->with($mockClient)
            ->shouldReceive('getRepository')->with('AppBundle\Entity\User')->andReturn($mockUserRepository)
            ->shouldReceive('getRepository')->with('AppBundle\Entity\Role')->andReturn($mockRoleRepository)
            ->getMock();

        $mockEmail = m::mock('\AppBundle\Model\Email')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('setToEmail')
            ->getMock();

        $mailFactory = m::mock('\AppBundle\Services\MailFactory')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('createActivationEmail')->withAnyArgs()->once()->andReturn($mockEmail)
            ->getMock();

        $mailSender = m::mock('\AppBundle\Services\MailSender')
            ->shouldIgnoreMissing(true)
            ->shouldReceive('send')->with($mockEmail)->once()
            ->getMock();


        $this->userRegistrationService = new UserRegistrationService($em, $mailFactory, $mailSender);

        $this->userRegistrationService->selfRegisterUser($data);
    }

}