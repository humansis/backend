<?php

namespace Utils;

use DateTime;
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
    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * @var ExportService
     */
    private $exportService;

    /**
     * @var BeneficiaryRepository
     */
    private $beneficiaryRepository;

    /**
     * @var HouseholdRepository
     */
    private $householdRepository;

    /**
     * @var VulnerabilityCriterionRepository
     */
    private $vulnerabilityCriterionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ExportService $exportService,
        BeneficiaryRepository $beneficiaryRepository,
        HouseholdRepository $householdRepository,
        VulnerabilityCriterionRepository $vulnerabilityCriterionRepository
    ) {
        $this->em = $entityManager;
        $this->exportService = $exportService;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->householdRepository = $householdRepository;
        $this->vulnerabilityCriterionRepository = $vulnerabilityCriterionRepository;
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

    /**
     * @param Beneficiary $beneficiary
     * @param BeneficiaryInputType $inputType
     *
     * @return Beneficiary
     */
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
     * @return VulnerabilityCriterion
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

    /**
     * @param Beneficiary $beneficiary
     */
    public function remove(Beneficiary $beneficiary): void
    {
        $beneficiary->setArchived();
    }

    /**
     * @param string $iso3
     * @return int
     */
    public function countAll(string $iso3): int
    {
        return (int) $this->beneficiaryRepository->countAllInCountry($iso3);
    }

    /**
     * @param string $iso3
     * @return int
     */
    public function countAllServed(string $iso3): int
    {
        return (int) $this->beneficiaryRepository->countServedInCountry($iso3);
    }

    /**
     * @param string $type
     * @param string $countryIso3
     * @param        $filters
     * @param        $ids
     *
     * @return string
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    public function exportToCsvDeprecated(string $type, string $countryIso3, $filters, $ids): string
    {
        $households = null;
        $exportableTable = [];
        if ($ids) {
            $households = $this->householdRepository->getAllByIds($ids);
        } else {
            if ($filters) {
                // $households = $this->householdService->getAll($countryIso3, $filters)[1];
                // This should be not used this way
                throw new \Exception('Using deprecated method.');
            } else {
                $exportableTable = $this->beneficiaryRepository->getAllInCountry($countryIso3);
            }
        }

        if ('csv' !== $type && count($households) > ExportController::EXPORT_LIMIT) {
            $count = count($households);
            throw new BadRequestHttpException(
                "Too much households ($count) to export. Limit is " . ExportController::EXPORT_LIMIT
            );
        }
        if ('csv' === $type && count($households) > ExportController::EXPORT_LIMIT_CSV) {
            $count = count($households);
            throw new BadRequestHttpException(
                "Too much households ($count) to export. Limit for CSV is " . ExportController::EXPORT_LIMIT_CSV
            );
        }

        if ($households) {
            foreach ($households as $household) {
                foreach ($household->getBeneficiaries() as $beneficiary) {
                    array_push($exportableTable, $beneficiary);
                }
            }
        }

        if ('csv' !== $type && count($exportableTable) > ExportController::EXPORT_LIMIT) {
            $BNFcount = count($exportableTable);
            $HHcount = count($households);
            throw new BadRequestHttpException(
                "Too much beneficiaries ($BNFcount) in households ($HHcount) to export. Limit is " . ExportController::EXPORT_LIMIT
            );
        }
        if ('csv' === $type && count($exportableTable) > ExportController::EXPORT_LIMIT_CSV) {
            $BNFcount = count($exportableTable);
            $HHcount = count($households);
            throw new BadRequestHttpException(
                "Too much beneficiaries ($BNFcount) in households ($HHcount) to export. Limit for CSV is " . ExportController::EXPORT_LIMIT_CSV
            );
        }

        try {
            return $this->exportService->export($exportableTable, 'beneficiaryhousehoulds', $type);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException("No data to export.");
        }
    }

    /**
     * @param string $type
     * @param string $countryIso3
     * @param HouseholdFilterInputType $filter
     * @param Pagination $pagination
     * @param HouseholdOrderInputType $order
     *
     * @return string
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportToCsv(
        string $type,
        string $countryIso3,
        HouseholdFilterInputType $filter,
        Pagination $pagination,
        HouseholdOrderInputType $order
    ): string {
        $households = $this->householdRepository->findByParams($countryIso3, $filter, $order, $pagination);

        if ('csv' !== $type && count($households) > ExportController::EXPORT_LIMIT) {
            $count = count($households);
            throw new BadRequestHttpException(
                "Too much households ($count) to export. Limit is " . ExportController::EXPORT_LIMIT
            );
        }
        if ('csv' === $type && count($households) > ExportController::EXPORT_LIMIT_CSV) {
            $count = count($households);
            throw new BadRequestHttpException(
                "Too much households ($count) to export. Limit for CSV is " . ExportController::EXPORT_LIMIT_CSV
            );
        }

        $exportableTable = [];
        if ($households) {
            foreach ($households as $household) {
                foreach ($household->getBeneficiaries() as $beneficiary) {
                    array_push($exportableTable, $beneficiary);
                }
            }
        }

        if ('csv' !== $type && count($exportableTable) > ExportController::EXPORT_LIMIT) {
            $BNFcount = count($exportableTable);
            $HHcount = count($households);
            throw new BadRequestHttpException(
                "Too much beneficiaries ($BNFcount) in households ($HHcount) to export. Limit is " . ExportController::EXPORT_LIMIT
            );
        }
        if ('csv' === $type && count($exportableTable) > ExportController::EXPORT_LIMIT_CSV) {
            $BNFcount = count($exportableTable);
            $HHcount = count($households);
            throw new BadRequestHttpException(
                "Too much beneficiaries ($BNFcount) in households ($HHcount) to export. Limit for CSV is " . ExportController::EXPORT_LIMIT_CSV
            );
        }

        try {
            return $this->exportService->export($exportableTable, 'beneficiaryhousehoulds', $type);
        } catch (ExportNoDataException $e) {
            throw new BadRequestHttpException("No data to export.");
        }
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