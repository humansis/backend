<?php

declare(strict_types=1);

namespace Component\Smartcard;

use Component\Smartcard\Exception\SmartcardPurchaseAlreadyProcessedException;
use Component\Smartcard\Exception\SmartcardPurchaseAssistanceNotFoundException;
use Component\Smartcard\Exception\SmartcardPurchaseBeneficiaryNotFoundException;
use Component\Smartcard\Exception\SmartcardPurchaseProductNotFoundException;
use Component\Smartcard\Exception\SmartcardPurchaseVendorNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Entity\Assistance;
use Entity\Assistance\ReliefPackage;
use Entity\Beneficiary;
use Entity\Product;
use Entity\SmartcardBeneficiary;
use Enum\ModalityType;
use InputType\SmartcardPurchaseInputType;
use Model\PurchaseService;
use Psr\Log\LoggerInterface;
use Repository\Assistance\ReliefPackageRepository;
use Repository\BeneficiaryRepository;
use Repository\ProductRepository;
use Repository\Smartcard\PreliminaryInvoiceRepository;
use Entity\Project;
use Entity\SmartcardPurchase;
use Entity\Vendor;
use Repository\AssistanceRepository;
use Repository\SmartcardBeneficiaryRepository;
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
        private readonly SmartcardBeneficiaryRepository $smartcardBeneficiaryRepository,
        private readonly ReliefPackageRepository $reliefPackageRepository,
        private readonly SmartcardService $smartcardService,
        private readonly PurchaseService $purchaseService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
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
     * @return SmartcardPurchase
     * @throws SmartcardPurchaseAssistanceNotFoundException
     * @throws SmartcardPurchaseBeneficiaryNotFoundException
     * @throws SmartcardPurchaseProductNotFoundException
     * @throws SmartcardPurchaseVendorNotFoundException
     */
    public function purchase(
        string $serialNumber,
        SmartcardPurchaseInputType $input
    ): SmartcardPurchase {
        $beneficiary = $this->getBeneficiaryById($input->getBeneficiaryId());

        $smartcardBeneficiary = $this->smartcardService->getSmartcardForPurchase(
            $serialNumber,
            $beneficiary,
            $input->getCreatedAt()
        );
        $this->smartcardBeneficiaryRepository->persist($smartcardBeneficiary);

        return $this->processPurchase($smartcardBeneficiary, $input);
    }

    /**
     * @throws SmartcardPurchaseAssistanceNotFoundException
     * @throws SmartcardPurchaseProductNotFoundException
     * @throws SmartcardPurchaseVendorNotFoundException
     */
    private function processPurchase(
        SmartcardBeneficiary $smartcardBeneficiary,
        SmartcardPurchaseInputType $input
    ): SmartcardPurchase {
        $vendor = $this->getVendorById($input->getVendorId());

        $hash = $this->purchaseService->hashPurchase(
            $smartcardBeneficiary->getBeneficiary(),
            $vendor,
            $input->getCreatedAt()
        );

        if ($this->wasPurchaseAlreadyProcessed($hash)) {
            $this->logger->error("Smartcard purchase failed: Purchase with hash {$hash} was already processed.");
            throw new SmartcardPurchaseAlreadyProcessedException("Purchase with hash {$hash} was already processed.");
        }

        $assistance = $this->getAssistanceById($input->getAssistanceId());
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
        return $purchase;
    }

    /**
     * @throws NonUniqueResultException
     */
    private function addSpentAmountToReliefPackage(string $totalSpent, Assistance $assistance, Beneficiary $beneficiary): void
    {
        $reliefPackages = $this->reliefPackageRepository->findByAssistanceAndBeneficiary($assistance, $beneficiary, ModalityType::SMART_CARD);

        /** @var ReliefPackage $reliefPackage */
        foreach ($reliefPackages as $reliefPackage) {
            $reliefPackage->addSpent($totalSpent);
        }
    }

    /**
     * @throws SmartcardPurchaseProductNotFoundException
     */
    private function addPurchaseRecordsAndGetTotalValue(SmartcardPurchase $purchase, array $products): string
    {
        $totalPurchaseAmount = DecimalNumberFactory::create(0);
        foreach ($products as $product) {
            $productEntity = $this->getProductById($product->getId());
            $purchase->addRecord(
                $productEntity,
                $product->getQuantity(),
                $product->getValue(),
                $product->getCurrency()
            );

            $totalPurchaseAmount->plus(DecimalNumberFactory::create($product->getValue()));
        }

        return (string) $totalPurchaseAmount;
    }

    private function wasPurchaseAlreadyProcessed(string $hash): bool
    {
        $purchase = $this->smartcardPurchaseRepository->findOneBy(['hash' => $hash]);

        return $purchase !== null;
    }

    /**
     * @throws SmartcardPurchaseBeneficiaryNotFoundException
     */
    private function getBeneficiaryById(int $beneficiaryId): Beneficiary
    {
        $beneficiary = $this->beneficiaryRepository->findOneBy([
            'id' => $beneficiaryId,
            'archived' => false,
        ]);

        if (!$beneficiary) {
            $this->logger->error("Smartcard purchase failed: Beneficiary with ID {$beneficiaryId} does not exist!");
            throw new SmartcardPurchaseBeneficiaryNotFoundException(
                "Beneficiary with ID {$beneficiaryId} does not exist!"
            );
        }

        return $beneficiary;
    }

    /**
     * @throws SmartcardPurchaseVendorNotFoundException
     */
    private function getVendorById(int $vendorId): Vendor
    {
        $vendor = $this->vendorRepository->find($vendorId);

        if (!$vendor) {
            $this->logger->error("Smartcard purchase failed: Vendor with ID {$vendorId} does not exist!");
            throw new SmartcardPurchaseVendorNotFoundException("Vendor with ID {$vendorId} does not exist!");
        }

        return $vendor;
    }

    /**
     * @throws SmartcardPurchaseAssistanceNotFoundException
     */
    private function getAssistanceById(int $assistanceId): Assistance
    {
        $assistance = $this->assistanceRepository->find($assistanceId);

        if (!$assistance) {
            $this->logger->error("Smartcard purchase failed: Assistance with ID {$assistanceId} does not exist!");
            throw new SmartcardPurchaseAssistanceNotFoundException(
                "Assistance with ID {$assistanceId} does not exist!"
            );
        }

        return $assistance;
    }

    /**
     * @throws SmartcardPurchaseProductNotFoundException
     */
    private function getProductById(int $productId): Product
    {
        $product = $this->productRepository->find($productId);

        if (!$product) {
            $this->logger->error("Smartcard purchase failed: Product with ID {$productId} does not exist!");
            throw new SmartcardPurchaseProductNotFoundException("Product with ID {$productId} does not exist!");
        }

        return $product;
    }
}
