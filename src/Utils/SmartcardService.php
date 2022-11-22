<?php

declare(strict_types=1);

namespace Utils;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Entity\Beneficiary;
use DateTimeInterface;
use Entity\AssistanceBeneficiary;
use Doctrine\ORM\EntityManager;
use Component\Smartcard\Exception\SmartcardActivationDeactivatedException;
use Component\Smartcard\Exception\SmartcardDoubledRegistrationException;
use Component\Smartcard\Exception\SmartcardNotAllowedStateTransition;
use Entity\Assistance\ReliefPackage;
use Entity\Commodity;
use Enum\ModalityType;
use InputType\Smartcard\ChangeSmartcardInputType;
use InputType\Smartcard\SmartcardRegisterInputType;
use InputType\SmartcardPurchaseInputType;
use InputType\Smartcard\UpdateSmartcardInputType;
use InvalidArgumentException;
use LogicException;
use Repository\BeneficiaryRepository;
use Repository\Smartcard\PreliminaryInvoiceRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Entity\Smartcard;
use Entity\SmartcardPurchase;
use Entity\Vendor;
use Enum\SmartcardStates;
use InputType\SmartcardPurchase as SmartcardPurchaseInput;
use Model\PurchaseService;
use Repository\SmartcardRepository;

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
     * @var BeneficiaryRepository
     */
    private $beneficiaryRepository;

    /**
     * @var PreliminaryInvoiceRepository
     */
    private $preliminaryInvoiceRepository;

    public function __construct(
        EntityManager $em,
        PurchaseService $purchaseService,
        SmartcardRepository $smartcardRepository,
        BeneficiaryRepository $beneficiaryRepository,
        PreliminaryInvoiceRepository $preliminaryInvoiceRepository
    ) {
        $this->em = $em;
        $this->purchaseService = $purchaseService;
        $this->smartcardRepository = $smartcardRepository;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->preliminaryInvoiceRepository = $preliminaryInvoiceRepository;
    }

    /**
     * @param Smartcard $smartcard
     * @param ChangeSmartcardInputType $changeSmartcardInputType
     *
     * @return void
     * @throws SmartcardActivationDeactivatedException
     * @throws SmartcardNotAllowedStateTransition
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function change(Smartcard $smartcard, ChangeSmartcardInputType $changeSmartcardInputType): void
    {
        if ($smartcard->getState() === SmartcardStates::INACTIVE) {
            throw new SmartcardActivationDeactivatedException($smartcard);
        }

        if ($smartcard->getState() !== $changeSmartcardInputType->getState()) {
            if (!SmartcardStates::isTransitionAllowed($smartcard->getState(), $changeSmartcardInputType->getState())) {
                throw new SmartcardNotAllowedStateTransition(
                    $smartcard,
                    $changeSmartcardInputType->getState(),
                    "Not allowed transition from state {$smartcard->getState()} to {$changeSmartcardInputType->getState()}."
                );
            }
            $smartcard->setState($changeSmartcardInputType->getState());
            $smartcard->setChangedAt($changeSmartcardInputType->getCreatedAt());
            $this->smartcardRepository->save($smartcard);
        }
    }

    /**
     * @param Smartcard $smartcard
     * @param UpdateSmartcardInputType $updateSmartcardInputType
     *
     * @return Smartcard
     * @throws SmartcardNotAllowedStateTransition
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function update(Smartcard $smartcard, UpdateSmartcardInputType $updateSmartcardInputType): Smartcard
    {
        if ($smartcard->getState() !== $updateSmartcardInputType->getState()) {
            if (!SmartcardStates::isTransitionAllowed($smartcard->getState(), $updateSmartcardInputType->getState())) {
                throw new SmartcardNotAllowedStateTransition(
                    $smartcard,
                    $updateSmartcardInputType->getState(),
                    "Not allowed transition from state {$smartcard->getState()} to {$updateSmartcardInputType->getState()}."
                );
            }
            if ($updateSmartcardInputType->getState() === SmartcardStates::INACTIVE) {
                $smartcard->setDisabledAt($updateSmartcardInputType->getCreatedAt());
            }
            if ($smartcard->isSuspicious() !== $updateSmartcardInputType->isSuspicious()) {
                $smartcard->setSuspicious(
                    $updateSmartcardInputType->isSuspicious(),
                    $updateSmartcardInputType->getSuspiciousReason()
                );
            }
            $smartcard->setState($updateSmartcardInputType->getState());
            $smartcard->setChangedAt($updateSmartcardInputType->getCreatedAt());
            $this->smartcardRepository->save($smartcard);
        }

        return $smartcard;
    }

    /**
     * @param SmartcardRegisterInputType $registerInputType
     *
     * @return Smartcard
     * @throws SmartcardDoubledRegistrationException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function register(SmartcardRegisterInputType $registerInputType): Smartcard
    {
        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->beneficiaryRepository->find($registerInputType->getBeneficiaryId());
        $smartcard = $this->getActualSmartcardOrCreateNew(
            $registerInputType->getSerialNumber(),
            $beneficiary,
            $registerInputType->getCreatedAt()
        );
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
     * @param Smartcard $smartcard
     * @param DateTimeInterface $registrationDateTime
     *
     * @return void
     * @throws SmartcardDoubledRegistrationException
     */
    private function checkSmartcardRegistrationDuplicity(
        Smartcard $smartcard,
        DateTimeInterface $registrationDateTime
    ): void {
        if (is_null($smartcard->getRegisteredAt())) {
            return;
        }
        if ($smartcard->getRegisteredAt()->getTimestamp() === $registrationDateTime->getTimestamp()) {
            throw new SmartcardDoubledRegistrationException($smartcard);
        }
    }

    /**
     * @param string $serialNumber
     * @param SmartcardPurchaseInput|SmartcardPurchaseInputType $data
     *
     * @return SmartcardPurchase
     * @throws EntityNotFoundException
     * @throws ORMException
     */
    public function purchase(string $serialNumber, $data): SmartcardPurchase
    {
        if (!$data instanceof SmartcardPurchaseInput && !$data instanceof SmartcardPurchaseInputType) {
            throw new InvalidArgumentException(
                'Argument 2 must be of type ' . SmartcardPurchaseInput::class . 'or ' . SmartcardPurchaseInputType::class
            );
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

    public function getActualSmartcardOrCreateNew(
        string $serialNumber,
        ?Beneficiary $beneficiary,
        DateTimeInterface $dateOfEvent
    ): Smartcard {
        $smartcard = $this->smartcardRepository->findBySerialNumberAndBeneficiary($serialNumber, $beneficiary);

        if (
            $smartcard
            && $smartcard->getBeneficiary()
            && $smartcard->getBeneficiary()->getId() === $beneficiary->getId()
        ) {
            $eventWasBeforeDisable = $smartcard->getDisabledAt()
                && $smartcard->getDisabledAt()->getTimestamp() > $dateOfEvent->getTimestamp();

            if (
                SmartcardStates::ACTIVE === $smartcard->getState()
                || $eventWasBeforeDisable
            ) {
                return $smartcard;
            } else {
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

    private static function findCurrency(AssistanceBeneficiary $assistanceBeneficiary): string
    {
        foreach ($assistanceBeneficiary->getAssistance()->getCommodities() as $commodity) {
            /** @var Commodity $commodity */
            if ($commodity->getModalityType() === ModalityType::SMART_CARD) {
                return $commodity->getUnit();
            }
        }

        throw new LogicException(
            'Unable to find currency for AssistanceBeneficiary #' . $assistanceBeneficiary->getId()
        );
    }

    /**
     * @param Smartcard $smartcard
     * @param ReliefPackage $reliefPackage
     *
     * @return void
     * @throws ORMException
     */
    public function setMissingCurrencyToSmartcardAndPurchases(Smartcard $smartcard, ReliefPackage $reliefPackage)
    {
        $this->setMissingCurrencyToSmartcard($smartcard, $reliefPackage);
        $this->setMissingCurrencyToPurchases($smartcard);
        $this->smartcardRepository->save($smartcard);
    }

    /**
     * @param Smartcard $smartcard
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
     * @throws ORMException
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

    /**
     * @param string $smartcardCode
     *
     * @retrun Smartcard
     * @throws ORMException
     */
    public function getSmartcardByCode(string $smartcardCode)
    {
        $smartcard = $this->smartcardRepository->findOneBy(['serialNumber' => $smartcardCode]);

        if (!$smartcard) {
            throw new NotFoundHttpException("Card with code '{$smartcardCode}' does not exists");
        }

        return $smartcard;
    }
}
