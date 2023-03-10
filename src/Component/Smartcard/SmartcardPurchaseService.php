<?php

declare(strict_types=1);

namespace Component\Smartcard;

use Component\Smartcard\Exception\SmartcardPurchaseAlreadyProcessedException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Entity\Assistance;
use Entity\Assistance\ReliefPackage;
use Entity\Beneficiary;
use Entity\SmartcardBeneficiary;
use Enum\ModalityType;
use InputType\SmartcardPurchaseInputType;
use Model\PurchaseService;
use Repository\Assistance\ReliefPackageRepository;
use Repository\BeneficiaryRepository;
use Repository\ProductRepository;
use Repository\Smartcard\PreliminaryInvoiceRepository;
use Entity\Project;
use Entity\SmartcardPurchase;
use Entity\Vendor;
use Repository\AssistanceRepository;
use Repository\SmartcardPurchaseRepository;
use Repository\VendorRepository;
use Utils\DecimalNumber\DecimalNumberFactory;
use Utils\SmartcardService;

class SmartcardPurchaseService
{
    public function __construct(
        private readonly SmartcardPurchaseRepository $smartcardPurchaseRepository,
        private readonly AssistanceRepository $assistanceRepository,
        private readonly PreliminaryInvoiceRepository $preliminaryInvoiceRepository,
        private readonly BeneficiaryRepository $beneficiaryRepository,
        private readonly ProductRepository $productRepository,
        private readonly VendorRepository $vendorRepository,
        private readonly ReliefPackageRepository $reliefPackageRepository,
        private readonly SmartcardService $smartcardService,
        private readonly PurchaseService $purchaseService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function getBy(Vendor $vendor, Project $project, string $currency)
    {
        $preliminaryInvoices = $this->preliminaryInvoiceRepository->findBy([
            'vendor' => $vendor,
            'project' => $project,
            'currency' => $currency,
        ]);
        foreach ($preliminaryInvoices as $preliminaryInvoice) {
            return $this->smartcardPurchaseRepository->findBy(['id' => $preliminaryInvoice->getPurchaseIds()]);
        }

        return [];
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function purchase(
        string $serialNumber,
        SmartcardPurchaseInputType $input
    ): SmartcardPurchase {
        $beneficiary = $this->beneficiaryRepository->getById($input->getBeneficiaryId());

        $smartcardBeneficiary = $this->smartcardService->getOrCreateSmartcardForPurchase(
            $serialNumber,
            $beneficiary,
            $input->getCreatedAt()
        );

        return $this->processPurchase($smartcardBeneficiary, $input);
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    private function processPurchase(
        SmartcardBeneficiary $smartcardBeneficiary,
        SmartcardPurchaseInputType $input
    ): SmartcardPurchase {
        $vendor = $this->vendorRepository->getById($input->getVendorId());

        $hash = $this->purchaseService->hashPurchase(
            $smartcardBeneficiary->getBeneficiary(),
            $vendor,
            $input->getCreatedAt()
        );

        if ($this->smartcardPurchaseRepository->doesPurchaseWithHashExist($hash)) {
            throw new SmartcardPurchaseAlreadyProcessedException("Purchase with hash $hash was already processed.");
        }

        $assistance = $this->assistanceRepository->getById($input->getAssistanceId());
        $purchase = SmartcardPurchase::create(
            $smartcardBeneficiary,
            $vendor,
            $input->getCreatedAt(),
            $assistance
        );

        $purchase->setHash($hash);
        $totalValue = $this->addPurchaseRecordsAndGetTotalValue($purchase, $input->getProducts());
        $smartcardBeneficiary->addPurchase($purchase);

        $this->addSpentAmountToReliefPackage($totalValue, $assistance, $smartcardBeneficiary->getBeneficiary());
        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        return $purchase;
    }

    /**
     * @throws NonUniqueResultException
     */
    private function addSpentAmountToReliefPackage(
        string $totalSpent,
        Assistance $assistance,
        Beneficiary $beneficiary
    ): void {
        $reliefPackages = $this->reliefPackageRepository->findByAssistanceAndBeneficiary(
            $assistance,
            $beneficiary,
            ModalityType::SMART_CARD
        );

        /** @var ReliefPackage $reliefPackage */
        foreach ($reliefPackages as $reliefPackage) {
            $reliefPackage->addSpent($totalSpent);
        }
    }

    /**
     * @throws EntityNotFoundException
     */
    private function addPurchaseRecordsAndGetTotalValue(SmartcardPurchase $purchase, array $products): string
    {
        $totalPurchaseAmount = DecimalNumberFactory::create(0);
        foreach ($products as $product) {
            $productEntity = $this->productRepository->getById($product->getId());
            $purchase->addRecord(
                $productEntity,
                $product->getQuantity(),
                $product->getValue(),
                $product->getCurrency()
            );

            $totalPurchaseAmount = $totalPurchaseAmount->plus(DecimalNumberFactory::create($product->getValue()));
        }

        return (string) $totalPurchaseAmount;
    }
}
