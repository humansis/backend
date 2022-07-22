<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use CommonBundle\Pagination\Paginator;
use NewApiBundle\Component\Codelist\CodeLists;
use NewApiBundle\Enum\Domain;
use NewApiBundle\Enum\ProductCategoryType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductCategoryCodelistController extends AbstractController
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    /**
     * @Rest\Get("/web-app/v1/product-categories/types")
     *
     * @return JsonResponse
     */
    public function getTypes(): JsonResponse
    {
        $data = CodeLists::mapEnum(ProductCategoryType::values(), $this->translator, Domain::ENUMS);

        return $this->json(new Paginator($data));
    }
}
