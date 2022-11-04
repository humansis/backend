<?php

declare(strict_types=1);

namespace Component\Import\Finishing;

use DateTimeImmutable;
use DateTimeInterface;
use Entity\Location;
use Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Component\Import\Utils\ImportDateConverter;
use Entity\ImportQueue;
use Enum\EnumTrait;
use InputType\Beneficiary\Address\CampAddressInputType;
use InputType\Beneficiary\Address\CampInputType;
use InputType\Beneficiary\Address\ResidenceAddressInputType;
use InputType\Beneficiary\CountrySpecificsAnswerInputType;
use InputType\HouseholdCreateInputType;
use InputType\HouseholdUpdateInputType;
use InputType\Beneficiary\BeneficiaryInputType;
use Component\Import;

class HouseholdDecoratorBuilder
{
    private ?\Component\Import\Integrity\ImportLine $householdLine = null;

    /** @var Import\Integrity\ImportLine[] */
    private $importLines;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly Import\Integrity\ImportLineFactory $importLineFactory, private readonly BeneficiaryDecoratorBuilder $beneficiaryDecoratorBuilder)
    {
    }

    public function buildHouseholdInputType(ImportQueue $importQueue): ?HouseholdCreateInputType
    {
        $this->householdLine = $this->importLineFactory->create($importQueue, 0);
        $this->importLines = $this->importLineFactory->createAll($importQueue);

        $household = new HouseholdCreateInputType();
        $this->fillHousehold($household, $importQueue->getImport()->getCountryIso3());

        return $household;
    }

    public function buildHouseholdUpdateType(ImportQueue $importQueue): ?HouseholdUpdateInputType
    {
        $this->householdLine = $this->importLineFactory->create($importQueue, 0);
        $this->importLines = $this->importLineFactory->createAll($importQueue);

        $household = new HouseholdUpdateInputType();
        $this->fillHousehold($household, $importQueue->getImport()->getCountryIso3());

        return $household;
    }

    private function fillHousehold(HouseholdUpdateInputType $household, string $countryIso3): void
    {
        $household->setProjectIds([]);
        $household->setCopingStrategiesIndex($this->householdLine->copingStrategiesIndex);
        $household->setDebtLevel($this->householdLine->debtLevel);
        $household->setFoodConsumptionScore($this->householdLine->foodConsumptionScore);
        $household->setIncome($this->householdLine->income);
        $household->setIso3($countryIso3);
        $household->setNotes($this->householdLine->notes);
        $household->setLatitude((string) $this->householdLine->latitude);
        $household->setLongitude((string) $this->householdLine->longitude);
        $household->setLivelihood($this->householdLine->livelihood);
        $household->setEnumeratorName($this->householdLine->enumeratorName);
        $household->setShelterStatus($this->householdLine->shelterStatus);
        if (!empty($this->householdLine->supportDateReceived)) {
            $household->setSupportDateReceived(
                $this->householdLine->getSupportDateReceived()
            );
        }
        $household->setSupportReceivedTypes($this->householdLine->supportReceivedTypes);
        $household->setAssets($this->householdLine->assets);

        foreach ($this->householdLine->countrySpecifics as $countrySpecificId => $data) {
            $specificAnswer = new CountrySpecificsAnswerInputType();
            $specificAnswer->setCountrySpecificId($countrySpecificId);
            $specificAnswer->setAnswer($data['value']);
            $household->addCountrySpecificAnswer($specificAnswer);
        }

        // defined must be Camp or Address - it's checked in Integrity Checking
        if ($this->householdLine->isCampValid()) {
            $household->setCampAddress($this->buildCampAddress($this->householdLine, $countryIso3));
        } else {
            /** @var LocationRepository $locationRepository */
            $locationRepository = $this->entityManager->getRepository(Location::class);
            $adms = [
                EnumTrait::normalizeValue($this->householdLine->adm1),
                EnumTrait::normalizeValue($this->householdLine->adm2),
                EnumTrait::normalizeValue($this->householdLine->adm3),
                EnumTrait::normalizeValue($this->householdLine->adm4),
            ];
            $locationsArray = array_filter($adms, fn($value) => !empty($value));

            $location = $locationRepository->getByNormalizedNames($countryIso3, $locationsArray);

            if (null !== $location) {
                $address = new ResidenceAddressInputType();
                $address->setStreet($this->householdLine->addressStreet);
                $address->setPostcode($this->householdLine->addressPostcode);
                $address->setNumber($this->householdLine->addressNumber);
                $address->setLocationId($location->getId());
                $household->setResidenceAddress($address);
            }
        }

        foreach ($this->importLines as $importLine) {
            $beneficiary = $this->beneficiaryDecoratorBuilder->buildBeneficiaryInputType($importLine);
            $household->addBeneficiary($beneficiary);
        }

        $i = 1;
        foreach ($this->buildNamelessMembers() as $namelessMember) {
            $namelessMember->setResidencyStatus($household->getHouseholdHead()->getResidencyStatus());
            $namelessMember->setLocalFamilyName($household->getHouseholdHead()->getLocalFamilyName());
            $namelessMember->setEnFamilyName($household->getHouseholdHead()->getEnFamilyName());
            $namelessMember->setEnGivenName("Member $i");
            $namelessMember->setLocalGivenName("Member $i");
            $household->addBeneficiary($namelessMember);
            $i++;
        }
    }

    /**
     * @return BeneficiaryInputType[]
     */
    private function buildNamelessMembers(): iterable
    {
        foreach ($this->buildMembersByAgeAndGender('F', 1, $this->householdLine->f0 ?? 0) as $bnf) {
            yield $bnf;
        }
        foreach ($this->buildMembersByAgeAndGender('M', 1, $this->householdLine->m0 ?? 0) as $bnf) {
            yield $bnf;
        }
        foreach ($this->buildMembersByAgeAndGender('F', 3, $this->householdLine->f2 ?? 0) as $bnf) {
            yield $bnf;
        }
        foreach ($this->buildMembersByAgeAndGender('M', 3, $this->householdLine->m2 ?? 0) as $bnf) {
            yield $bnf;
        }
        foreach ($this->buildMembersByAgeAndGender('F', 7, $this->householdLine->f6 ?? 0) as $bnf) {
            yield $bnf;
        }
        foreach ($this->buildMembersByAgeAndGender('M', 7, $this->householdLine->m6 ?? 0) as $bnf) {
            yield $bnf;
        }
        foreach ($this->buildMembersByAgeAndGender('F', 19, $this->householdLine->f18 ?? 0) as $bnf) {
            yield $bnf;
        }
        foreach ($this->buildMembersByAgeAndGender('M', 19, $this->householdLine->m18 ?? 0) as $bnf) {
            yield $bnf;
        }
        foreach ($this->buildMembersByAgeAndGender('F', 66, $this->householdLine->f60 ?? 0) as $bnf) {
            yield $bnf;
        }
        foreach ($this->buildMembersByAgeAndGender('M', 66, $this->householdLine->m60 ?? 0) as $bnf) {
            yield $bnf;
        }
    }

    private function buildMembersByAgeAndGender(string $gender, int $age, int $count): iterable
    {
        if (0 === $count) {
            return;
        }
        $today = new DateTimeImmutable();

        for ($i = 0; $i < $count; $i++) {
            $beneficiary = new BeneficiaryInputType();
            $beneficiary->setDateOfBirth($today->modify("-$age year")->format(DateTimeInterface::ISO8601));
            $beneficiary->setGender($gender);
            $beneficiary->setIsHead(false);
            yield $beneficiary;
        }
    }

    private function buildCampAddress($line, string $countryIso3): CampAddressInputType
    {
        $campAddress = new CampAddressInputType();
        $campAddress->setCamp($this->buildCampInputType($line, $countryIso3));
        $campAddress->setTentNumber($line->tentNumber);

        return $campAddress;
    }

    private function buildCampInputType($line, string $countryIso3): CampInputType
    {
        $campInput = new CampInputType();
        $campInput->setName($line->campName);

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->entityManager->getRepository(Location::class);

        $adms = [
            EnumTrait::normalizeValue($line->adm1),
            EnumTrait::normalizeValue($line->adm2),
            EnumTrait::normalizeValue($line->adm3),
            EnumTrait::normalizeValue($line->adm4),
        ];

        $locationsArray = array_filter($adms, fn($value) => !empty($value));

        $location = $locationRepository->getByNormalizedNames($countryIso3, $locationsArray);
        if ($location !== null) {
            $campInput->setLocationId($location->getId());
        }

        return $campInput;
    }
}
