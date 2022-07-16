<?php declare(strict_types=1);

namespace VoucherBundle\Utils;

use NewApiBundle\Entity\Beneficiary;
use DateTime;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Repository\BeneficiaryRepository;
use DateTimeInterface;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManager;
use NewApiBundle\Component\Smartcard\Exception\SmartcardActivationDeactivatedException;
use NewApiBundle\Component\Smartcard\Exception\SmartcardDoubledRegistrationException;
use NewApiBundle\Component\Smartcard\Exception\SmartcardNotAllowedStateTransition;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Entity\Smartcard\PreliminaryInvoice;
use NewApiBundle\InputType\Smartcard\ChangeSmartcardInputType;
use NewApiBundle\InputType\Smartcard\SmartcardRegisterInputType;
use NewApiBundle\InputType\SmartcardPurchaseInputType;
use NewApiBundle\Repository\Assistance\ReliefPackageRepository;
use NewApiBundle\Workflow\ReliefPackageTransitions;
use NewApiBundle\Entity\Project;
use NewApiBundle\Repository\ProjectRepository;
use Psr\Log\LoggerInterface;
use ProjectBundle\Entity\Project;
use ProjectBundle\Repository\ProjectRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\Cache\CacheInterface;
use NewApiBundle\Entity\User;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Invoice;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Enum\SmartcardStates;
use VoucherBundle\InputType\SmartcardPurchase as SmartcardPurchaseInput;
use VoucherBundle\InputType\SmartcardPurchaseDeprecated as SmartcardPurchaseDeprecatedInput;
use VoucherBundle\Model\PurchaseService;
use VoucherBundle\Repository\SmartcardPurchaseRepository;
use VoucherBundle\InputType\SmartcardInvoice as RedemptionBatchInput;
use VoucherBundle\Repository\SmartcardRepository;

class SmartcardService
{
    /** @var EntityManager */
    private $em;

    /** @var PurchaseService */
    private $purchaseService;

    /**
     * @var SmartcardRepository
     */
    private $smartcardRepository;

    /**
     * @var SmartcardPurchaseRepository
     */
    private $smartcardPurchaseRepository;

    /**
     * @var BeneficiaryRepository
     */
    private $beneficiaryRepository;

    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    public function __construct(
        EntityManager               $em,
        PurchaseService             $purchaseService,
        SmartcardRepository         $smartcardRepository,
        SmartcardPurchaseRepository $smartcardPurchaseRepository,
        BeneficiaryRepository       $beneficiaryRepository,
        ProjectRepository           $projectRepository
    ) {
        $this->em = $em;
        $this->purchaseService = $purchaseService;
        $this->smartcardRepository = $smartcardRepository;
        $this->smartcardPurchaseRepository = $smartcardPurchaseRepository;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->projectRepository = $projectRepository;
    }

    /**
     * @param Smartcard                $smartcard
     * @param ChangeSmartcardInputType $changeSmartcardInputType
     *
     * @return void
     * @throws SmartcardActivationDeactivatedException
     * @throws SmartcardNotAllowedStateTransition
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function change(Smartcard $smartcard, ChangeSmartcardInputType $changeSmartcardInputType): void
    {
        if ($smartcard->getState() === SmartcardStates::INACTIVE) {
            throw new SmartcardActivationDeactivatedException($smartcard);
        }

        if ($smartcard->getState() !== $changeSmartcardInputType->getState()) {
            if (!SmartcardStates::isTransitionAllowed($smartcard->getState(), $changeSmartcardInputType->getState())) {
                throw new SmartcardNotAllowedStateTransition($smartcard, $changeSmartcardInputType->getState(),
                    "Not allowed transition from state {$smartcard->getState()} to {$changeSmartcardInputType->getState()}.");
            }
            $smartcard->setState($changeSmartcardInputType->getState());
            $smartcard->setChangedAt($changeSmartcardInputType->getCreatedAt());
            $this->smartcardRepository->save($smartcard);
        }
    }

    /**
     * @param SmartcardRegisterInputType $registerInputType
     *
     * @return Smartcard
     * @throws SmartcardDoubledRegistrationException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function register(SmartcardRegisterInputType $registerInputType): Smartcard
    {
        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->beneficiaryRepository->find($registerInputType->getBeneficiaryId());
        $smartcard = $this->getActualSmartcardOrCreateNew($registerInputType->getSerialNumber(), $beneficiary, $registerInputType->getCreatedAt());
        $this->checkSmartcardRegistrationDuplicity($smartcard, $registerInputType->getCreatedAt());
        $smartcard->setSuspicious(false, null);
        $smartcard->setRegisteredAt($registerInputType->getCreatedAt());

        if ($beneficiary) {
            $smartcard->setBeneficiary($beneficiary);
        } else {
            $smartcard->setSuspicious(true, "Beneficiary #{$registerInputType->getBeneficiaryId()} does not exists");
        }

        $this->smartcardRepository->save($smartcard);

        return $smartcard;
    }

    /**
     * @param Smartcard         $smartcard
     * @param DateTimeInterface $registrationDateTime
     *
     * @return void
     * @throws SmartcardDoubledRegistrationException
     */
    private function checkSmartcardRegistrationDuplicity(Smartcard $smartcard, DateTimeInterface $registrationDateTime): void
    {
        if (is_null($smartcard->getRegisteredAt())) {
            return;
        }
        if ($smartcard->getRegisteredAt()->getTimestamp() === $registrationDateTime->getTimestamp()) {
            throw new SmartcardDoubledRegistrationException($smartcard);
        }
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

        $smartcard = $this->smartcardRepository->findOneBy(['serialNumber' => $serialNumber]);
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
     * @param string $serialNumber
     * @param SmartcardPurchaseInput|SmartcardPurchaseInputType $data
     *
     * @return SmartcardPurchase
     * @throws \Doctrine\ORM\EntityNotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function purchase(string $serialNumber, $data): SmartcardPurchase
    {
        if (!$data instanceof SmartcardPurchaseInput && !$data instanceof SmartcardPurchaseInputType) {
            throw new \InvalidArgumentException('Argument 2 must be of type '.SmartcardPurchaseInput::class . 'or ' . SmartcardPurchaseInputType::class);
        }
        $beneficiary = $this->beneficiaryRepository->findOneBy([
            'id' => $data->getBeneficiaryId(),
            'archived' => false,
        ]);
        if (!$beneficiary) {
            throw new NotFoundHttpException('Beneficiary ID must exist');
        }
        $smartcard = $this->getActualSmartcardOrCreateNew($serialNumber, $beneficiary, $data->getCreatedAt());
        $this->em->persist($smartcard);
        return $this->purchaseService->purchaseSmartcard($smartcard, $data);
    }

    public function getActualSmartcardOrCreateNew(string $serialNumber, ?Beneficiary $beneficiary, DateTimeInterface $dateOfEvent): Smartcard
    {
        $smartcard = $this->smartcardRepository->findBySerialNumberAndBeneficiary($serialNumber, $beneficiary);

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

        $this->smartcardRepository->disableBySerialNumber($serialNumber, SmartcardStates::REUSED, $dateOfEvent);

        $smartcard = new Smartcard($serialNumber, $dateOfEvent);
        $smartcard->setState(SmartcardStates::ACTIVE);
        $smartcard->setBeneficiary($beneficiary);
        $smartcard->setSuspicious(true, "Smartcard made adhoc");
        $this->em->persist($smartcard);
        return $smartcard;
    }

    public function getRedemptionCandidates(Vendor $vendor): array
    {
        return $this->em->getRepository(PreliminaryInvoice::class)->findBy(['vendor' => $vendor]);
    }

    /**
     * @param Vendor               $vendor
     * @param RedemptionBatchInput $inputBatch
     * @param User                 $redeemedBy
     *
     * @return Invoice
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function redeem(Vendor $vendor, RedemptionBatchInput $inputBatch, User $redeemedBy): Invoice
    {
        $purchases = $this->smartcardPurchaseRepository->findBy([
            'id' => $inputBatch->getPurchases(),
        ], ['id'=>'asc']);

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
            $extractedProjectId = $this->extractPurchaseProjectId($purchase);
            if (null === $extractedProjectId) {
                throw new \InvalidArgumentException("Purchase #{$purchase->getId()} has no project.");
            }
            if (null === $projectId) {
                $projectId = $extractedProjectId;
            }
            if ($extractedProjectId !== $projectId) {
                throw new \InvalidArgumentException("Purchases have inconsistent currencies. Project #$extractedProjectId in Purchase #{$purchase->getId()} is different than project of others: {$projectId}");
            }
        }

        /** @var Project|null $project */
        $project = $this->projectRepository->find($projectId);

        $redemptionBath = new Invoice(
            $vendor,
            $project,
            new \DateTime(),
            $redeemedBy,
            $this->smartcardPurchaseRepository->countPurchasesValue($purchases),
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
        $smartcardDeposit = $this->getDeposit($deposits, $purchase->getCreatedAt());

        if (
            null === $smartcardDeposit->getReliefPackage()
            || null === $smartcardDeposit->getReliefPackage()->getAssistanceBeneficiary()
            || null === $smartcardDeposit->getReliefPackage()->getAssistanceBeneficiary()->getAssistance()
            || null === $smartcardDeposit->getReliefPackage()->getAssistanceBeneficiary()->getAssistance()->getProject()
        ) {
            return null;
        }

        return $smartcardDeposit->getReliefPackage()->getAssistanceBeneficiary()->getAssistance()->getProject()->getId();
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
            return $d2->getDistributedAt()->getTimestamp() - $d1->getDistributedAt()->getTimestamp();
        });
        $deposit = null;
        /** @var SmartcardDeposit $deposit */
        foreach ($deposits as $deposit) {
            if ($deposit->getDistributedAt()->getTimestamp() <= $purchaseDate->getTimestamp()) {
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
        $this->smartcardRepository->save($smartcard);

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

    /**
     * @param Smartcard     $smartcard
     * @param ReliefPackage $reliefPackage
     *
     * @return void
     * @throws \Doctrine\ORM\ORMException
     */
    public function setMissingCurrencyToSmartcardAndPurchases(Smartcard $smartcard, ReliefPackage $reliefPackage)
    {
        $this->setMissingCurrencyToSmartcard($smartcard, $reliefPackage);
        $this->setMissingCurrencyToPurchases($smartcard);
        $this->smartcardRepository->save($smartcard);
    }

    /**
     * @param Smartcard     $smartcard
     * @param ReliefPackage $reliefPackage
     *
     * @return void
     */
    private function setMissingCurrencyToSmartcard(Smartcard $smartcard, ReliefPackage $reliefPackage): void
    {
        if (null === $smartcard->getCurrency()) {
            $smartcard->setCurrency(SmartcardService::findCurrency($reliefPackage->getAssistanceBeneficiary()));
        }
    }

    /**
     * @param Smartcard $smartcard
     *
     * @return void
     * @throws \Doctrine\ORM\ORMException
     */
    private function setMissingCurrencyToPurchases(Smartcard $smartcard): void
    {
        foreach ($smartcard->getPurchases() as $purchase) {
            foreach ($purchase->getRecords() as $record) {
                if (null === $record->getCurrency()) {
                    $record->setCurrency($smartcard->getCurrency());
                    $this->em->persist($record);
                }
            }
        }
    }
}
