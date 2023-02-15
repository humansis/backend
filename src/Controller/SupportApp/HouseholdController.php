<?php

declare(strict_types=1);

namespace Controller\SupportApp;

use Component\Country\Countries;
use FOS\RestBundle\Controller\Annotations as Rest;
use Repository\BeneficiaryRepository;

class HouseholdController extends AbstractSupportAppController
{
    public function __construct(private readonly BeneficiaryRepository $beneficiaryRepository, private readonly Countries $countries)
    {
    }

    /**
     * @Rest\Get("/support-app/v1/households/headStatistics")
     *
     */
    public function getHeadStatistics()
    {
        $headStatistics = [];
        foreach ($this->countries->getAll(true) as $country) {
            $headStatistics[$country->getIso3()] = 0;
        }

        foreach ($this->beneficiaryRepository->getNumberHouseholdWithoutHead() as $obj) {
            $headStatistics[$obj['countryIso3']] = $obj['total'];
        }
        return $this->json($headStatistics);
    }
}
