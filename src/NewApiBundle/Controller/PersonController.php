<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\Person;
use NewApiBundle\InputType\PersonFilterInputType;
use NewApiBundle\Repository\PersonRepository;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

class PersonController extends AbstractController
{
    /**
     * @Rest\Get("/persons/{id}")
     *
     * @param Person $person
     *
     * @return JsonResponse
     */
    public function item(Person $person): JsonResponse
    {
        return $this->json($person);
    }

    /**
     * @Rest\Get("/persons")
     *
     * @param Pagination            $pagination
     *
     * @param PersonFilterInputType $filterInputType
     *
     * @return JsonResponse
     */
    public function list(Pagination $pagination, PersonFilterInputType $filterInputType): JsonResponse
    {
        /** @var PersonRepository $repository */
        $repository = $this->getDoctrine()->getRepository(Person::class);
        $data = $repository->findByParams($pagination, $filterInputType);

        return $this->json($data);
    }
}
