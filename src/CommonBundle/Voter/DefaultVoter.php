<?php


namespace CommonBundle\Voter;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use UserBundle\Entity\User;
use UserBundle\Entity\UserCountry;

/**
 * Class DefaultVoter
 * Check if the user is connected, if he/she has the route needed role and if he/she is assigned to the country
 *
 * @package CommonBundle\Voter
 */
class DefaultVoter extends BMSVoter
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var RequestStack $requestStack */
    private $requestStack;


    /**
     * DefaultVoter constructor.
     * @param RoleHierarchy $roleHierarchy
     * @param EntityManagerInterface $entityManager
     * @param RequestStack $requestStack
     */
    public function __construct(RoleHierarchy $roleHierarchy, EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        parent::__construct($roleHierarchy);
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
            return false;
        }
        /**
         * @var User $user
         */
        if (!$this->hasRole($user->getRoles(), $attribute))
            return false;

        if (!$this->requestStack->getCurrentRequest()->request->has('__country'))
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
            ->findOneBy([
                "user" => $user,
                "iso3" => $countryISO3
            ]);
        return ($userCountry instanceof UserCountry);
    }
}