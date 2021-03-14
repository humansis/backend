<?php
declare(strict_types=1);

namespace NewApiBundle\Controller;

use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Entity\Phone;
use NewApiBundle\InputType\NationalIdFilterInputType;
use NewApiBundle\InputType\PersonFilterInputType;
use NewApiBundle\InputType\PhoneFilterInputType;
use NewApiBundle\Repository\PersonRepository;
use NewApiBundle\Request\Pagination;
use Symfony\Component\HttpFoundation\JsonResponse;
use FOS\RestBundle\Controller\Annotations as Rest;

class PersonController extends AbstractController
{
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

    /**
     * @Rest\Get("/persons/national-ids")
     *
     * @param NationalIdFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function nationalIds(NationalIdFilterInputType $filter): JsonResponse
    {
        $nationalIds = $this->getDoctrine()->getRepository(NationalId::class)->findByParams($filter);

        return $this->json($nationalIds);
    }

    /**
     * @Rest\Get("/persons/national-ids/{id}")
     *
     * @param NationalId $nationalId
     *
     * @return JsonResponse
     */
    public function nationalId(NationalId $nationalId): JsonResponse
    {
        return $this->json($nationalId);
    }

    /**
     * @Rest\Get("/persons/phones")
     *
     * @param PhoneFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function phones(PhoneFilterInputType $filter): JsonResponse
    {
        $params = $this->getDoctrine()->getRepository(Phone::class)->findByParams($filter);

        return $this->json($params);
    }

    /**
     * @Rest\Get("/persons/phones/{id}")
     *
     * @param Phone $phone
     *
     * @return JsonResponse
     */
    public function phone(Phone $phone): JsonResponse
    {
        return $this->json($phone);
    }

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
}
