<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use NewApiBundle\InputType\Beneficiary\Address\ResidenceAddressInputType;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;
use NewApiBundle\InputType\HouseholdCreateInputType;
use NewApiBundle\InputType\HouseholdUpdateInputType;

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
        $household->setCopingStrategiesIndex($this->copingStrategiesIndex);
        $household->setDebtLevel($this->debtLevel);
        $household->setFoodConsumptionScore($this->foodConsumptionScore);
        $household->setIncomeLevel($this->incomeLevel);
        $household->setIso3($this->countryIso3);
        $household->setNotes($this->notes);
        $household->setLatitude('');
        $household->setLongitude('');

        $address = new ResidenceAddressInputType();
        $address->setNumber($this->addressStreet);
        $address->setPostcode($this->addressPostcode);
        $address->setNumber($this->addressNumber);
        $address->setLocationId(1); // FIXME
        $household->setResidenceAddress($address);

        $head = $this->buildBeneficiaryInputType();
        $head->setIsHead(true);

        $household->addBeneficiary($head);
    }

    public function buildBeneficiaryInputType(): BeneficiaryInputType
    {
        $beneficiary = new BeneficiaryInputType();
        $beneficiary->setDateOfBirth($this->dateOfBirth);
        $beneficiary->setEnFamilyName($this->englishFamilyName);
        $beneficiary->setEnGivenName($this->englishGivenName);
        $beneficiary->setLocalFamilyName($this->localFamilyName);
        $beneficiary->setLocalGivenName($this->localGivenName);
        $beneficiary->setGender($this->gender == 'Male' ? 'M' : 'F');
        $beneficiary->setResidencyStatus($this->residencyStatus);
        return $beneficiary;
    }


}
