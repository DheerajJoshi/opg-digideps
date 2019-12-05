<?php

namespace Tests\AppBundle\Service\Auth;

use AppBundle\Entity\Repository\UserRepository;
use AppBundle\Service\Auth\AuthService;
use Mockery;
use MockeryStub as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Role\Role;

class AuthServiceTest extends TestCase
{
    /**
     * @var AuthService
     */
    private $authService;

    private $clientSecrets = [
        'layDeputySecret' => [
            'permissions' => ['ROLE_LAY_DEPUTY'],
        ],
        'adminSecret' => [
            'permissions' => ['ROLE_ADMIN'],
        ],
        'layDeputySecretNoPermissions' => [
        ],
        'layDeputySecretWrongFormat' => 'IShouldBeAnArray',
    ];

    public function setUp(): void
    {
        $this->userRepo = m::stub(UserRepository::class);
        $this->logger = m::mock('Symfony\Bridge\Monolog\Logger');
        $this->encoderFactory = m::stub('Symfony\Component\Security\Core\Encoder\EncoderFactory');
        $this->roleHierarchy = m::stub('Symfony\Component\Security\Core\Role\RoleHierarchyInterface');
        $this->roleHierarchy->shouldReceive('getReachableRoles')->with(Mockery::any())->andReturn([new Role('ROLE_LAY_DEPUTY')]);

        $this->authService = new AuthService($this->encoderFactory, $this->logger, $this->userRepo, $this->roleHierarchy, $this->clientSecrets);
    }

    public function testMissingSecrets()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->authService = new AuthService($this->encoderFactory, $this->logger, $this->userRepo, $this->roleHierarchy, []);
    }

    public function isSecretValidProvider()
    {
        return [
            ['layDeputySecret', true],
            ['layDeputySecret ', false],
            ['LAYDEPUTYSECRET-deputy ', false],
            ['123', false],
            [null, false],
            [0, false],
            [false, false],
        ];
    }

    /**
     * @dataProvider isSecretValidProvider
     */
    public function testisSecretValid($clientSecret, $expectedValidity)
    {
        $request = new Request();
        $request->headers->set(AuthService::HEADER_CLIENT_SECRET, $clientSecret);

        $this->assertEquals($expectedValidity, $this->authService->isSecretValid($request));
    }

    public function testgetUserByEmailAndPasswordUserNotFound()
    {
        $this->userRepo->shouldReceive('findOneBy')->with(['email' => 'email@example.org'])->andReturn(null);
        $this->logger->shouldReceive('info')->with(Mockery::pattern('/not found/'))->once();

        $this->assertEquals(false, $this->authService->getUserByEmailAndPassword('email@example.org', 'plainPassword'));
    }

    public function testgetUserByEmailAndPasswordMismatchPassword()
    {
        $user = m::stub('AppBundle\Entity\User', [
                'getSalt' => 'salt',
                'getPassword' => 'encodedPassword',
        ]);
        $this->userRepo->shouldReceive('findOneBy')->with(['email' => 'email@example.org'])->andReturn($user);

        $encoder = m::stub('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface', [
                'encodePassword(plainPassword,salt)' => 'encodedPassword-WRONG',
        ]);
        $this->encoderFactory->shouldReceive('getEncoder')->with($user)->andReturn($encoder);

        $this->logger->shouldReceive('info')->with(Mockery::pattern('/password mismatch/'))->once();

        $this->assertEquals(null, $this->authService->getUserByEmailAndPassword('email@example.org', 'plainPassword'));
    }

    public function testgetUserByEmailAndPasswordCorrect()
    {
        $user = m::stub('AppBundle\Entity\User', [
                'getSalt' => 'salt',
                'getPassword' => 'encodedPassword',
        ]);
        $this->userRepo->shouldReceive('findOneBy')->with(['email' => 'email@example.org'])->andReturn($user);

        $encoder = m::stub('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface', [
                'encodePassword(plainPassword,salt)' => 'encodedPassword',
        ]);
        $this->encoderFactory->shouldReceive('getEncoder')->with($user)->andReturn($encoder);

        $this->assertEquals($user, $this->authService->getUserByEmailAndPassword('email@example.org', 'plainPassword'));
    }

    public function testgetUserByToken()
    {
        $user = m::mock('AppBundle\Entity\User');

        $this->userRepo->shouldReceive('findOneBy')->with(['registrationToken' => 'token'])->andReturn($user);
        $this->assertEquals($user, $this->authService->getUserByToken('token'));

        $this->userRepo->shouldReceive('findOneBy')->with(['registrationToken' => 'wrongtoken'])->andReturn(false);
        $this->assertEquals(null, $this->authService->getUserByToken('wrongtoken'));
    }

    public function isSecretValidForUserProvider()
    {
        return [
            ['layDeputySecret', 'ROLE_LAY_DEPUTY', true],
            ['layDeputySecret', 'ROLE_LAY_DEPUTY_INHERITED', true],
            ['layDeputySecret', 'ROLE_ADMIN', false],
            ['layDeputySecret', 'OTHER_ROLE', false],
            ['layDeputySecret', null, false],
            ['adminSecret', 'ROLE_LAY_DEPUTY', false],
            ['adminSecret', 'ROLE_ADMIN', true],
            ['adminSecret', 'OTHER_ROLE', false],
            ['adminSecret', null, false],
            ['layDeputySecretNoPermissions', '', false],
            ['layDeputySecretNoPermissions', null, false],
            ['layDeputySecretNoPermissions', false, false],
            ['layDeputySecretWrongFormat', '', false],
            [null, null, false],
        ];
    }

    /**
     * @dataProvider isSecretValidForUserProvider
     */
    public function testisSecretValidForRole($clientSecret, $role, $expectedResult)
    {
        $request = new Request();
        $request->headers->set(AuthService::HEADER_CLIENT_SECRET, $clientSecret);

        $this->assertEquals($expectedResult, $this->authService->isSecretValidForRole($role, $request));
    }

    public function tearDown(): void
    {
        m::close();
    }
}
