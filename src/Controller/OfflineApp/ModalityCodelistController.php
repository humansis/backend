<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Pagination\Paginator;
use Entity\ModalityType;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Codelist\CodeItem;
use Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+100 days", public=true)
 */
class ModalityCodelistController extends AbstractController
{
    /**
     * @Rest\Get("/offline-app/v1/modality-types")
     *
     * @return JsonResponse
     */
    public function allTypes(): JsonResponse
    {
        $data = [];

        /** @var ModalityType[] $types */
        $types = $this->getDoctrine()->getRepository(ModalityType::class)->findBy(['internal' => false]);
        foreach ($types as $type) {
            $data[] = new CodeItem($type->getName(), $type->getName());
        }

        return $this->json($data);
    }
}
