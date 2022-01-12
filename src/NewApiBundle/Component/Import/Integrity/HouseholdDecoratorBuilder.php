<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Integrity;

use CommonBundle\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Import\Utils\ImportDateConverter;
use NewApiBundle\Entity\ImportQueue;
use NewApiBundle\InputType\Beneficiary\Address\ResidenceAddressInputType;
use NewApiBundle\InputType\Beneficiary\CountrySpecificsAnswerInputType;
use NewApiBundle\InputType\HouseholdCreateInputType;
use NewApiBundle\InputType\HouseholdUpdateInputType;
use NewApiBundle\InputType\Beneficiary\BeneficiaryInputType;

/* TODO many unused parameters in HouseholdHead / HouseholdMember:
    $campName
    $tentNumber
*/
class HouseholdDecoratorBuilder
{
    /** @var string */
    private $countryIso3;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ImportLine */
    private $householdLine;

    /** @var ImportLine[] */
    private $importLines;

    /**
     * @param string                 $countryIso3
     * @param EntityManagerInterface $entityManager
     * @param ImportQueue            $importQueue
     */
    public function __construct(string $countryIso3, EntityManagerInterface $entityManager, ImportQueue $importQueue)
    {
        $this->countryIso3 = $countryIso3;
        $this->entityManager = $entityManager;
        $this->householdLine = new ImportLine($importQueue->getHeadContent(), $countryIso3, $entityManager);
        foreach ($importQueue->getContent() as $lineData) {
            $this->importLines[] = new ImportLine($lineData, $countryIso3, $entityManager);
        }
    }

    public function buildHouseholdInputType(): ?HouseholdCreateInputType
    {
        $household = new HouseholdCreateInputType();
        $this->fillHousehold($household);
        return $household;
    }

    public function buildHouseholdUpdateType(): ?HouseholdUpdateInputType
    {
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
        $household->setCopingStrategiesIndex($this->householdLine->copingStrategiesIndex);
        $household->setDebtLevel($this->householdLine->debtLevel);
        $household->setFoodConsumptionScore($this->householdLine->foodConsumptionScore);
        $household->setIncomeLevel($this->householdLine->incomeLevel);
        $household->setIso3($this->countryIso3);
        $household->setNotes($this->householdLine->notes);
        $household->setLatitude($this->householdLine->latitude);
        $household->setLongitude($this->householdLine->longitude);
        $household->setLivelihood($this->householdLine->livelihood);
        $household->setEnumeratorName($this->householdLine->enumeratorName);
        $household->setShelterStatus($this->householdLine->shelterStatus);
        $household->setSupportDateReceived(ImportDateConverter::toIso(ImportDateConverter::toDatetime($this->householdLine->supportDateReceived)));
        $household->setSupportReceivedTypes($this->householdLine->supportReceivedTypes);
        $household->setAssets($this->householdLine->assets);

        foreach ($this->householdLine->countrySpecifics as $countrySpecificId => $answer) {
            $specificAnswer = new CountrySpecificsAnswerInputType();
            $specificAnswer->setCountrySpecificId($countrySpecificId);
            $specificAnswer->setAnswer($answer);
            $household->addCountrySpecificAnswer($specificAnswer);
        }

        $locationRepository = $this->entityManager->getRepository(Location::class);
        $locationByAdms = $locationRepository->getByNames(
            $this->countryIso3,
            $this->householdLine->adm1,
            $this->householdLine->adm2,
            $this->householdLine->adm3,
            $this->householdLine->adm4
        );
        if (null !== $locationByAdms) {
            $address = new ResidenceAddressInputType();
            $address->setNumber($this->householdLine->addressStreet);
            $address->setPostcode($this->householdLine->addressPostcode);
            $address->setNumber($this->householdLine->addressNumber);
            $address->setLocationId($locationByAdms->getId());
            $household->setResidenceAddress($address);
        }

        foreach ($this->importLines as $importLine) {
            $builder = new BeneficiaryDecoratorBuilder($importLine);
            $beneficiary = $builder->buildBeneficiaryInputType();
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
        foreach ($this->buildMembersByAgeAndGender('F', 1, $this->householdLine->f0 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('M', 1, $this->householdLine->m0 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('F', 3, $this->householdLine->f2 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('M', 3, $this->householdLine->m2 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('F', 7, $this->householdLine->f6 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('M', 7, $this->householdLine->m6 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('F', 19, $this->householdLine->f18 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('M', 19, $this->householdLine->m18 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('F', 66, $this->householdLine->f60 ?? 0) as $bnf) { yield $bnf; }
        foreach ($this->buildMembersByAgeAndGender('M', 66, $this->householdLine->m60 ?? 0) as $bnf) { yield $bnf; }
    }

    private function buildMembersByAgeAndGender(string $gender, int $age, int $count): iterable
    {
        if (0 === $count) return;
        $today = new \DateTime();

        for ($i=0; $i<$count; $i++) {
            $beneficiary = new BeneficiaryInputType();
            $beneficiary->setDateOfBirth($today->modify("-$age year")->format('d-m-Y'));
            $beneficiary->setGender($gender);
            $beneficiary->setIsHead(false);
            yield $beneficiary;
        }
    }

}
