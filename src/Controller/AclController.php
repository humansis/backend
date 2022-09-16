<?php

namespace Controller;

use Pagination\Paginator;
use FOS\RestBundle\Controller\Annotations as Rest;
use Entity\Role;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;

class AclController extends AbstractController
{
    // list of roles to be processed by FE
    private const ROLES = [
        'ROLE_ADMIN',
        'ROLE_REGIONAL_MANAGER',
        'ROLE_COUNTRY_MANAGER',
        'ROLE_PROJECT_MANAGER',
        'ROLE_PROJECT_OFFICER',
        'ROLE_FIELD_OFFICER',
        'ROLE_ENUMERATOR',
    ];

    /**
     * @Rest\Get("/web-app/v1/acl/roles")
     * @Cache(expires="+5 days", public=true)
     *
     * @return JsonResponse
     */
    public function roles(): JsonResponse
    {
        $roles = $this->getDoctrine()->getRepository(Role::class)->findAll();

        $filtered = [];
        foreach ($roles as $role) {
            if (!in_array($role->getCode(), self::ROLES)) {
                continue;
            }

            $filtered[] = $role;
        }

        return $this->json(new Paginator($filtered));
    }

    /**
     * @Rest\Get("/web-app/v1/acl/roles/{code}")
     * @ParamConverter("role", options={"mapping": {"code": "code"}})
     * @Cache(expires="+5 days", public=true)
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
