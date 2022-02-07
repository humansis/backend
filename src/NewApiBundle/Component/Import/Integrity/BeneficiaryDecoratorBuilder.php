<?php declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use CommonBundle\Entity\Location;
use NewApiBundle\Component\Import\Utils\ImportDateConverter;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;

class BeneficiaryDecoratorBuilder
{
    /** @var ImportLine */
    private $beneficiaryLine;

    /**
     * @param ImportLine $beneficiaryLine
     */
    public function __construct(ImportLine $beneficiaryLine)
    {
        $this->beneficiaryLine = $beneficiaryLine;
    }

    public function buildBeneficiaryInputType(): BeneficiaryInputType
    {
        $beneficiary = new BeneficiaryInputType();
        $beneficiary->setDateOfBirth(ImportDateConverter::toIso($this->beneficiaryLine->getDateOfBirth()));
        $beneficiary->setLocalFamilyName($this->beneficiaryLine->localFamilyName);
        $beneficiary->setLocalGivenName($this->beneficiaryLine->localGivenName);
        $beneficiary->setLocalParentsName($this->beneficiaryLine->localParentsName);
        $beneficiary->setEnFamilyName($this->beneficiaryLine->englishFamilyName);
        $beneficiary->setEnGivenName($this->beneficiaryLine->englishGivenName);
        $beneficiary->setEnParentsName($this->beneficiaryLine->englishParentsName);
        $beneficiary->setGender($this->beneficiaryLine->gender);
        $beneficiary->setResidencyStatus($this->beneficiaryLine->residencyStatus);
        $beneficiary->setIsHead($this->beneficiaryLine->head);

        if (!is_null($this->beneficiaryLine->idType)) { //TODO check, that id card is filled completely
            $nationalId = new NationalIdCardInputType();
            $nationalId->setType($this->beneficiaryLine->idType);
            $nationalId->setNumber((string) $this->beneficiaryLine->idNumber);
            $beneficiary->addNationalIdCard($nationalId);
        }

        if (!is_null($this->beneficiaryLine->numberPhone1)) { //TODO check, that phone is filled completely in import
            $phone1 = new PhoneInputType();
            $phone1->setNumber((string) $this->beneficiaryLine->numberPhone1);
            $phone1->setType($this->beneficiaryLine->typePhone1);
            $phone1->setPrefix((string) $this->beneficiaryLine->prefixPhone1);
            $phone1->setProxy($this->beneficiaryLine->proxyPhone1);
            $beneficiary->addPhone($phone1);
        }

        if (!is_null($this->beneficiaryLine->numberPhone2)) { //TODO check, that phone is filled completely in import
            $phone2 = new PhoneInputType();
            $phone2->setNumber((string) $this->beneficiaryLine->numberPhone2);
            $phone2->setType($this->beneficiaryLine->typePhone2);
            $phone2->setPrefix((string) $this->beneficiaryLine->prefixPhone2);
            $phone2->setProxy($this->beneficiaryLine->proxyPhone2);
            $beneficiary->addPhone($phone2);
        }

        return $beneficiary;
    }
}
