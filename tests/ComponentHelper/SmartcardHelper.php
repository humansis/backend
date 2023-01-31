<?php

declare(strict_types=1);

namespace Tests\ComponentHelper;

use DateTimeImmutable;
use Entity\Beneficiary;
use Entity\Smartcard;
use Enum\SmartcardStates;
use Utils\SmartcardService;

trait SmartcardHelper
{
    private function getSmartcardForBeneficiary(string $serialNumber, Beneficiary $beneficiary): Smartcard
    {
        $smartcardService = self::getContainer()->get(SmartcardService::class);

        $smartcard = $smartcardService->getOrCreateActiveSmartcardForBeneficiary(
            $serialNumber,
            $beneficiary,
            new DateTimeImmutable()
        );

        $this->em->persist($smartcard);
        $this->em->flush();

        return $smartcard;
    }
}
