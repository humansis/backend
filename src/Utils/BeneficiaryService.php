<?php

namespace Utils;

use DateTime;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\Beneficiary;
use Entity\NationalId;
use Entity\Phone;
use Entity\Profile;
use Entity\Referral;
use Entity\VulnerabilityCriterion;
use Exception\ExportNoDataException;
use InvalidArgumentException;
use Repository\BeneficiaryRepository;
use Repository\HouseholdRepository;
use Repository\VulnerabilityCriterionRepository;
use Controller\ExportController;
use Doctrine\ORM\EntityManagerInterface;
use InputType\BenefciaryPatchInputType;
use InputType\Beneficiary\BeneficiaryInputType;
use InputType\Beneficiary\NationalIdCardInputType;
use InputType\Beneficiary\PhoneInputType;
use InputType\HouseholdFilterInputType;
use InputType\HouseholdOrderInputType;
use Request\Pagination;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BeneficiaryService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly BeneficiaryRepository $beneficiaryRepository, private readonly HouseholdRepository $householdRepository, private readonly VulnerabilityCriterionRepository $vulnerabilityCriterionRepository)
    {
    }

    public function createPhone(PhoneInputType $inputType): Phone
    {
        $phone = new Phone();

        $number = $inputType->getNumber();
        if (preg_match('/^0/', $number)) {
            $number = substr($number, 1);
        }

        $phone->setType($inputType->getType());
        $phone->setPrefix($inputType->getPrefix());
        $phone->setNumber($number);
        $phone->setProxy($inputType->getProxy());

        $this->em->persist($phone);

        return $phone;
    }

    public function createNationalId(NationalIdCardInputType $inputType): NationalId
    {
        $nationalId = NationalId::fromNationalIdInputType($inputType);

        $this->em->persist($nationalId);

        return $nationalId;
    }

    public function update(Beneficiary $beneficiary, BeneficiaryInputType $inputType): Beneficiary
    {
        $beneficiaryPerson = $beneficiary->getPerson();

        $beneficiaryPerson->setGender($inputType->getGender())
            ->setDateOfBirth($inputType->getDateOfBirth())
            ->setEnGivenName($inputType->getEnGivenName())
            ->setEnFamilyName($inputType->getEnFamilyName())
            ->setEnParentsName($inputType->getEnParentsName())
            ->setLocalGivenName($inputType->getLocalGivenName())
            ->setLocalFamilyName($inputType->getLocalFamilyName())
            ->setLocalParentsName($inputType->getLocalParentsName());

        $beneficiary->setHead($inputType->isHead())
            ->setResidencyStatus($inputType->getResidencyStatus())
            ->setUpdatedOn(new DateTime()); //TODO use doctrine lifecycle callback

        //phones
        foreach ($beneficiaryPerson->getPhones() as $oldPhone) {
            $this->em->remove($oldPhone);
        }
        $beneficiaryPerson->getPhones()->clear();

        foreach ($inputType->getPhones() as $phoneInputType) {
            $phone = $this->createPhone($phoneInputType);
            $phone->setPerson($beneficiaryPerson);
            $beneficiaryPerson->addPhone($phone);
        }

        //national ids
        foreach ($beneficiaryPerson->getNationalIds() as $nationalId) {
            $this->em->remove($nationalId);
        }
        $beneficiaryPerson->getNationalIds()->clear();

        foreach ($inputType->getNationalIdCards() as $nationalIdInputType) {
            $nationalId = $this->createNationalId($nationalIdInputType);
            $nationalId->setPerson($beneficiaryPerson);
            $beneficiaryPerson->addNationalId($nationalId);
        }

        //vulnerability criteria
        $beneficiary->getVulnerabilityCriteria()->clear();
        foreach ($inputType->getVulnerabilityCriteria() as $vulnerabilityCriterionName) {
            /** @var VulnerabilityCriterion $criterion */
            $criterion = $this->vulnerabilityCriterionRepository->findOneBy(
                ['fieldString' => $vulnerabilityCriterionName]
            );
            $beneficiary->addVulnerabilityCriterion($criterion);
        }

        //referral
        $referral = $beneficiaryPerson->getReferral();

        if (is_null($referral)) {
            if (!is_null($inputType->getReferralType())) {
                $referral = new Referral();
                $this->em->persist($referral);
            }
        } else {
            if (!is_null($inputType->getReferralType())) {
                $referral->setType($inputType->getReferralType());
                $referral->setComment($inputType->getReferralComment());
            } else {
                $this->em->remove($referral);
            }
        }

        $this->em->persist($beneficiary);

        return $beneficiary;
    }

    public function create(BeneficiaryInputType $inputType): Beneficiary
    {
        $beneficiary = new Beneficiary();
        $beneficiary
            ->setHead($inputType->isHead())
            ->setResidencyStatus($inputType->getResidencyStatus())
            ->setUpdatedOn(new DateTime());

        foreach ($inputType->getVulnerabilityCriteria() as $id => $vulnerability_criterion) {
            $beneficiary->addVulnerabilityCriterion($this->getVulnerabilityCriterion($vulnerability_criterion));
        }

        $person = $beneficiary->getPerson();
        $person->setGender($inputType->getGender())
            ->setDateOfBirth($inputType->getDateOfBirth())
            ->setEnFamilyName($inputType->getEnFamilyName())
            ->setEnGivenName($inputType->getEnGivenName())
            ->setEnParentsName($inputType->getEnParentsName())
            ->setLocalFamilyName($inputType->getLocalFamilyName())
            ->setLocalGivenName($inputType->getLocalGivenName())
            ->setLocalParentsName($inputType->getLocalParentsName())
            ->setUpdatedOn(new DateTime())
            ->setProfile(new Profile());
        $person->getProfile()->setPhoto('');

        foreach ($inputType->getPhones() as $phoneInputType) {
            $phone = $this->createPhone($phoneInputType);
            $person->addPhone($phone);
            $phone->setPerson($person);
            $this->em->persist($phone);
        }

        foreach ($inputType->getNationalIdCards() as $nationalIdArray) {
            $nationalId = $this->createNationalId($nationalIdArray);
            $person->addNationalId($nationalId);
            $nationalId->setPerson($person);
            $this->em->persist($nationalId);
        }

        // $this->createProfile($person, $inputType->getProfile()); TODO

        $previousReferral = $person->getReferral();
        if ($previousReferral) {
            $this->em->remove($previousReferral);
        }
        if ($inputType->hasReferral()) {
            $referral = new Referral();
            $referral->setType($inputType->getReferralType())
                ->setComment($inputType->getReferralComment());
            $person->setReferral($referral);
            $this->em->persist($referral);
        }

        $this->em->persist($beneficiary);

        return $beneficiary;
    }

    /**
     * @param $vulnerabilityCriterionId
     * @throws \Exception
     */
    public function getVulnerabilityCriterion($vulnerabilityCriterionId): VulnerabilityCriterion
    {
        /** @var VulnerabilityCriterion $vulnerabilityCriterion */
        $vulnerabilityCriterion = $this->vulnerabilityCriterionRepository->findOneBy(
            ['fieldString' => $vulnerabilityCriterionId]
        );

        if (!$vulnerabilityCriterion) {
            $vulnerabilityCriterion = $this->vulnerabilityCriterionRepository->find($vulnerabilityCriterionId);
        }

        if (!$vulnerabilityCriterion instanceof VulnerabilityCriterion) {
            throw new \Exception("Vulnerability $vulnerabilityCriterionId doesn't exist.");
        }

        return $vulnerabilityCriterion;
    }

    public function remove(Beneficiary $beneficiary): void
    {
        $beneficiary->setArchived();
    }

    public function countAll(string $iso3): int
    {
        return (int) $this->beneficiaryRepository->countAllInCountry($iso3);
    }

    public function countAllServed(string $iso3): int
    {
        return (int) $this->beneficiaryRepository->countServedInCountry($iso3);
    }

    /**
     * @param Paginator $households
     * @return array
     */
    public function getHouseholdBeneficiaries(
        Paginator $households
    ): array {
        $exportableTable = [];
        foreach ($households as $household) {
            foreach ($household->getBeneficiaries() as $beneficiary) {
                array_push($exportableTable, $beneficiary);
            }
        }
        return $exportableTable;
    }

    /**
     * @param Paginator $households
     * @return int
     */
    public function countBeneficiaries(
        Paginator $households
    ): int {
        $sum = 0;
        foreach ($households as $household) {
            $sum += $household->getBeneficiaries()->count();
        }
        return $sum;
    }


    public function patch(Beneficiary $beneficiary, BenefciaryPatchInputType $inputType): Beneficiary
    {
        if (
            ($inputType->getReferralType() || $inputType->getReferralComment()) && null == $beneficiary->getPerson(
            )->getReferral()
        ) {
            $beneficiary->getPerson()->setReferral(new Referral());
        }

        if ($inputType->getReferralComment()) {
            $beneficiary->getPerson()->getReferral()->setComment($inputType->getReferralComment());
        }

        if ($inputType->getReferralType()) {
            $beneficiary->getPerson()->getReferral()->setType($inputType->getReferralType());
        }

        $this->em->persist($beneficiary->getPerson()->getReferral());
        $this->em->persist($beneficiary->getPerson());
        $this->em->flush();

        return $beneficiary;
    }
}
