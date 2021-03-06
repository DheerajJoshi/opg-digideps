<?php

namespace Tests\AppBundle\Entity\Repository;

use AppBundle\Entity\Repository\UserRepository;
use AppBundle\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Fixtures;

class UserRepositoryTest extends WebTestCase
{
    /**
     * @var UserRepository
     */
    private $sut;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()->get('em');
        $this->fixtures = new Fixtures($this->em);

        $metaClass = self::prophesize(ClassMetadata::class);
        $metaClass->name = User::class;

        $this->sut = new UserRepository($this->em, $metaClass->reveal());
    }

    public function testCountsInactiveUsers()
    {
        $oldUserWithNoClient = $this->fixtures->createUser();
        $oldUserWithNoClient->setRegistrationDate(DateTime::createFromFormat('Y-m-d', '2019-03-03'));
        $oldUserWithNoClient->setRoleName(User::ROLE_LAY_DEPUTY);

        $oldUserWithNoReports = $this->fixtures->createUser();
        $oldUserWithNoReports->setRegistrationDate(DateTime::createFromFormat('Y-m-d', '2019-03-03'));
        $oldUserWithNoReports->setRoleName(User::ROLE_LAY_DEPUTY);
        $this->fixtures->createClient($oldUserWithNoReports);

        $oldUserWithReport = $this->fixtures->createUser();
        $oldUserWithReport->setRegistrationDate(DateTime::createFromFormat('Y-m-d', '2019-03-03'));
        $oldUserWithReport->setRoleName(User::ROLE_LAY_DEPUTY);
        $oldClientWithReport = $this->fixtures->createClient($oldUserWithReport);
        $this->fixtures->createReport($oldClientWithReport);

        $oldUserWithNdr = $this->fixtures->createUser();
        $oldUserWithNdr->setRegistrationDate(DateTime::createFromFormat('Y-m-d', '2019-03-03'));
        $oldUserWithNdr->setRoleName(User::ROLE_LAY_DEPUTY);
        $oldClientWithNdr = $this->fixtures->createClient($oldUserWithNdr);
        $this->fixtures->createNdr($oldClientWithNdr);

        $oldProfUserWithNoClient = $this->fixtures->createUser();
        $oldProfUserWithNoClient->setRegistrationDate(DateTime::createFromFormat('Y-m-d', '2019-03-03'));
        $oldProfUserWithNoClient->setRoleName(User::ROLE_PROF_ADMIN);

        $recentUserWithNoClient = $this->fixtures->createUser();
        $recentUserWithNoClient->setRegistrationDate(new DateTime());
        $recentUserWithNoClient->setRoleName(User::ROLE_LAY_DEPUTY);
        $this->fixtures->createClient($recentUserWithNoClient);

        $this->em->flush();

        $inactiveUsers = $this->sut->findInactive();

        self::assertCount(2, $inactiveUsers);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->em->close();
        $this->em = null;
    }
}
