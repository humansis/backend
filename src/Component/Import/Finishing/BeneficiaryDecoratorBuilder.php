<?php

declare(strict_types=1);

namespace Component\Import\Finishing;

use Component\Import\Utils\ImportDateConverter;
use Enum\VulnerabilityCriteria;
use InputType\Beneficiary\BeneficiaryInputType;
use InputType\Beneficiary\NationalIdCardInputType;
use InputType\Beneficiary\PhoneInputType;
use Component\Import;
use InputType\Helper\EnumsBuilder;

class BeneficiaryDecoratorBuilder
{
    public function buildBeneficiaryInputType(Import\Integrity\ImportLine $beneficiaryLine): BeneficiaryInputType
    {
        $beneficiary = new BeneficiaryInputType();
        $beneficiary->setDateOfBirth(ImportDateConverter::toIso($beneficiaryLine->getDateOfBirth()));
        $beneficiary->setLocalFamilyName($beneficiaryLine->localFamilyName);
        $beneficiary->setLocalGivenName($beneficiaryLine->localGivenName);
        $beneficiary->setLocalParentsName($beneficiaryLine->localParentsName);
        $beneficiary->setEnFamilyName($beneficiaryLine->englishFamilyName);
        $beneficiary->setEnGivenName($beneficiaryLine->englishGivenName);
        $beneficiary->setEnParentsName($beneficiaryLine->englishParentsName);
        $beneficiary->setGender($beneficiaryLine->gender);
        $beneficiary->setResidencyStatus($beneficiaryLine->residencyStatus);
        $beneficiary->setIsHead($beneficiaryLine->head);

        if (is_string($beneficiaryLine->vulnerabilityCriteria)) {
            $enumBuilder = new EnumsBuilder(VulnerabilityCriteria::class);
            $enumBuilder->setNullToEmptyArrayTransformation();
            $importedVulnerabilities = $enumBuilder->buildInputValues($beneficiaryLine->vulnerabilityCriteria);
            $beneficiary->setVulnerabilityCriteria($importedVulnerabilities);
        }

        if (!is_null($beneficiaryLine->idType)) {
            $beneficiary->addNationalIdCard($this->buildIdentityType($beneficiaryLine->idType, (string) $beneficiaryLine->idNumber));
        }

        if (!is_null($beneficiaryLine->numberPhone1)) { //TODO check, that phone is filled completely in import
            $phone1 = new PhoneInputType();
            $phone1->setNumber((string) $beneficiaryLine->numberPhone1);
            $phone1->setType($beneficiaryLine->typePhone1);
            $phone1->setPrefix((string) $beneficiaryLine->prefixPhone1);
            $phone1->setProxy($beneficiaryLine->proxyPhone1);
            $beneficiary->addPhone($phone1);
        }

        if (!is_null($beneficiaryLine->numberPhone2)) { //TODO check, that phone is filled completely in import
            $phone2 = new PhoneInputType();
            $phone2->setNumber((string) $beneficiaryLine->numberPhone2);
            $phone2->setType($beneficiaryLine->typePhone2);
            $phone2->setPrefix((string) $beneficiaryLine->prefixPhone2);
            $phone2->setProxy($beneficiaryLine->proxyPhone2);
            $beneficiary->addPhone($phone2);
        }

        return $beneficiary;
    }

    /**
     * @param Import\Integrity\ImportLine $beneficiaryLine
     *
     * @return BeneficiaryInputType
     */
    public function buildBeneficiaryIdentityInputType(Import\Integrity\ImportLine $beneficiaryLine): BeneficiaryInputType
    {
        $beneficiary = new BeneficiaryInputType();
        if (!is_null($beneficiaryLine->idType)) {
            $beneficiary->addNationalIdCard($this->buildIdentityType($beneficiaryLine->idType, (string) $beneficiaryLine->idNumber));
        }

        return $beneficiary;
    }

    /**
     * @param string $idType
     * @param string $idNumber
     *
     * @return NationalIdCardInputType
     */
    private function buildIdentityType(string $idType, string $idNumber): NationalIdCardInputType
    {
        $nationalId = new NationalIdCardInputType();
        $nationalId->setType($idType);
        $nationalId->setNumber($idNumber);

        return $nationalId;
    }
}
