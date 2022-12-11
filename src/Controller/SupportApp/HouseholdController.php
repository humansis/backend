<?php

declare(strict_types=1);

namespace Controller\SupportApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use Repository\BeneficiaryRepository;

class HouseholdController extends AbstractSupportAppController
{
    public function __construct(private readonly BeneficiaryRepository $beneficiaryRepository)
    {
    }

    /**
     * @Rest\Get("/support-app/v1/households/headStatistics")
     *
     */
    public function getHeadStatistics()
    {
        return $this->json($this->beneficiaryRepository->getNumberHouseholdWithoutHead());
    }
}
