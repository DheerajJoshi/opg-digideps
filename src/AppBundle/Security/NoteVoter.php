<?php

namespace AppBundle\Security;

use AppBundle\Entity\Client;
use AppBundle\Entity\Note;
use AppBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class NoteVoter extends Voter
{
    const ADD_NOTE = 'add-note';
    const EDIT_NOTE = 'edit-note';
    const DELETE_NOTE = 'delete-note';

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * NoteVoter constructor.
     *
     * @param AccessDecisionManagerInterface $decisionManager
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    /**
     * Does this voter support the attribute?
     *
     * @param  string $attribute
     * @param  mixed  $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        switch ($attribute) {
            case self::ADD_NOTE:
            case self::DELETE_NOTE:
                return true;
            case self::EDIT_NOTE:
                // only vote on User objects inside this voter
                if ($attribute === self::EDIT_NOTE && $subject instanceof Note) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Vote on whether to grant attribute permission on subject
     *
     * @param  string         $attribute
     * @param  mixed          $subject
     * @param  TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var User $loggedInUser */
        $loggedInUser= $token->getUser();

        if (!$loggedInUser instanceof User && $loggedInUser->isPaDeputy()) {
            // the loggedUser must be logged in PA user; if not, deny access
            return false;
        }

        switch ($attribute) {
            case self::ADD_NOTE:
                /** @var Client $subject */
                return $this->clientBelongsToUserTeam($loggedInUser, $subject);
            case self::EDIT_NOTE:
            case self::DELETE_NOTE:
                /** @var Note $subject */
                return $this->clientBelongsToUserTeam($loggedInUser, $subject->getClient());
        }

        return false;
    }

    /**
     * Does the logged in user belong to the client
     *
     * @param User $loggedInUser
     * @param Client $client
     *
     * @return bool
     */
    private function clientBelongsToUserTeam(User $loggedInUser, Client $client)
    {
        return in_array($loggedInUser->getId(), $client->getUsers());
    }
}
