<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use CommonBundle\Entity\Location;
use CommonBundle\Repository\LocationRepository;
use NewApiBundle\Component\Import\Utils\ImportDateConverter;
use NewApiBundle\Enum\EnumTrait;
use NewApiBundle\InputType\Beneficiary\Address\CampAddressInputType;
use NewApiBundle\InputType\Beneficiary\Address\CampInputType;
use NewApiBundle\InputType\Beneficiary\Address\ResidenceAddressInputType;
use NewApiBundle\InputType\Beneficiary\CountrySpecificsAnswerInputType;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\InputType\HouseholdCreateInputType;
use NewApiBundle\InputType\HouseholdUpdateInputType;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;

/* TODO many unused parameters in HouseholdHead / HouseholdMember:
    $campName
    $tentNumber
*/
trait HouseholdInputBuilderTrait
{
    public function buildHouseholdInputType(): ?HouseholdCreateInputType
    {
        if (false === $this->head) {
            return null;
        }
        $household = new HouseholdCreateInputType();
        $this->fillHousehold($household);
        return $household;
    }

    public function buildHouseholdUpdateType(): ?HouseholdUpdateInputType
    {
        if (false === $this->head) {
            return null;
        }
        $household = new HouseholdUpdateInputType();
        $this->fillHousehold($household);
        return $household;
    }

    /**
     * @param HouseholdUpdateInputType $household
     */
    private function fillHousehold(HouseholdUpdateInputType $household): void
    {
        $household->setProjectIds([]);
        $household->setCopingStrategiesIndex($this->copingStrategiesIndex);
        $household->setDebtLevel($this->debtLevel);
        $household->setFoodConsumptionScore($this->foodConsumptionScore);
        $household->setIncomeLevel($this->incomeLevel);
        $household->setIso3($this->countryIso3);
        $household->setNotes($this->notes);
        $household->setLatitude($this->latitude);
        $household->setLongitude($this->longitude);
        $household->setLivelihood($this->getLivelihood());
        $household->setEnumeratorName($this->enumeratorName);
        $household->setShelterStatus($this->getShelterStatus());
        $household->setSupportDateReceived($this->supportDateReceived ? ImportDateConverter::toDatetime($this->supportDateReceived)->format(\DateTimeInterface::ISO8601) : null);
        $household->setSupportReceivedTypes($this->getSupportReceivedTypes());
        $household->setAssets($this->getAssets());

        foreach ($this->countrySpecifics as $countrySpecificId => $answer) {
            $specificAnswer = new CountrySpecificsAnswerInputType();
            $specificAnswer->setCountrySpecificId($countrySpecificId);
            $specificAnswer->setAnswer($answer);
            $household->addCountrySpecificAnswer($specificAnswer);
        }

        // defined must be Camp or Address - it's checked in Integrity Checking
        if($this->campName && $this->tentNumber){
            $household->setCampAddress($this->buildCampAddress());
        } else {
            /** @var LocationRepository $locationRepository */
            $locationRepository = $this->entityManager->getRepository(Location::class);
            $adms = [EnumTrait::normalizeValue($this->adm1), EnumTrait::normalizeValue($this->adm2), EnumTrait::normalizeValue($this->adm3), EnumTrait::normalizeValue($this->adm4)];
            $locationsArray = array_filter($adms, function ($value) {
                return !empty($value);
            });

            $location = $locationRepository->getByNormalizedNames($this->countryIso3, $locationsArray);

            if (null !== $location) {
                $address = new ResidenceAddressInputType();
                $address->setStreet($this->addressStreet);
                $address->setPostcode($this->addressPostcode);
                $address->setNumber($this->addressNumber);
                $address->setLocationId($location->getId());
                $household->setResidenceAddress($address);
            }
        }

        $head = $this->buildBeneficiaryInputType();
        $head->setIsHead(true);

        $household->addBeneficiary($head);

        $i = 1;
        foreach ($this->buildNamelessMembers() as $namelessMember) {
            $namelessMember->setResidencyStatus($head->getResidencyStatus());
            $namelessMember->setLocalFamilyName($head->getLocalFamilyName());
            $namelessMember->setEnFamilyName($head->getEnFamilyName());
            $namelessMember->setEnGivenName("Member $i");
            $namelessMember->setLocalGivenName("Member $i");
            $household->addBeneficiary($namelessMember);
            $i++;
        }
    }

    public function buildBeneficiaryInputType(): BeneficiaryInputType
    {
        $beneficiary = new BeneficiaryInputType();
        $beneficiary->setDateOfBirth($this->getDateOfBirth() ? $this->getDateOfBirth()->format(\DateTimeInterface::ISO8601) : null);
        $beneficiary->setLocalFamilyName($this->localFamilyName);
        $beneficiary->setLocalGivenName($this->localGivenName);
        $beneficiary->setLocalParentsName($this->localParentsName);
        $beneficiary->setEnFamilyName($this->englishFamilyName);
        $beneficiary->setEnGivenName($this->englishGivenName);
        $beneficiary->setEnParentsName($this->englishParentsName);
        $beneficiary->setGender($this->getGender());
        $beneficiary->setResidencyStatus($this->getResidencyStatus());
        $beneficiary->setIsHead(false);
        $beneficiary->setVulnerabilityCriteria($this->getVulnerabilityCriteria());

        if (!is_null($this->idType)) { //TODO check, that id card is filled completely
            $nationalId = new NationalIdCardInputType();
            $nationalId->setType($this->getIdType());
            $nationalId->setNumber((string) $this->idNumber);
            $beneficiary->addNationalIdCard($nationalId);
        }

        if (!is_null($this->numberPhone1)) { //TODO check, that phone is filled completely in import
            $phone1 = new PhoneInputType();
            $phone1->setNumber((string) $this->numberPhone1);
            $phone1->setType($this->getTypePhone1());
            $phone1->setPrefix((string) $this->prefixPhone1);
            $phone1->setProxy($this->isProxyPhone1());
            $beneficiary->addPhone($phone1);
        }

        if (!is_null($this->numberPhone2)) { //TODO check, that phone is filled completely in import
            $phone2 = new PhoneInputType();
            $phone2->setNumber((string) $this->numberPhone2);
            $phone2->setType($this->getTypePhone2());
            $phone2->setPrefix((string) $this->prefixPhone2);
            $phone2->setProxy($this->isProxyPhone2());
            $beneficiary->addPhone($phone2);
        }

        return $beneficiary;
    }

    /**
     * @return BeneficiaryInputType[]
     */
    private function buildNamelessMembers(): iterable
    {
        foreach ($this->buildMembersByAgeAndGender('F', 1, $this->f0 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('M', 1, $this->m0 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('F', 3, $this->f2 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('M', 3, $this->m2 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('F', 7, $this->f6 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('M', 7, $this->m6 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('F', 19, $this->f18 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('M', 19, $this->m18 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('F', 66, $this->f60 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('M', 66, $this->m60 ?? 0) as $bnf) { yield $bnf; }
    }

    private function buildMembersByAgeAndGender(string $gender, int $age, int $count): iterable
    {
        if (0 === $count) return;
        $today = new \DateTimeImmutable();

        for ($i=0; $i<$count; $i++) {
            $beneficiary = new BeneficiaryInputType();
            $beneficiary->setDateOfBirth($today->modify("-$age year")->format('d-m-Y'));
            $beneficiary->setGender($gender);
            $beneficiary->setIsHead(false);
            yield $beneficiary;
        }
    }

    /**
     * @return CampAddressInputType
     */
    private function buildCampAddress(): CampAddressInputType
    {
        $campAddress = new CampAddressInputType();
        $campAddress->setCamp($this->buildCampInputType());
        $campAddress->setTentNumber($this->tentNumber);

        return $campAddress;
    }

    /**
     * @return CampInputType
     */
    private function buildCampInputType(): CampInputType
    {
        $campInput = new CampInputType();
        $campInput->setName($this->campName);

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->entityManager->getRepository(Location::class);

        $adms = [EnumTrait::normalizeValue($this->adm1), EnumTrait::normalizeValue($this->adm2), EnumTrait::normalizeValue($this->adm3), EnumTrait::normalizeValue($this->adm4)];

        $locationsArray = array_filter($adms, function ($value) {
            return !empty($value);
        });

        $location = $locationRepository->getByNormalizedNames($this->countryIso3,$locationsArray);
        if ($location !== null) {
            $campInput->setLocationId($location->getId());
        }

        return $campInput;
    }

}
