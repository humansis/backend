<?php

namespace VoucherBundle\Utils;

use BeneficiaryBundle\Entity\Beneficiary;
use DateTime;
use DateTimeInterface;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManager;
use NewApiBundle\Entity\AssistanceBeneficiaryCommodity;
use NewApiBundle\Enum\AssistanceBeneficiaryCommodityState;
use ProjectBundle\Entity\Project;
use ProjectBundle\Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\SmartcardRedemptionBatch;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Enum\SmartcardStates;
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
        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->em->getRepository(Beneficiary::class)->find($beneficiaryId);
        $smartcard = $this->getActualSmartcard($serialNumber, $beneficiary, $createdAt);
        $smartcard->setSuspicious(false, null);

        if ($beneficiary) {
            $smartcard->setBeneficiary($beneficiary);
        } else {
            $smartcard->setSuspicious(true, "Beneficiary #$beneficiaryId does not exists");
        }

        $this->em->persist($smartcard);
        $this->em->flush();
        return $smartcard;
    }

    public function deposit(string $serialNumber, int $assistanceId, ?int $beneficiaryId, $value, $balance, DateTimeInterface $distributedAt, User $user): SmartcardDeposit
    {
        $assistance = $this->em->getRepository(Assistance::class)->find($assistanceId);
        if (!$assistance) {
            throw new NotFoundHttpException('Assistance does not exist.');
        }
        $beneficiary = $this->em->getRepository(Beneficiary::class)->findOneBy([
            'id' => $beneficiaryId,
            'archived' => false,
        ]);
        if (!$beneficiary) {
            throw new NotFoundHttpException('Beneficiary ID must exist');
        }

        /** @var AssistanceBeneficiaryCommodity $assistanceBeneficiaryCommodity */
        $assistanceBeneficiaryCommodity = $this->em->getRepository(AssistanceBeneficiaryCommodity::class)
            ->findForSmartcardByAssistanceBeneficiary($assistance, $beneficiary);

        if (!$assistanceBeneficiaryCommodity || $assistanceBeneficiaryCommodity->getState() !== AssistanceBeneficiaryCommodityState::TO_DISTRIBUTE) {
            throw new NotFoundHttpException("There is nothing to distribute to beneficiary #{$beneficiary->getId()} in assistance #{$assistance->getId()}");
        }

        $smartcard = $this->getActualSmartcard($serialNumber, $beneficiary, $distributedAt);

        if (!$smartcard->getBeneficiary()) {
            throw new NotFoundHttpException('Smartcard does not have assigned beneficiary.');
        }

        $deposit = SmartcardDeposit::create(
            $smartcard,
            $user,
            $assistanceBeneficiaryCommodity,
            (float) $value,
            null !== $balance ? (float) $balance : null,
            $distributedAt
        );

        $smartcard->addDeposit($deposit);

        $assistanceBeneficiaryCommodity->setState(AssistanceBeneficiaryCommodityState::DISTRIBUTED);

        if (null === $smartcard->getCurrency()) {
            $smartcard->setCurrency(self::findCurrency($assistanceBeneficiaryCommodity->getAssistanceBeneficiary()));
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

    /**
     * @deprecated use version with SC reuse
     * @see self::purchase
     */
    public function purchaseWithoutReusingSC(string $serialNumber, $data): SmartcardPurchase
    {
        if ($data instanceof SmartcardPurchaseInput && $data instanceof SmartcardPurchaseDeprecatedInput) {
            throw new \InvalidArgumentException('Argument 3 must be of type '.SmartcardPurchaseInput::class.' or '.SmartcardPurchaseDeprecatedInput::class);
        }

        $smartcard = $this->em->getRepository(Smartcard::class)->findOneBy(['serialNumber' => $serialNumber]);
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

    public function purchase(string $serialNumber, $data): SmartcardPurchase
    {
        if (!$data instanceof SmartcardPurchaseInput) {
            throw new \InvalidArgumentException('Argument 3 must be of type '.SmartcardPurchaseInput::class);
        }
        $beneficiary = $this->em->getRepository(Beneficiary::class)->findOneBy([
            'id' => $data->getBeneficiaryId(),
            'archived' => false,
        ]);
        if (!$beneficiary) {
            throw new NotFoundHttpException('Beneficiary ID must exist');
        }
        $smartcard = $this->getActualSmartcard($serialNumber, $beneficiary, $data->getCreatedAt());
        $this->em->persist($smartcard);
        return $this->purchaseService->purchaseSmartcard($smartcard, $data);
    }

    public function getActualSmartcard(string $serialNumber, ?Beneficiary $beneficiary, DateTimeInterface $dateOfEvent): Smartcard
    {
        $repo = $this->em->getRepository(Smartcard::class);
        $smartcard = $repo->findBySerialNumber($serialNumber, $beneficiary);

        if ($smartcard
            && $smartcard->getBeneficiary()
            && $smartcard->getBeneficiary()->getId() === $beneficiary->getId()
        ) {
            $eventWasBeforeDisable = $smartcard->getDisabledAt()
                && $smartcard->getDisabledAt()->getTimestamp() > $dateOfEvent->getTimestamp();

            if (SmartcardStates::ACTIVE === $smartcard->getState()
                || $eventWasBeforeDisable) {
                return $smartcard;
            }else {
                $smartcard->setSuspicious(true, "Using disabled card");
                return $smartcard;
            }
        }

        $repo->disableBySerialNumber($serialNumber, SmartcardStates::REUSED, $dateOfEvent);

        $smartcard = new Smartcard($serialNumber, $dateOfEvent);
        $smartcard->setState(SmartcardStates::ACTIVE);
        $smartcard->setBeneficiary($beneficiary);
        $smartcard->setSuspicious(true, "Smartcard made adhoc");
        $this->em->persist($smartcard);
        return $smartcard;
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

        /** @var ProjectRepository $projectRepository */
        $projectRepository = $this->em->getRepository(Project::class);

        /** @var Project|null $project */
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

    /**
     * @param array             $deposits
     * @param DateTimeInterface $purchaseDate
     *
     * @return SmartcardDeposit
     * @deprecated it works bad, dont use it
     */
    private function getDeposit(array $deposits, DateTimeInterface $purchaseDate): SmartcardDeposit
    {
        usort($deposits, function (SmartcardDeposit $d1, SmartcardDeposit $d2) {
            return $d2->getCreatedAt()->getTimestamp() - $d1->getCreatedAt()->getTimestamp();
        });
        $deposit = null;
        /** @var SmartcardDeposit $deposit */
        foreach ($deposits as $deposit) {
            if ($deposit->getCreatedAt()->getTimestamp() <= $purchaseDate->getTimestamp()) {
                return $deposit;
            }
        }
        return $deposit;
    }

    protected function createSuspiciousSmartcard(string $serialNumber, DateTimeInterface $createdAt): Smartcard
    {
        $smartcard = new Smartcard($serialNumber, $createdAt);
        $smartcard->setState(SmartcardStates::ACTIVE);
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
