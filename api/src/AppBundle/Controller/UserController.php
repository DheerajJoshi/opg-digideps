<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Client;
use AppBundle\Entity\Repository\ClientRepository;
use AppBundle\Entity\Repository\UserRepository;
use AppBundle\Entity\User;
use AppBundle\Service\UserService;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;

//TODO
//http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html

/**
 * @Route("/user")
 */
class UserController extends RestController
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var EncoderFactory
     */
    private $encoderFactory;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    public function __construct(
        UserService $userService,
        EncoderFactory $encoderFactory,
        UserRepository $userRepository,
        ClientRepository $clientRepository
    )
    {
        $this->userService = $userService;
        $this->encoderFactory = $encoderFactory;
        $this->userRepository = $userRepository;
        $this->clientRepository = $clientRepository;
    }

    /**
     * @Route("", methods={"POST"})
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_AD') or has_role('ROLE_ORG_NAMED') or has_role('ROLE_ORG_ADMIN')")
     */
    public function add(Request $request)
    {
        $data = $this->deserializeBodyContent($request, [
            'role_name' => 'notEmpty',
            'email' => 'notEmpty',
            'firstname' => 'mustExist',
            'lastname' => 'mustExist',
        ]);

        /** @var User $loggedInUser */
        $loggedInUser = $this->getUser();
        $user = new User();

        $user = $this->populateUser($user, $data);

        $this->userService->addUser($loggedInUser, $user, $data);

        $groups = $request->query->has('groups') ?
            $request->query->get('groups') : ['user', 'user-teams', 'team'];
        $this->setJmsSerialiserGroups($groups);

        return $user;
    }

    /**
     * @Route("/{id}", methods={"PUT"})
     */
    public function update(Request $request, $id)
    {
        $user = $this->findEntityBy(User::class, $id, 'User not found');

        /** @var User $loggedInUser */
        $loggedInUser = $this->getUser();

        if ($loggedInUser->getId() != $user->getId()
            && !$this->isGranted(User::ROLE_ADMIN)
            && !$this->isGranted(User::ROLE_AD)
            && !$this->isGranted(User::ROLE_ORG_NAMED)
            && !$this->isGranted(User::ROLE_ORG_ADMIN)
        ) {
            throw $this->createAccessDeniedException("Non-admin not authorised to change other user's data");
        }

        $originalUser = clone $user;
        $data = $this->deserializeBodyContent($request);
        $this->populateUser($user, $data);
        $this->userService->editUser($originalUser, $user);

        return ['id' => $user->getId()];
    }

    /**
     * //TODO take user from logged user.
     *
     * @Route("/{id}/is-password-correct", methods={"POST"})
     */
    public function isPasswordCorrect(Request $request, $id)
    {
        // for both ADMIN and DEPUTY
        $user = $this->findEntityBy(User::class, $id, 'User not found');

        /** @var User $loggedInUser */
        $loggedInUser = $this->getUser();

        if ($loggedInUser->getId() != $user->getId()) {
            throw $this->createAccessDeniedException("Not authorised to check other user's password");
        }

        $data = $this->deserializeBodyContent($request, [
            'password' => 'notEmpty',
        ]);

        $oldPassword = $this->encoderFactory->getEncoder($user)->encodePassword($data['password'], $user->getSalt());
        if ($oldPassword == $user->getPassword()) {
            return true;
        }

        return false;
    }

    /**
     * change password, activate user and send remind email.
     *
     * @Route("/{id}/set-password", methods={"PUT"})
     */
    public function changePassword(Request $request, $id)
    {
        //for both admin and users

        $user = $this->findEntityBy(User::class, $id, 'User not found');

        /** @var User $loggedInUser */
        $loggedInUser = $this->getUser();

        if ($loggedInUser->getId() != $user->getId()) {
            throw $this->createAccessDeniedException("Not authorised to change other user's data");
        }

        $data = $this->deserializeBodyContent($request, [
            'password_plain' => 'notEmpty',
        ]);

        $newPassword = $this->encoderFactory->getEncoder($user)->encodePassword($data['password_plain'], $user->getSalt());

        $user->setPassword($newPassword);

        if (array_key_exists('set_active', $data)) {
            $user->setActive($data['set_active']);
        }

        $this->getEntityManager()->flush();

        return $user->getId();
    }

    /**
     * @Route("/{id}", requirements={"id":"\d+"}, methods={"GET"})
     */
    public function getOneById(Request $request, $id)
    {
        return $this->getOneByFilter($request, 'user_id', $id);
    }

    /**
     * @Route("/get-one-by/{what}/{filter}", requirements={
     *   "what" = "(user_id|email|case_number)"
     * }, methods={"GET"})
     */
    public function getOneByFilter(Request $request, $what, $filter)
    {
        if ($what == 'email') {
            $user = $this->userRepository->findOneBy(['email' => $filter]);
            if (!$user) {
                throw new \RuntimeException('User not found', 404);
            }
        } elseif ($what == 'case_number') {
            /** @var Client|null $client */
            $client = $this->clientRepository->findOneBy(['caseNumber' => $filter]);

            if ($client === null) {
                throw new \RuntimeException('Client not found', 404);
            }
            if (empty($client->getUsers())) {
                throw new \RuntimeException('Client has not users', 404);
            }
            $user = $client->getUsers()[0];
        } elseif ($what == 'user_id') {
            $user = $this->userRepository->find($filter);
            if (!$user) {
                throw new \RuntimeException('User not found', 419);
            }
        } else {
            throw new \RuntimeException('wrong query', 500);
        }

        /** @var User $loggedInuser */
        $loggedInUser = $this->getUser();
        $requestedUserIsLogged = $loggedInUser->getId() == $user->getId();

        $groups = $request->query->has('groups') ?
            $request->query->get('groups') : ['user'];
        $this->setJmsSerialiserGroups($groups);

        // only allow admins and case managers to access any user, otherwise the user can only see himself
        if (!$this->isGranted(User::ROLE_CASE_MANAGER)
            && !$this->isGranted(User::ROLE_AD)
            && !$requestedUserIsLogged) {
            throw $this->createAccessDeniedException("Not authorised to see other user's data");
        }

        return $user;
    }

    /**
     * Get user by email, and retrieve only id and team names the user belongs to.
     * Only for ROLE_PROF named and admin, when adding users to multiple teams.
     * Returns empty if user doesn't exist
     *
     * @Route("/get-team-names-by-email/{email}", methods={"GET"})
     * @Security("has_role('ROLE_ORG_NAMED') or has_role('ROLE_ORG_ADMIN')")
     */
    public function getUserTeamNames(Request $request, $email)
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        $this->setJmsSerialiserGroups(['user-id', 'team-names']);

        return $user;
    }

    /**
     * Delete user with clients.
     * //TODO move to UserService
     *
     * @Route("/{id}", methods={"DELETE"})
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param int $id
     */
    public function delete($id)
    {
        /** @var User $user */
        $user = $this->userRepository->find($id);

        if ($user->getRoleName() !== User::ROLE_LAY_DEPUTY) {
            throw $this->createAccessDeniedException('Cannot delete users with role ' . $user->getRoleName());
        }

        $clients = $user->getClients();

        if (count($clients) > 1) {
            throw $this->createAccessDeniedException('Cannot delete user with multiple clients');
        }

        // delete clients (max 1)
        foreach ($clients as $client) {
            if (count($client->getReports()) > 0) {
                throw $this->createAccessDeniedException('Cannot delete user with reports');
            }
            $this->getEntityManager()->remove($client);
        }

        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();

        return [];
    }

    /**
     * @Route("/get-all", methods={"GET"})
     * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_AD')")
     */
    public function getAll(Request $request)
    {
        $order_by = $request->get('order_by', 'id');
        $sort_order = strtoupper($request->get('sort_order', 'DESC'));
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);
        $roleName = $request->get('role_name');
        $adManaged = $request->get('ad_managed');
        $ndrEnabled = $request->get('ndr_enabled');
        $includeClients = $request->get('include_clients');
        $q = $request->get('q');

        $qb = $this->userRepository->createQueryBuilder('u');
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('u.' . $order_by, $sort_order);

        if ($roleName) {
            if (strpos($roleName, '%')) {
                $qb->andWhere('u.roleName LIKE :role');
            } else {
                $qb->andWhere('u.roleName = :role');
            }
            $qb->setParameter('role', $roleName);
        }

        if ($adManaged) {
            $qb->andWhere('u.adManaged = true');
        }

        if ($ndrEnabled) {
            $qb->andWhere('u.ndrEnabled = true');
        }

        if ($q) {
            if (Client::isValidCaseNumber($q)) { // case number
                $qb->leftJoin('u.clients', 'c');
                $qb->andWhere('lower(c.caseNumber) = :cn');
                $qb->setParameter('cn', strtolower($q));
            } else {
                $qb->leftJoin('u.clients', 'c');
                $nameBasedQuery = 'lower(u.email) LIKE :qLike OR lower(u.firstname) LIKE :qLike OR lower(u.lastname) LIKE :qLike';

                if ($includeClients) {
                    $nameBasedQuery .= ' OR lower(c.firstname) LIKE :qLike OR lower(c.lastname) LIKE :qLike';
                }

                $qb->andWhere($nameBasedQuery);

                $qb->setParameter('qLike', '%' . strtolower($q) . '%');
            }
        }

        $qb->groupBy('u.id');
        $this->setJmsSerialiserGroups(['user']);

        return $qb->getQuery()->getResult();
    }

    /**
     * Requires client secret.
     *
     * @Route("/recreate-token/{email}/{type}", defaults={"email": "none"}, requirements={
     *   "type" = "(activate|pass-reset)"
     * }, methods={"PUT"})
     */
    public function recreateToken(Request $request, $email, $type)
    {
        if (!$this->getAuthService()->isSecretValid($request)) {
            throw new \RuntimeException('client secret not accepted.', 403);
        }

        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => strtolower($email)]);

        $hasAdminSecret = $this->getAuthService()->isSecretValidForRole(User::ROLE_ADMIN, $request);

        if (!$hasAdminSecret && $user->getRoleName() == User::ROLE_ADMIN) {
            throw new \RuntimeException('Admin emails not accepted.', 403);
        }

        $user->recreateRegistrationToken();

        $this->getEntityManager()->flush($user);

        $this->setJmsSerialiserGroups(['user']);

        return $user;
    }

    /**
     * @Route("/get-by-token/{token}", methods={"GET"})
     */
    public function getByToken(Request $request, $token)
    {
        if (!$this->getAuthService()->isSecretValid($request)) {
            throw new \RuntimeException('client secret not accepted.', 403);
        }

        $user = $this->findEntityBy(User::class, ['registrationToken' => $token], 'User not found');
        /* @var $user User */

        if (!$this->getAuthService()->isSecretValidForRole($user->getRoleName(), $request)) {
            throw new \RuntimeException($user->getRoleName() . ' user role not allowed from this client.', 403);
        }

        // `user-login` contains number of clients and reports, needed to properly redirect the user to the right page after activation
        $this->setJmsSerialiserGroups(['user', 'user-login']);

        return $user;
    }

    /**
     * @Route("/agree-terms-use/{token}", methods={"PUT"})
     */
    public function agreeTermsUSe(Request $request, $token)
    {
        if (!$this->getAuthService()->isSecretValid($request)) {
            throw new \RuntimeException('client secret not accepted.', 403);
        }

        $user = $this->findEntityBy(User::class, ['registrationToken' => $token], 'User not found');
        /* @var $user User */

        if (!$this->getAuthService()->isSecretValidForRole($user->getRoleName(), $request)) {
            throw new \RuntimeException($user->getRoleName() . ' user role not allowed from this client.', 403);
        }

        $user->setAgreeTermsUse(true);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush($user);

        return $user->getId();
    }

    /**
     * call setters on User when $data contains values.
     * //TODO move to service
     *
     * @param User $user
     * @param array          $data
     */
    private function populateUser(User $user, array $data)
    {
        // Cannot easily(*) use JSM deserialising with already constructed objects.
        // Also. It'd be possible to differentiate when a NULL value is intentional or not
        // (*) see options here https://github.com/schmittjoh/serializer/issues/79
        // http://jmsyst.com/libs/serializer/master/event_system

        $this->hydrateEntityWithArrayData($user, $data, [
            'firstname' => 'setFirstname',
            'lastname' => 'setLastname',
            'email' => 'setEmail',
            'address1' => 'setAddress1',
            'address2' => 'setAddress2',
            'address3' => 'setAddress3',
            'address_postcode' => 'setAddressPostcode',
            'address_country' => 'setAddressCountry',
            'phone_alternative' => 'setPhoneAlternative',
            'phone_main' => 'setPhoneMain',
            'ndr_enabled' => 'setNdrEnabled',
            'ad_managed' => 'setAdManaged',
            'role_name' => 'setRoleName',
            'job_title' => 'setJobTitle',
            'co_deputy_client_confirmed' => 'setCoDeputyClientConfirmed',
        ]);

        if (array_key_exists('last_logged_in', $data)) {
            $user->setLastLoggedIn(new \DateTime($data['last_logged_in']));
        }

        if (!empty($data['registration_token'])) {
            $user->setRegistrationToken($data['registration_token']);
        }

        if (!empty($data['token_date'])) { //important, keep this after "setRegistrationToken" otherwise date will be reset
            $user->setTokenDate(new \DateTime($data['token_date']));
        }

        if (!empty($data['role_name'])) {
            $roleToSet = $data['role_name'];
            $user->setRoleName($roleToSet);
        }

        return $user;
    }

    /**
     * @Route("/{id}/team", requirements={"id":"\d+"}, methods={"GET"})
     * @Security("has_role('ROLE_ORG')")
     */
    public function getTeamByUserId(Request $request, $id)
    {
        /** @var User|null $user */
        $user = $this->userRepository->find($id);

        /** @var User $loggedInUser */
        $loggedInUser = $this->getUser();

        if (!$user) {
            throw new \RuntimeException('User not found', 419);
        }

        if ($user->getTeams()->first() !== $loggedInUser->getTeams()->first()) {
            throw $this->createAccessDeniedException('User not part of the same team');
        }

        $groups = $request->query->has('groups') ?
            (array) $request->query->get('groups') :
            ['team', 'team-users', 'user'];

        $this->setJmsSerialiserGroups($groups);

        return $user->getTeams()->first();
    }
}
