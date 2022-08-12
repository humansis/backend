<?php


namespace NewApiBundle\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

/**
 * Class BMSVoter+q
 * @package NewApiBundle\Voter
 */
abstract class BMSVoter extends Voter
{

    /** @var RoleHierarchy $roleHierarchy */
    private $roleHierarchy;

    public function __construct(RoleHierarchy $roleHierarchy)
    {
        $this->roleHierarchy = $roleHierarchy;
    }

    /**
     * @param array $myRoles
     * @param string $attribute
     * @return bool
     */
    protected function hasRole(array $myRoles, string $attribute)
    {
        $myArrayRoles = $this->getMyReachableRoles($myRoles);

        foreach ($myArrayRoles as $role) {
            if ($role->getRole() === strval($attribute)) {
                return true;
            }
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
        foreach ($my_roles as $role) {
            $arrayRoles[] = new Role($role);
        }
        return $this->roleHierarchy->getReachableRoles($arrayRoles);
    }
}
