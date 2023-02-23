<?php

declare(strict_types=1);

namespace Tests\ComponentHelper;

use DateTimeImmutable;
use Entity\Beneficiary;
use Entity\SmartcardBeneficiary;
use Utils\SmartcardService;

trait SmartcardHelper
{
    private function getSmartcardForBeneficiary(string $serialNumber, Beneficiary $beneficiary): SmartcardBeneficiary
    {
        $smartcardService = self::getContainer()->get(SmartcardService::class);

        $smartcardBeneficiary = $smartcardService->getOrCreateActiveSmartcardForBeneficiary(
            $serialNumber,
            $beneficiary,
            new DateTimeImmutable()
        );

        $this->em->persist($smartcardBeneficiary);
        $this->em->flush();

        return $smartcardBeneficiary;
    }
}
