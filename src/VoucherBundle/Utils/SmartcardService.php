<?php

namespace VoucherBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use DateTime;
use DateTimeInterface;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManager;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
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

    public function register(string $serialNumber, string $beneficiaryId, DateTime $createdAt): Smartcard
    {
        /** @var Smartcard $smartcard */
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber($serialNumber);
        if (!$smartcard) {
            $smartcard = new Smartcard($serialNumber, $createdAt);
            $smartcard->setState(Smartcard::STATE_ACTIVE);
        }

        if ($smartcard->getBeneficiary() && $smartcard->getBeneficiary()->getId() !== $beneficiaryId) {
            $smartcard->setSuspicious(true, sprintf('Beneficiary changed. #%s -> #%s',
                $smartcard->getBeneficiary()->getId(),
                $beneficiaryId
            ));
        }

        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryId);
        if ($beneficiary) {
            $smartcard->setBeneficiary($beneficiary);
        } else {
            $smartcard->setSuspicious(true, 'Beneficiary does not exists');
        }

        $this->em->persist($smartcard);
        $this->em->flush();
        return $smartcard;
    }

    public function deposit(string $serialNumber, int $distributionId, $value, $balance, DateTimeInterface $createdAt, User $user): SmartcardDeposit
    {
        $smartcard = $this->em->getRepository(Smartcard::class)->findBySerialNumber($serialNumber);
        if (!$smartcard) {
            $smartcard = $this->createSuspiciousSmartcard($serialNumber, $createdAt);
        }

        if (!$smartcard->isActive()) {
            $smartcard->setSuspicious(true, 'Smartcard is in '.$smartcard->getState().' state');
        }

        $distribution = $this->em->getRepository(Assistance::class)->find($distributionId);
        if (!$distribution) {
            throw new NotFoundHttpException('Distribution does not exist.');
        }
        if (!$smartcard->getBeneficiary()) {
            throw new NotFoundHttpException('Smartcard does not have assigned beneficiary.');
        }

        $assistanceBeneficiary = $this->em->getRepository(AssistanceBeneficiary::class)->findByDistributionAndBeneficiary(
            $distribution,
            $smartcard->getBeneficiary()
        );

        if (!$assistanceBeneficiary) {
            throw new NotFoundHttpException("Distribution does not have smartcard's beneficiary.");
        }

        $deposit = SmartcardDeposit::create(
            $smartcard,
            $user,
            $assistanceBeneficiary,
            (float) $value,
            null !== $balance ? (float) $balance : null,
            $createdAt
        );

        $smartcard->addDeposit($deposit);

        if (null === $smartcard->getCurrency()) {
            $smartcard->setCurrency(self::findCurrency($assistanceBeneficiary));
        }

        // for situation, that purchases are sync before any money were deposited, we need to fix missing currency
        foreach ($smartcard->getPurchases() as $purchase) {
            foreach ($purchase->getRecords() as $record) {
                if (null === $record->getCurrency()) {
                    $record->setCurrency($smartcard->getCurrency());
                    $this->em->persist($record);
                }
            }
        }

        $this->em->persist($smartcard);
        $this->em->flush();

        return $deposit;
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

    public function getRedemptionCandidates(Vendor $vendor): array
    {
        return $this->em->getRepository(SmartcardPurchase::class)->countPurchasesToRedeem($vendor);
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
            $vendor->getContractNo(),
            $vendor->getVendorNo(),
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
        if (null === $purchase->getSmartcard() || null === $purchase->getSmartcard()->getDeposites()) {
            return null;
        }
        $deposits = $purchase->getSmartcard()->getDeposites()->toArray();
        $purchaseDeposit = $this->getDeposit($deposits, $purchase->getCreatedAt());

        if (null === $purchaseDeposit->getAssistanceBeneficiary()->getAssistance()
            || null === $purchaseDeposit->getAssistanceBeneficiary()->getAssistance()->getProject()
        ) {
            return null;
        }

        return $purchaseDeposit->getAssistanceBeneficiary()->getAssistance()->getProject()->getId();
    }

    private function getDeposit(array $deposits, DateTimeInterface $purchaseDate): SmartcardDeposit
    {
        usort($deposits, function (SmartcardDeposit $d1, SmartcardDeposit $d2) {
            return $d2->getCreatedAt()->getTimestamp() - $d1->getCreatedAt()->getTimestamp();
        });
        /** @var SmartcardDeposit $deposit */
        foreach ($deposits as $deposit) {
            if ($deposit->getCreatedAt()->getTimestamp() <= $purchaseDate->getTimestamp()) {
                return $deposit;
            }
        }
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

    private static function findCurrency(AssistanceBeneficiary $assistanceBeneficiary): string
    {
        foreach ($assistanceBeneficiary->getAssistance()->getCommodities() as $commodity) {
            /** @var \DistributionBundle\Entity\Commodity $commodity */
            if ('Smartcard' === $commodity->getModalityType()->getName()) {
                return $commodity->getUnit();
            }
        }

        throw new \LogicException('Unable to find currency for AssistanceBeneficiary #'.$assistanceBeneficiary->getId());
    }
}
