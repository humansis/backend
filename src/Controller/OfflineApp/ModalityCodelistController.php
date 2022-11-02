<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Enum\ModalityType;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Codelist\CodeItem;
use Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+12 hours", public=true)
 */
class ModalityCodelistController extends AbstractController
{
    /**
     * @Rest\Get("/offline-app/v1/modality-types")
     */
    public function allTypes(): JsonResponse
    {
        $data = [];

        $types = ModalityType::values();
        foreach ($types as $type) {
            $data[] = new CodeItem($type, $type);
        }

        return $this->json($data);
    }
}
