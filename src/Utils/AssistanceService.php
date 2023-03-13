<?php

namespace Utils;

use Doctrine\ORM\Exception\ORMException;
use Entity\AbstractBeneficiary;
use Entity\User;
use Exception\CsvParserException;
use Exception\ExportNoDataException;
use InputType\Assistance\UpdateAssistanceInputType;
use Pagination\Paginator;
use DateTime;
use DateTimeInterface;
use DTO\VulnerabilityScore;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Psr\Cache\InvalidArgumentException;
use Repository\Assistance\ReliefPackageRepository;
use Repository\AssistanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Component\Assistance\AssistanceFactory;
use Component\Assistance\SelectionCriteriaFactory;
use Entity\Assistance\ReliefPackage;
use Enum\CacheTarget;
use InputType\AssistanceCreateInputType;
use Repository\BeneficiaryRepository;
use Repository\ProjectRepository;
use Request\Pagination;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
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
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CriteriaAssistanceService $criteriaAssistanceService,
        private readonly Environment $twig,
        private readonly CacheInterface $cache,
        private readonly AssistanceFactory $assistanceFactory,
        private readonly AssistanceRepository $assistanceRepository,
        private readonly SelectionCriteriaFactory $selectionCriteriaFactory,
        private readonly TranslatorInterface $translator,
        private readonly ProjectRepository $projectRepository,
        private readonly BeneficiaryRepository $beneficiaryRepository,
        private readonly ReliefPackageRepository $reliefPackageRepository,
        private readonly ExportService $exportService,
        private readonly PdfService $pdfService,
        private readonly ProjectService $projectService,
    ) {
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
                'Pdf/distributions.html.twig',
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
    public function delete(Assistance $assistanceEntity): void
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

        $this->projectService->removeAssistanceCountCache($assistanceEntity->getProject());

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
