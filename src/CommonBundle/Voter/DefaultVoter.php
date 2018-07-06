<?php


namespace CommonBundle\Voter;


use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use UserBundle\Entity\User;

class DefaultVoter extends Voter
{

    /**
     * @var RoleHierarchy $roleHierarchy
     */
    private $roleHierarchy;

    public function __construct(RoleHierarchy $roleHierarchy)
    {
        $this->roleHierarchy = $roleHierarchy;
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports($attribute, $subject)
    {
        return true;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof User)
        {
            return VoterInterface::ACCESS_DENIED;
        }
        /**
         * @var User $user
         */
        return $this->canDoTask($user->getRoles(), $attribute);
    }

    /**
     * @param array $myRoles
     * @param string $attribute
     * @return bool
     */
    protected function canDoTask(array $myRoles, string $attribute)
    {
        $myArrayRoles = $this->getMyReachableRoles($myRoles);

        foreach($myArrayRoles as $role)
        {
            if ($role->getRole() === strval($attribute))
                return true;
        }

        return false;
    }

    /**
     * @param array $my_roles
     * @return array|\Symfony\Component\Security\Core\Role\RoleInterface[]
     */
    protected function getMyReachableRoles(array $my_roles)
    {
        $arrayRoles = [];
        foreach ($my_roles as $role)
        {
            $arrayRoles[] = new Role($role);
        }
        return $this->roleHierarchy->getReachableRoles($arrayRoles);
    }
}