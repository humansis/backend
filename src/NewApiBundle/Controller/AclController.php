<?php

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity\Role;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;

class AclController extends AbstractController
{
    /**
     * @Rest\Get("/acl/roles")
     *
     * @return JsonResponse
     */
    public function roles(): JsonResponse
    {
        $roles = $this->getDoctrine()->getRepository(Role::class)->findAll();

        $filtered = [];
        foreach ($roles as $role) {
            if ($role->getPrivileges()->isEmpty()) {
                continue;
            }

            $filtered[] = $role;
        }

        return $this->json(new Paginator($filtered));
    }

    /**
     * @Rest\Get("/acl/roles/{code}")
     * @ParamConverter("role", options={"mapping": {"code": "code"}})
     *
     * @param Role $role
     *
     * @return JsonResponse
     */
    public function getRole(Role $role): JsonResponse
    {
        return $this->json($role);
    }
}
