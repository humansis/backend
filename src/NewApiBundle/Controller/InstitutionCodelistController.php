<?php

declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Institution;
use CommonBundle\Pagination\Paginator;
use NewApiBundle\Component\Codelist\CodeLists;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Cache(expires="+5 days", public=true)
 */
class InstitutionCodelistController extends AbstractController
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    
    /**
     * @Rest\Get("/web-app/v1/institutions/types")
     *
     * @return JsonResponse
     */
    public function getInstitutionTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(Institution::TYPE_ALL, $this->translator);

        return $this->json(new Paginator($data));
    }
}
