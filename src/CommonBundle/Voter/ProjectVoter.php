<?php


namespace CommonBundle\Voter;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\Project;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use NewApiBundle\Entity\User;
use NewApiBundle\Entity\UserProject;

/**
 * Class ProjectVoter
 * @package CommonBundle\Voter
 */
class ProjectVoter extends BMSVoter
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var \Monolog\Logger $logger */
    private $logger;

    public function __construct(RoleHierarchy $roleHierarchy, EntityManagerInterface $entityManager, \Monolog\Logger $logger)
    {
        parent::__construct($roleHierarchy);
        $this->em = $entityManager;
        $this->logger = $logger;
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
        if ($subject instanceof Project) {
            return true;
        }

        return false;
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
        /** @var Project $subject */
        /** @var User $user */
        $user = $token->getUser();
        $userProject = $this->em->getRepository(UserProject::class)->findOneBy([
            "user" => $user,
            "project" => $subject
        ]);
        if ($userProject instanceof UserProject) {
            return true;
        }

        $roles = $user->getRoles();
        if ($this->hasRole($roles, "ROLE_COUNTRY_MANAGER") || $this->hasRole($roles, "ROLE_REGIONAL_MANAGER")) {
            return true;
        }

        $this->logger->error('ProjectVoter - error', ['roles' => $roles, 'attribute' => $attribute, 'user' => $user->getUsername()]);
        return false;
    }
}
