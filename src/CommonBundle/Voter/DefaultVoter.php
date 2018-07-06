<?php


namespace CommonBundle\Voter;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use UserBundle\Entity\User;
use UserBundle\Entity\UserCountry;

class DefaultVoter extends Voter
{

    /** @var RoleHierarchy $roleHierarchy */
    private $roleHierarchy;

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var RequestStack $requestStack */
    private $requestStack;

    public function __construct(RoleHierarchy $roleHierarchy, EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->roleHierarchy = $roleHierarchy;
        $this->em = $entityManager;
        $this->requestStack = $requestStack;
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
        dump(true);
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
        dump($this->requestStack);
        $user = $token->getUser();
        if (!$user instanceof User)
        {
            return false;
        }
        /**
         * @var User $user
         */
        if (!$this->hasRole($user->getRoles(), $attribute))
            return false;

        if(!$this->requestStack->getCurrentRequest()->request->has('__country'))
            return false;

        $countryISO3 = $this->requestStack->getCurrentRequest()->request->get('__country');
        if (!$this->hasCountry($user, $countryISO3))
            return false;

        return true;
    }

    /**
     * Check if the user is assigned on the country
     * @param User $user
     * @param $countryISO3
     * @return bool
     */
    protected function hasCountry(User $user, $countryISO3)
    {
        $userCountry = $this->em->getRepository(UserCountry::class)
            ->findBy([
                "user" => $user,
                "iso3" => $countryISO3
            ]);
        return ($userCountry instanceof UserCountry);
    }

    /**
     * @param array $myRoles
     * @param string $attribute
     * @return bool
     */
    protected function hasRole(array $myRoles, string $attribute)
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