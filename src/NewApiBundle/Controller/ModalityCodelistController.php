<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Modality;
use DistributionBundle\Entity\ModalityType;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Codelist\CodeItem;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Cache(expires="+5 days", public=true)
 */
class ModalityCodelistController extends AbstractController
{
    /**
     * @Rest\Get("/modalities")
     *
     * @return JsonResponse
     */
    public function modalities(): JsonResponse
    {
        $fn = function (Modality $modality) {
            return new CodeItem($modality->getName(), $modality->getName());
        };

        $modalities = $this->getDoctrine()->getRepository(Modality::class)->findAll();

        return $this->json(new Paginator(array_map($fn, $modalities)));
    }

    /**
     * @Rest\Get("/modalities/{code}/types")
     *
     * @param string $code
     *
     * @return JsonResponse
     */
    public function types(string $code): JsonResponse
    {
        $modality = $this->getDoctrine()->getRepository(Modality::class)->findOneBy(['name' => $code]);
        if (!$modality) {
            throw $this->createNotFoundException('Modality not found');
        }

        $data = [];
        foreach ($modality->getModalityTypes() as $type) {
            /** @var ModalityType $type */
            if (!$type->isInternal()) {
                $data[] = new CodeItem($type->getName(), $type->getName());
            }
        }

        return $this->json(new Paginator($data));
    }
}
