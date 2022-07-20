<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use CommonBundle\Pagination\Paginator;
use DistributionBundle\Entity\Modality;
use DistributionBundle\Entity\ModalityType;
use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\Component\Codelist\CodeItem;
use NewApiBundle\Component\Codelist\CodeLists;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Cache(expires="+5 days", public=true)
 */
class ModalityCodelistController extends AbstractController
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    
    /**
     * @Rest\Get("/web-app/v1/modalities")
     *
     * @return JsonResponse
     */
    public function modalities(): JsonResponse
    {
        $modalities = $this->getDoctrine()
            ->getRepository(Modality::class)
            ->getNames();

        $data = CodeLists::mapEnum($modalities, $this->translator, 'enums');
        
        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/modalities/types")
     *
     * @return JsonResponse
     */
    public function allTypes(): JsonResponse
    {
        $types = $this->getDoctrine()
            ->getRepository(ModalityType::class)
            ->getPublicNames();

        $data = CodeLists::mapEnum($types, $this->translator, 'enums');

        return $this->json(new Paginator($data));
    }

    /**
     * @Rest\Get("/web-app/v1/modalities/{code}/types")
     *
     * @param string $code
     *
     * @return JsonResponse
     */
    public function types(string $code): JsonResponse
    {
        $types = $this->getDoctrine()
            ->getRepository(ModalityType::class)
            ->getPublicNames($code);

        $data = CodeLists::mapEnum($types, $this->translator, 'enums');

        return $this->json(new Paginator($data));
    }
}
