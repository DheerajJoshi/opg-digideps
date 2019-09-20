<?php
namespace AppBundle\Security;

use AppBundle\Entity\Client;
use AppBundle\Entity\Organisation;
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class ClientVoter extends Voter
{
    /** @var string */
    const VIEW = 'view';

    /** @var string */
    const EDIT = 'edit';

    /** @var Security  */
    private $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::VIEW, self::EDIT]) && $subject instanceof Client;
    }

    /**
     * @param string $attribute
     * @param Client $client
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $client, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }


        switch ($attribute) {
            case self::VIEW:
            case self::EDIT:
                return $this->canManage($client, $user);

            default:
                throw new \LogicException('This code should not be reached!');
        }
    }

    /**
     * @param Client $client
     * @param User $user
     * @return bool
     */
    private function canManage(Client $client, User $user)
    {
        // using permission flag ensures both checks are made
        $permission = false;

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }


        if ($client->userBelongsToClientsOrganisation($user)) {
            $permission = true;
        }

        // @to-do move tto ArrayCollection and use contains
        if (in_array($user->getId(), $client->getUserIds()) || $permission) {
            return true;
        }

        return false;
    }
}
