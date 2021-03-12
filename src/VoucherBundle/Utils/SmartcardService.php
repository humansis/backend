<?php

namespace VoucherBundle\Utils;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManager;
use ProjectBundle\Entity\Project;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\SmartcardRedemptionBatch;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\InputType\SmartcardPurchase as SmartcardPurchaseInput;
use VoucherBundle\InputType\SmartcardPurchaseDeprecated as SmartcardPurchaseDeprecatedInput;
use VoucherBundle\Model\PurchaseService;
use VoucherBundle\Repository\SmartcardPurchaseRepository;
use VoucherBundle\InputType\SmartcardRedemtionBatch as RedemptionBatchInput;

class SmartcardService
{
    /** @var EntityManager */
    private $em;

    /** @var PurchaseService */
    private $purchaseService;

    public function __construct(EntityManager $em, PurchaseService $purchaseService)
    {
        $this->em = $em;
        $this->purchaseService = $purchaseService;
    }

    public function purchase(string $serialNumber, $data): SmartcardPurchase
    {
        if ($data instanceof SmartcardPurchaseInput && $data instanceof SmartcardPurchaseDeprecatedInput) {
            throw new \InvalidArgumentException('Argument 3 must be of type '.SmartcardPurchaseInput::class.' or '.SmartcardPurchaseDeprecatedInput::class);
        }

        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber($serialNumber);
        if (!$smartcard) {
            $smartcard = $this->createSuspiciousSmartcard($serialNumber, $data->getCreatedAt());
        }

        if ($data instanceof SmartcardPurchaseDeprecatedInput) {
            $products = [];
            foreach ($data->getProducts() as $product) {
                $product['currency'] = $smartcard->getCurrency();
                $products[] = $product;
            }

            $new = new SmartcardPurchaseInput();
            $new->setCreatedAt($data->getCreatedAt());
            $new->setVendorId($data->getVendorId());
            $new->setProducts($products);

            $data = $new;
        }

        return $this->purchaseService->purchaseSmartcard($smartcard, $data);
    }

    /**
     * @param Vendor               $vendor
     * @param RedemptionBatchInput $inputBatch
     * @param User                 $redeemedBy
     *
     * @return SmartcardRedemptionBatch
     * @throws \InvalidArgumentException
     */
    public function redeem(Vendor $vendor, RedemptionBatchInput $inputBatch, User $redeemedBy): SmartcardRedemptionBatch
    {
        /** @var SmartcardPurchaseRepository $repository */
        $repository = $this->em->getRepository(SmartcardPurchase::class);
        $purchases = $repository->findBy([
            'id' => $inputBatch->getPurchases(),
        ]);

        // purchases validation
        $currency = null;
        $projectId = null;
        foreach ($purchases as $purchase) {
            if ($purchase->getVendor()->getId() !== $vendor->getId()) {
                throw new \InvalidArgumentException("Inconsistent vendor and purchase' #{$purchase->getId()} vendor");
            }
            if (null !== $purchase->getRedeemedAt()) {
                throw new \InvalidArgumentException("Purchase' #{$purchase->getId()} was already redeemed at ".$purchase->getRedeemedAt()->format('Y-m-d H:i:s'));
            }
            if (null === $currency) {
                $currency = $purchase->getCurrency();
            }
            if ($purchase->getCurrency() != $currency) {
                throw new \InvalidArgumentException("Purchases have inconsistent currencies. {$purchase->getCurrency()} in {$purchase->getId()} is different than {$currency}");
            }
            if (null === $this->extractPurchaseProjectId($purchase)) {
                throw new \InvalidArgumentException("Purchase #{$purchase->getId()} has no project.");
            }
            if (null === $projectId) {
                $projectId = $this->extractPurchaseProjectId($purchase);
            }
            if ($this->extractPurchaseProjectId($purchase) !== $projectId) {
                throw new \InvalidArgumentException("Purchases have inconsistent currencies. Project #{$this->extractPurchaseProjectId($purchase)} in Purchase #{$purchase->getId()} is different than project of others: {$projectId}");
            }
        }

        $projectRepository = $this->em->getRepository(Project::class);
        $project = $projectRepository->find($projectId);

        $redemptionBath = new SmartcardRedemptionBatch(
            $vendor,
            $project,
            new \DateTime(),
            $redeemedBy,
            $repository->countPurchasesValue($purchases),
            $currency,
            $purchases
        );

        foreach ($purchases as $purchase) {
            $purchase->setRedemptionBatch($redemptionBath);
        }

        $this->em->persist($redemptionBath);
        $this->em->flush();

        return $redemptionBath;
    }

    public function extractPurchaseProjectId(SmartcardPurchase $purchase): ?int
    {
        if (null === $purchase->getSmartcard()
            || null === $purchase->getSmartcard()->getDeposit()
            || null === $purchase->getSmartcard()->getDeposit()->getAssistanceBeneficiary()->getAssistance()
            || null === $purchase->getSmartcard()->getDeposit()->getAssistanceBeneficiary()->getAssistance()->getProject()
        ) {
            return null;
        }
        return $purchase->getSmartcard()->getDeposit()->getAssistanceBeneficiary()->getAssistance()->getProject()->getId();
    }

    protected function createSuspiciousSmartcard(string $serialNumber, DateTimeInterface $createdAt): Smartcard
    {
        $smartcard = new Smartcard($serialNumber, $createdAt);
        $smartcard->setState(Smartcard::STATE_ACTIVE);
        $smartcard->setSuspicious(true, 'Smartcard does not exists in database');

        $this->em->persist($smartcard);
        $this->em->flush();

        return $smartcard;
    }
}
