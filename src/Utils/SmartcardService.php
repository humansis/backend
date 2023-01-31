<?php

declare(strict_types=1);

namespace Utils;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Exception\ORMException;
use Entity\Beneficiary;
use DateTimeInterface;
use Entity\AssistanceBeneficiary;
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Entity\Smartcard;
use Entity\SmartcardPurchase;
use Enum\SmartcardStates;
use InputType\SmartcardPurchase as SmartcardPurchaseInput;
use Model\PurchaseService;
use Repository\SmartcardRepository;

class SmartcardService
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
        private readonly SmartcardRepository $smartcardRepository,
        private readonly BeneficiaryRepository $beneficiaryRepository,
    ) {
    }

    /**
     * @throws SmartcardNotAllowedStateTransition
     * @throws SmartcardActivationDeactivatedException
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
     * @throws SmartcardNotAllowedStateTransition
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
     * @throws SmartcardDoubledRegistrationException
     */
    public function register(SmartcardRegisterInputType $registerInputType): Smartcard
    {
        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->beneficiaryRepository->find($registerInputType->getBeneficiaryId());
        $smartcard = $this->getOrCreateActiveSmartcardForBeneficiary(
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
    public function purchase(
        string $serialNumber,
        SmartcardPurchaseInput | SmartcardPurchaseInputType $data
    ): SmartcardPurchase {
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
        $smartcard = $this->getSmartcardForPurchase(
            $serialNumber,
            $beneficiary,
            $data->getCreatedAt()
        );
        $this->smartcardRepository->persist($smartcard);

        return $this->purchaseService->purchaseSmartcard($smartcard, $data);
    }

    public function getSmartcardForPurchase(
        string $serialNumber,
        Beneficiary $beneficiary,
        DateTimeInterface $createdAt
    ): Smartcard {
        $smartcard = $this->getSmartcardForBeneficiaryBySerialNumber($serialNumber, $beneficiary, $createdAt);
        if (!$smartcard) {
            $smartcard = $this->createSmartcardForBeneficiary($serialNumber, $beneficiary, $createdAt);
        }

        return $smartcard;
    }

    /**
     * Returns already assigned Smartcard for BNF or creates new one
     * for both cases Smartcard is activated and others are deactivated
     */
    public function getOrCreateActiveSmartcardForBeneficiary(
        string $serialNumber,
        Beneficiary $beneficiary,
        DateTimeInterface $dateOfEvent
    ): Smartcard {
        $smartcard = $this->getSmartcardForBeneficiaryBySerialNumber($serialNumber, $beneficiary, $dateOfEvent);
        $smartcard ?: $smartcard = $this->createSmartcardForBeneficiary($serialNumber, $beneficiary, $dateOfEvent);
        $this->activateSmartcardAndDisableOthers($smartcard);

        return $smartcard;
    }

    private function getSmartcardForBeneficiaryBySerialNumber(
        string $serialNumber,
        Beneficiary $beneficiary,
        DateTimeInterface $dateOfEvent
    ): ?Smartcard {
        $smartcard = $this->smartcardRepository->findBySerialNumberAndBeneficiary($serialNumber, $beneficiary);
        if ($smartcard) {
            $this->checkAndMarkDisabledSmartcardAsSuspicious($smartcard, $dateOfEvent);

            return $smartcard;
        }

        return null;
    }

    private function createSmartcardForBeneficiary(
        string $serialNumber,
        Beneficiary $beneficiary,
        DateTimeInterface $dateOfEvent
    ): Smartcard {
        $this->smartcardRepository->disableBySerialNumber($serialNumber, SmartcardStates::REUSED, $dateOfEvent);
        $smartcard = new Smartcard($serialNumber, $dateOfEvent);
        $smartcard->setBeneficiary($beneficiary);
        $smartcard->setSuspicious(true, "Smartcard made adhoc");
        $this->smartcardRepository->persist($smartcard);

        return $smartcard;
    }

    private function checkAndMarkDisabledSmartcardAsSuspicious(
        Smartcard $smartcard,
        DateTimeInterface $dateOfEvent
    ): void {
        $eventWasBeforeDisable = $smartcard->getDisabledAt()
            && $smartcard->getDisabledAt()->getTimestamp() > $dateOfEvent->getTimestamp();

        if (SmartcardStates::ACTIVE !== $smartcard->getState() && !$eventWasBeforeDisable) {
            $smartcard->setSuspicious(true, "Using disabled card");
        }
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

    public function setMissingCurrencyToSmartcardAndPurchases(Smartcard $smartcard, ReliefPackage $reliefPackage): void
    {
        $this->setMissingCurrencyToSmartcard($smartcard, $reliefPackage);
        $this->setMissingCurrencyToPurchases($smartcard);
        $this->smartcardRepository->save($smartcard);
    }

    private function setMissingCurrencyToSmartcard(Smartcard $smartcard, ReliefPackage $reliefPackage): void
    {
        if (null === $smartcard->getCurrency()) {
            $smartcard->setCurrency(SmartcardService::findCurrency($reliefPackage->getAssistanceBeneficiary()));
        }
    }

    private function setMissingCurrencyToPurchases(Smartcard $smartcard): void
    {
        foreach ($smartcard->getPurchases() as $purchase) {
            foreach ($purchase->getRecords() as $record) {
                if (null === $record->getCurrency()) {
                    $record->setCurrency($smartcard->getCurrency());
                    $this->smartcardRepository->persist($record);
                }
            }
        }
    }

    public function getSmartcardByCode(string $smartcardCode): Smartcard
    {
        $smartcard = $this->smartcardRepository->findOneBy(['serialNumber' => $smartcardCode]);

        if (!$smartcard) {
            throw new NotFoundHttpException("Card with code '{$smartcardCode}' does not exists");
        }

        return $smartcard;
    }

    private function activateSmartcardAndDisableOthers(Smartcard $smartcardForActivation): void
    {
        if (!$smartcardForActivation->getBeneficiary()) {
            throw new LogicException(
                "Smartcard must have assigned Beneficiary at this point. SmartcardId: {$smartcardForActivation->getId()}"
            );
        }
        $smartcardForActivation->setState(SmartcardStates::ACTIVE);

        $activatedSmartcardsByBeneficiary = $this->smartcardRepository->findBy(
            ['beneficiary' => $smartcardForActivation->getBeneficiary(), 'state' => SmartcardStates::ACTIVE]
        );
        foreach ($activatedSmartcardsByBeneficiary as $smartcardBnf) {
            if ($smartcardForActivation->getId() !== $smartcardBnf->getId()) {
                $this->smartcardRepository->disable($smartcardBnf);
            }
        }
    }
}
