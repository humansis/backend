<?php

namespace Utils;

use Entity\AbstractBeneficiary;
use Entity\User;
use Enum\ModalityType;
use Exception\CsvParserException;
use Exception\ExportNoDataException;
use InputType\Assistance\UpdateAssistanceInputType;
use Pagination\Paginator;
use DateTime;
use DateTimeInterface;
use DTO\VulnerabilityScore;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Enum\AssistanceTargetType;
use Psr\Cache\InvalidArgumentException;
use Repository\Assistance\ReliefPackageRepository;
use Repository\AssistanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Exception;
use Component\Assistance\AssistanceFactory;
use Component\Assistance\SelectionCriteriaFactory;
use Entity\Assistance\ReliefPackage;
use Enum\CacheTarget;
use Enum\PersonGender;
use InputType\AssistanceCreateInputType;
use Repository\BeneficiaryRepository;
use Repository\ProjectRepository;
use Request\Pagination;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Entity\Voucher;
use Component\Assistance\Domain\Assistance as AssistanceDomain;

/**
 * Class AssistanceService
 *
 * @package Utils
 */
class AssistanceService
{
    /**
     * AssistanceService constructor.
     *
     * @param FilesystemAdapter $cache
     */
    public function __construct(private readonly EntityManagerInterface $em, private readonly CriteriaAssistanceService $criteriaAssistanceService, private readonly Environment $twig, private readonly CacheInterface $cache, private readonly AssistanceFactory $assistanceFactory, private readonly AssistanceRepository $assistanceRepository, private readonly SelectionCriteriaFactory $selectionCriteriaFactory, private readonly TranslatorInterface $translator, private readonly ProjectRepository $projectRepository, private readonly BeneficiaryRepository $beneficiaryRepository, private readonly ReliefPackageRepository $reliefPackageRepository, private readonly ExportService $exportService, private readonly PdfService $pdfService)
    {
    }

    public function update(
        Assistance $assistanceRoot,
        UpdateAssistanceInputType $updateAssistanceInputType,
        User $user
    ): AssistanceDomain {
        $assistance = $this->assistanceFactory->hydrate($assistanceRoot);
        if ($updateAssistanceInputType->hasValidated()) {
            if ($updateAssistanceInputType->getValidated()) {
                $assistance->validate($user);
            } else {
                $assistance->unvalidate();
            }
        }
        if ($updateAssistanceInputType->isCompleted()) {
            $assistance->complete();
        }
        if ($updateAssistanceInputType->hasDateDistribution()) {
            $this->updateDateDistribution($assistanceRoot, $updateAssistanceInputType->getDateDistribution());
        }
        if ($updateAssistanceInputType->hasDateExpiration()) {
            $this->updateDateExpiration($assistanceRoot, $updateAssistanceInputType->getDateExpiration());
        }
        if ($updateAssistanceInputType->hasRound()) {
            $this->updateRound($assistanceRoot, $updateAssistanceInputType->getRound());
        }
        if ($updateAssistanceInputType->hasNote()) {
            $this->updateNote($assistanceRoot, $updateAssistanceInputType->getNote());
        }

        $this->assistanceRepository->save($assistance);

        return $assistance;
    }

    /**
     *
     * @deprecated use Assistance::validate instead
     */
    public function validateDistribution(Assistance $assistanceRoot, User $user)
    {
        $assistance = $this->assistanceFactory->hydrate($assistanceRoot);
        $assistance->validate($user);
        $this->assistanceRepository->save($assistance);
    }

    // TODO: presunout do ABNF
    public function findByCriteria(AssistanceCreateInputType $inputType, Pagination $pagination): Paginator
    {
        $project = $this->projectRepository->find($inputType->getProjectId());
        if (!$project) {
            throw new EntityNotFoundException('Project #' . $inputType->getProjectId() . ' does not exists.');
        }

        $selectionGroups = $this->selectionCriteriaFactory->createGroups($inputType->getSelectionCriteria());
        $result = $this->criteriaAssistanceService->load(
            $selectionGroups,
            $project,
            $inputType->getTarget(),
            $inputType->getSector(),
            $inputType->getSubsector(),
            $inputType->getThreshold(),
            false,
            $inputType->getScoringBlueprintId()
        );
        $ids = array_keys($result['finalArray']);
        $count = count($ids);

        $ids = array_slice($ids, $pagination->getOffset(), $pagination->getSize());

        $beneficiaries = $this->em->getRepository(AbstractBeneficiary::class)->findBy(['id' => $ids]);

        return new Paginator($beneficiaries, $count);
    }

    /**
     *
     * @return Paginator|VulnerabilityScore[]
     * @throws EntityNotFoundException
     * @throws CsvParserException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     */
    public function findVulnerabilityScores(AssistanceCreateInputType $inputType, Pagination $pagination): \Pagination\Paginator|array
    {
        $project = $this->projectRepository->find($inputType->getProjectId());
        if (!$project) {
            throw new EntityNotFoundException('Project #' . $inputType->getProjectId() . ' does not exists.');
        }

        $selectionGroups = $this->selectionCriteriaFactory->createGroups($inputType->getSelectionCriteria());
        $result = $this->criteriaAssistanceService->load(
            $selectionGroups,
            $project,
            $inputType->getTarget(),
            $inputType->getSector(),
            $inputType->getSubsector(),
            $inputType->getThreshold(),
            false,
            $inputType->getScoringBlueprintId()
        );
        $ids = array_keys($result['finalArray']);
        $count = count($ids);

        $ids = array_slice($ids, $pagination->getOffset(), $pagination->getSize());

        $list = [];
        foreach ($ids as $id) {
            $beneficiary = $this->em->getRepository(AbstractBeneficiary::class)->find($id);
            $list[] = new VulnerabilityScore($beneficiary, $result['finalArray'][$id]);
        }

        return new Paginator($list, $count);
    }

    public function updateDateDistribution(Assistance $assistance, DateTimeInterface $date): void
    {
        $assistance
            ->setDateDistribution($date)
            ->setName(AssistanceFactory::generateName($assistance))
            ->setUpdatedOn(new DateTime());
    }

    public function updateDateExpiration(Assistance $assistance, ?DateTimeInterface $date): void
    {
        $assistance->setDateExpiration($date);
        $assistance->setUpdatedOn(new DateTime());
    }

    public function updateNote(Assistance $assistance, ?string $note): void
    {
        $assistance->setNote($note);
        $assistance->setUpdatedOn(new DateTime());
    }

    public function updateRound(Assistance $assistance, ?int $round): void
    {
        $assistance->setRound($round);
        $assistance->setName(AssistanceFactory::generateName($assistance));
        $assistance->setUpdatedOn(new DateTime());
    }

    /**
     *
     * @throws ExportNoDataException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportToCsv(int $projectId, string $type): string
    {
        $exportableTable = $this->assistanceRepository->findBy(['project' => $projectId]);

        return $this->exportService->export($exportableTable, 'distributions', $type);
    }

    /**
     *
     * @throws ExportNoDataException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportToOfficialCsv(int $projectId, string $type): string
    {
        $project = $this->projectRepository->find($projectId);

        if (!$project) {
            throw new NotFoundHttpException("Project #$projectId missing");
        }

        $assistances = $this->assistanceRepository->findBy(['project' => $projectId, 'archived' => 0]);
        $exportableTable = [];

        $donors = implode(
            ', ',
            array_map(fn($donor) => $donor->getShortname(), $project->getDonors()->toArray())
        );

        foreach ($assistances as $assistance) {
            $idps = $this->beneficiaryRepository->countByResidencyStatus($assistance, "IDP");
            $residents = $this->beneficiaryRepository->countByResidencyStatus($assistance, "resident");
            $maleHHH = $this->beneficiaryRepository->countHouseholdHeadsByGender($assistance, PersonGender::MALE);
            $femaleHHH = $this->beneficiaryRepository->countHouseholdHeadsByGender($assistance, PersonGender::FEMALE);
            $maleChildrenUnder23month = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                1,
                0,
                2,
                $assistance->getDateDistribution()
            );
            $femaleChildrenUnder23month = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                0,
                0,
                2,
                $assistance->getDateDistribution()
            );
            $maleChildrenUnder5years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                1,
                2,
                6,
                $assistance->getDateDistribution()
            );
            $femaleChildrenUnder5years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                0,
                2,
                6,
                $assistance->getDateDistribution()
            );
            $maleUnder17years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                1,
                6,
                18,
                $assistance->getDateDistribution()
            );
            $femaleUnder17years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                0,
                6,
                18,
                $assistance->getDateDistribution()
            );
            $maleUnder59years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                1,
                18,
                60,
                $assistance->getDateDistribution()
            );
            $femaleUnder59years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                0,
                18,
                60,
                $assistance->getDateDistribution()
            );
            $maleOver60years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                1,
                60,
                200,
                $assistance->getDateDistribution()
            );
            $femaleOver60years = $this->beneficiaryRepository->countByAgeAndByGender(
                $assistance,
                0,
                60,
                200,
                $assistance->getDateDistribution()
            );
            $maleTotal = $maleChildrenUnder23month + $maleChildrenUnder5years + $maleUnder17years + $maleUnder59years + $maleOver60years;
            $femaleTotal = $femaleChildrenUnder23month + $femaleChildrenUnder5years + $femaleUnder17years + $femaleUnder59years + $femaleOver60years;
            $noFamilies = $assistance->getTargetType(
            ) === AssistanceTargetType::INDIVIDUAL ? ($maleTotal + $femaleTotal) : ($maleHHH + $femaleHHH);
            $familySize = $assistance->getTargetType(
            ) === AssistanceTargetType::HOUSEHOLD && $noFamilies ? ($maleTotal + $femaleTotal) / $noFamilies : null;
            $modalityType = $assistance->getCommodities()[0]->getModalityType();
            $beneficiaryServed = $this->assistanceRepository->getNoServed($assistance->getId(), $modalityType);

            $commodityNames = implode(
                ', ',
                array_map(
                    fn($commodity) => $commodity->getModalityType(),
                    $assistance->getCommodities()->toArray()
                )
            );
            $commodityUnit = implode(
                ', ',
                array_map(
                    fn($commodity) => $commodity->getUnit(),
                    $assistance->getCommodities()->toArray()
                )
            );
            $numberOfUnits = implode(
                ', ',
                array_map(
                    fn($commodity) => $commodity->getValue(),
                    $assistance->getCommodities()->toArray()
                )
            );

            $totalAmount = implode(
                ', ',
                array_map(
                    fn($commodity) => $commodity->getValue() * $noFamilies . ' ' . $commodity->getUnit(),
                    $assistance->getCommodities()->toArray()
                )
            );

            $row = [
                $this->translator->trans("Navi/Elo number") => $assistance->getProject()->getInternalId() ?? " ",
                $this->translator->trans("DISTR. NO.") => $assistance->getId(),
                $this->translator->trans("Distributed by") => " ",
                $this->translator->trans("Round") => ($assistance->getRound() ?? $this->translator->trans(
                    "N/A"
                )),
                $this->translator->trans("Donor") => $donors,
                $this->translator->trans("Starting Date") => $assistance->getDateDistribution(),
                $this->translator->trans("Ending Date") => $assistance->getCompleted() ? $assistance->getUpdatedOn(
                ) : " - ",
                $this->translator->trans("Governorate") => $assistance->getLocation()->getAdm1Name(),
                $this->translator->trans("District") => $assistance->getLocation()->getAdm2Name(),
                $this->translator->trans("Sub-District") => $assistance->getLocation()->getAdm3Name(),
                $this->translator->trans("Town, Village") => $assistance->getLocation()->getAdm4Name(),
                $this->translator->trans("Location = School/Camp") => " ",
                $this->translator->trans("Neighbourhood (Camp Name)") => " ",
                $this->translator->trans("Latitude") => " ",
                $this->translator->trans("Longitude") => " ",
                // $this->translator->trans("Location Code") => $distribution->getLocation()->getCode(),
                $this->translator->trans("Activity (Modality)") => $commodityNames,
                $this->translator->trans("UNIT") => $commodityUnit,
                $this->translator->trans("Nº Of Units") => $numberOfUnits,
                $this->translator->trans("Amount (USD/SYP)") => " ",
                $this->translator->trans("Total Amount") => $totalAmount,
                $this->translator->trans("Bebelac Type") => " ",
                $this->translator->trans("Water\nNº of 1.5 bottles ") => " ",
                $this->translator->trans("Bebelac kg") => " ",
                $this->translator->trans("Nappies Pack") => " ",
                $this->translator->trans("IDPs") => $idps,
                $this->translator->trans("Residents") => $residents,
                $this->translator->trans("Nº FAMILIES") => $noFamilies,
                $this->translator->trans("FEMALE\nHead of Family gender") => $femaleHHH,
                $this->translator->trans("MALE\nHead of Family gender") => $maleHHH,
                /*
                * Male and Female children from 0 to 17 months
                */
                $this->translator->trans("Children\n0-23 months\nMale") => $maleChildrenUnder23month,
                $this->translator->trans("Children\n0-23 months\nFemale") => $femaleChildrenUnder23month,
                //$this->translator->trans("Children\n2-5") => $childrenUnder5years
                $this->translator->trans("Children\n2-5\nMale") => $maleChildrenUnder5years,
                $this->translator->trans("Children\n2-5\nFemale") => $femaleChildrenUnder5years,
                $this->translator->trans("Males\n6-17") => $maleUnder17years,
                $this->translator->trans("Females\n6-17") => $femaleUnder17years,
                $this->translator->trans("Males\n18-59") => $maleUnder59years,
                $this->translator->trans("Females\n18-59") => $femaleUnder59years,
                $this->translator->trans("Males\n60+") => $maleOver60years,
                $this->translator->trans("Females\n60+") => $femaleOver60years,
                $this->translator->trans("Total\nMales") => $maleTotal,
                $this->translator->trans("Total\nFemales") => $femaleTotal,
                $this->translator->trans("Individ. Benef.\nServed") => $beneficiaryServed,
                $this->translator->trans("Family\nSize") => $familySize,
            ];
            $exportableTable[] = $row;
        }

        return $this->exportService->export($exportableTable, 'distributions', $type);
    }

    /**
     * Export all distributions in a pdf
     *
     *
     * @return mixed
     * @throws Exception
     */
    public function exportToPdf(int $projectId)
    {
        $exportableTable = $this->assistanceRepository->findBy(['project' => $projectId, 'archived' => false]);
        $project = $this->projectRepository->find($projectId);

        try {
            $html = $this->twig->render(
                '@Distribution/Pdf/distributions.html.twig',
                array_merge(
                    [
                        'project' => $project,
                        'distributions' => $exportableTable,
                    ],
                    $this->pdfService->getInformationStyle()
                )
            );

            return $this->pdfService->printPdf($html, 'landscape', 'bookletCodes');
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function delete(Assistance $assistanceEntity)
    {
        $this->cache->delete(CacheTarget::assistanceId($assistanceEntity->getId()));
        if ($assistanceEntity->isValidated()) {
            $assistance = $this->assistanceFactory->hydrate($assistanceEntity);
            $assistance->archive();
            $this->assistanceRepository->save($assistance);

            return;
        }

        foreach ($assistanceEntity->getCommodities() as $commodity) {
            $this->em->remove($commodity);
        }
        foreach ($assistanceEntity->getAssistanceSelection()->getSelectionCriteria() as $criterion) {
            $this->em->remove($criterion);
        }
        $this->em->remove($assistanceEntity->getAssistanceSelection());

        foreach ($assistanceEntity->getDistributionBeneficiaries() as $assistanceBeneficiary) {
            /** @var AssistanceBeneficiary $assistanceBeneficiary */
            foreach ($assistanceBeneficiary->getReliefPackages() as $relief) {
                $this->em->remove($relief);
            }
            foreach ($assistanceBeneficiary->getTransactions() as $transaction) {
                $this->em->remove($transaction);
            }
            foreach ($assistanceBeneficiary->getSmartcardDeposits() as $deposit) {
                $this->em->remove($deposit);
            }
            foreach ($assistanceBeneficiary->getBooklets() as $booklet) {
                foreach ($booklet->getVouchers() as $voucher) {
                    if ($voucher->getVoucherPurchase()) {
                        foreach ($voucher->getVoucherPurchase() as $voucherPurchase) {
                            foreach ($voucherPurchase->getRecords() as $record) {
                                $this->em->remove($record);
                            }
                            $this->em->remove($voucherPurchase);
                        }
                        $this->em->remove($voucher);
                    }
                }
                $this->em->remove($booklet);
            }
            $this->em->remove($assistanceBeneficiary);
        }

        $this->em->remove($assistanceEntity);
        $this->em->flush();
    }

    /**
     *
     * @throws ExportNoDataException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @deprecated old form of exports, will be removed after export system refactoring
     */
    public function exportGeneralReliefDistributionToCsv(Assistance $assistance, string $type): string
    {
        $distributionBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)
            ->findByAssistance($assistance);

        /** @var ReliefPackage[] $packages */
        $packages = [];
        $exportableTable = [];
        foreach ($distributionBeneficiaries as $db) {
            $relief = $this->reliefPackageRepository->findOneByAssistanceBeneficiary($db);

            if ($relief) {
                $packages[] = $relief;
            }
        }

        foreach ($packages as $relief) {
            $beneficiary = $relief->getAssistanceBeneficiary()->getBeneficiary();
            $commodityNames = $relief->getModalityType();

            $commonFields = $beneficiary->getCommonExportFields();

            $exportableTable[] = array_merge($commonFields, [
                $this->translator->trans("Commodity") => $commodityNames,
                $this->translator->trans("To Distribute") => $relief->getAmountToDistribute(),
                $this->translator->trans("Spent") => $relief->getAmountSpent() ?? '0',
                $this->translator->trans("Unit") => $relief->getUnit(),
                $this->translator->trans("Distributed At") => $relief->getLastModifiedAt(),
                $this->translator->trans("Notes Distribution") => $relief->getNotes(),
                $this->translator->trans("Removed") => $relief->getAssistanceBeneficiary()->getRemoved()
                    ? 'Yes'
                    : 'No',
                $this->translator->trans("Justification for adding/removing") => $relief
                    ->getAssistanceBeneficiary()
                    ->getJustification(),
            ]);
        }

        return $this->exportService->export($exportableTable, 'relief', $type);
    }

    /**
     *
     * @throws ExportNoDataException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @deprecated old form of exports, will be removed after export system refactoring
     */
    public function exportVouchersDistributionToCsv(Assistance $assistance, string $type): string
    {
        $distributionBeneficiaries = $this->em->getRepository(AssistanceBeneficiary::class)
            ->findByAssistance($assistance);

        $beneficiaries = [];
        $exportableTable = [];
        foreach ($distributionBeneficiaries as $assistanceBeneficiary) {
            $beneficiary = $assistanceBeneficiary->getBeneficiary();
            $booklets = $assistanceBeneficiary->getBooklets();
            $transactionBooklet = null;
            if ((is_countable($booklets) ? count($booklets) : 0) > 0) {
                foreach ($booklets as $booklet) {
                    if ($booklet->getStatus() !== 3) {
                        $transactionBooklet = $booklet;
                    }
                }
                if ($transactionBooklet === null) {
                    $transactionBooklet = $booklets[0];
                }
            }

            $commonFields = $beneficiary->getCommonExportFields();

            $products = [];
            if ($transactionBooklet) {
                /** @var Voucher $voucher */
                foreach ($transactionBooklet->getVouchers() as $voucher) {
                    if ($voucher->getVoucherPurchase()) {
                        foreach ($voucher->getVoucherPurchase()->getRecords() as $record) {
                            array_push($products, $record->getProduct()->getName());
                        }
                    }
                }
            }
            $products = implode(', ', array_unique($products));

            $exportableTable[] = array_merge($commonFields, [
                $this->translator->trans("Booklet") => $transactionBooklet ? $transactionBooklet->getCode() : null,
                $this->translator->trans("Status") => $transactionBooklet ? $transactionBooklet->getStatus() : null,
                $this->translator->trans("Value") => $transactionBooklet
                    ? $transactionBooklet->getTotalValue() . ' ' . $transactionBooklet->getCurrency()
                    : null,
                $this->translator->trans("Used At") => $transactionBooklet ? $transactionBooklet->getUsedAt() : null,
                $this->translator->trans("Purchased items") => $products,
                $this->translator->trans("Removed") => $assistanceBeneficiary->getRemoved() ? 'Yes' : 'No',
                $this->translator->trans("Justification for adding/removing") => $assistanceBeneficiary
                    ->getJustification(),
            ]);
        }

        return $this->exportService->export($exportableTable, 'qrVouchers', $type);
    }

    /**
     *
     * @throws ExportNoDataException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @deprecated old form of exports, will be removed after export system refactoring
     */
    public function exportToCsvBeneficiariesInDistribution(Assistance $assistance, string $type): string
    {
        $beneficiaries = $this->beneficiaryRepository->getNotRemovedofDistribution($assistance);

        return $this->exportService->export($beneficiaries, 'beneficiaryInDistribution', $type);
    }
}
