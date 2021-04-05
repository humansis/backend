<?php

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Entity\Role;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;

class AclController extends AbstractController
{
    /**
     * @Rest\Get("/acl/roles/{code}")
     * @ParamConverter("role", options={"mapping": {"code": "name"}})
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
