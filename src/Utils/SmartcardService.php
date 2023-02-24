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
use Entity\SmartcardBeneficiary;
use Enum\ModalityType;
use InputType\Smartcard\ChangeSmartcardInputType;
use InputType\Smartcard\SmartcardRegisterInputType;
use InputType\SmartcardPurchaseInputType;
use InputType\Smartcard\UpdateSmartcardInputType;
use InvalidArgumentException;
use LogicException;
use Repository\BeneficiaryRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Entity\SmartcardPurchase;
use Enum\SmartcardStates;
use InputType\SmartcardPurchase as SmartcardPurchaseInput;
use Model\PurchaseService;
use Repository\SmartcardBeneficiaryRepository;

class SmartcardService
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
        private readonly SmartcardBeneficiaryRepository $smartcardBeneficiaryRepository,
        private readonly BeneficiaryRepository $beneficiaryRepository,
    ) {
    }

    /**
     * @throws SmartcardNotAllowedStateTransition
     * @throws SmartcardActivationDeactivatedException
     */
    public function change(SmartcardBeneficiary $smartcardBeneficiary, ChangeSmartcardInputType $changeSmartcardInputType): void
    {
        if ($smartcardBeneficiary->getState() === SmartcardStates::INACTIVE) {
            throw new SmartcardActivationDeactivatedException($smartcardBeneficiary);
        }

        if ($smartcardBeneficiary->getState() !== $changeSmartcardInputType->getState()) {
            if (!SmartcardStates::isTransitionAllowed($smartcardBeneficiary->getState(), $changeSmartcardInputType->getState())) {
                throw new SmartcardNotAllowedStateTransition(
                    $smartcardBeneficiary,
                    $changeSmartcardInputType->getState(),
                    "Not allowed transition from state {$smartcardBeneficiary->getState()} to {$changeSmartcardInputType->getState()}."
                );
            }
            $smartcardBeneficiary->setState($changeSmartcardInputType->getState());
            $smartcardBeneficiary->setChangedAt($changeSmartcardInputType->getCreatedAt());
            $this->smartcardBeneficiaryRepository->save($smartcardBeneficiary);
        }
    }

    /**
     * @throws SmartcardNotAllowedStateTransition
     */
    public function update(SmartcardBeneficiary $smartcardBeneficiary, UpdateSmartcardInputType $updateSmartcardInputType): SmartcardBeneficiary
    {
        if ($smartcardBeneficiary->getState() !== $updateSmartcardInputType->getState()) {
            if (!SmartcardStates::isTransitionAllowed($smartcardBeneficiary->getState(), $updateSmartcardInputType->getState())) {
                throw new SmartcardNotAllowedStateTransition(
                    $smartcardBeneficiary,
                    $updateSmartcardInputType->getState(),
                    "Not allowed transition from state {$smartcardBeneficiary->getState()} to {$updateSmartcardInputType->getState()}."
                );
            }
            if ($updateSmartcardInputType->getState() === SmartcardStates::INACTIVE) {
                $smartcardBeneficiary->setDisabledAt($updateSmartcardInputType->getCreatedAt());
            }
            if ($smartcardBeneficiary->isSuspicious() !== $updateSmartcardInputType->isSuspicious()) {
                $smartcardBeneficiary->setSuspicious(
                    $updateSmartcardInputType->isSuspicious(),
                    $updateSmartcardInputType->getSuspiciousReason()
                );
            }
            $smartcardBeneficiary->setState($updateSmartcardInputType->getState());
            $smartcardBeneficiary->setChangedAt($updateSmartcardInputType->getCreatedAt());
            $this->smartcardBeneficiaryRepository->save($smartcardBeneficiary);
        }

        return $smartcardBeneficiary;
    }

    /**
     * @throws SmartcardDoubledRegistrationException
     */
    public function register(SmartcardRegisterInputType $registerInputType): SmartcardBeneficiary
    {
        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->beneficiaryRepository->find($registerInputType->getBeneficiaryId());
        $smartcardBeneficiary = $this->getOrCreateActiveSmartcardForBeneficiary(
            $registerInputType->getSerialNumber(),
            $beneficiary,
            $registerInputType->getCreatedAt()
        );
        $this->checkSmartcardRegistrationDuplicity($smartcardBeneficiary, $registerInputType->getCreatedAt());
        $smartcardBeneficiary->setSuspicious(false, null);
        $smartcardBeneficiary->setRegisteredAt($registerInputType->getCreatedAt());

        if ($beneficiary) {
            $smartcardBeneficiary->setBeneficiary($beneficiary);
        } else {
            $smartcardBeneficiary->setSuspicious(true, "Beneficiary #{$registerInputType->getBeneficiaryId()} does not exists");
        }

        $this->smartcardBeneficiaryRepository->save($smartcardBeneficiary);

        return $smartcardBeneficiary;
    }

    /**
     * @throws SmartcardDoubledRegistrationException
     */
    private function checkSmartcardRegistrationDuplicity(
        SmartcardBeneficiary $smartcardBeneficiary,
        DateTimeInterface $registrationDateTime
    ): void {
        if (is_null($smartcardBeneficiary->getRegisteredAt())) {
            return;
        }
        if ($smartcardBeneficiary->getRegisteredAt()->getTimestamp() === $registrationDateTime->getTimestamp()) {
            throw new SmartcardDoubledRegistrationException($smartcardBeneficiary);
        }
    }

    public function getOrCreateSmartcardForPurchase(
        string $serialNumber,
        Beneficiary $beneficiary,
        DateTimeInterface $createdAt
    ): SmartcardBeneficiary {
        $smartcardBeneficiary = $this->getSmartcardForBeneficiaryBySerialNumber(
            $serialNumber,
            $beneficiary,
            $createdAt
        );

        if (!$smartcardBeneficiary) {
            $smartcardBeneficiary = $this->createSmartcardForBeneficiary($serialNumber, $beneficiary, $createdAt);
        }

        return $smartcardBeneficiary;
    }

    /**
     * Returns already assigned Smartcard for BNF or creates new one
     * for both cases Smartcard is activated and others are deactivated
     */
    public function getOrCreateActiveSmartcardForBeneficiary(
        string $serialNumber,
        Beneficiary $beneficiary,
        DateTimeInterface $dateOfEvent
    ): SmartcardBeneficiary {
        $smartcardBeneficiary = $this->getSmartcardForBeneficiaryBySerialNumber($serialNumber, $beneficiary, $dateOfEvent);
        $smartcardBeneficiary ?: $smartcardBeneficiary = $this->createSmartcardForBeneficiary($serialNumber, $beneficiary, $dateOfEvent);
        $this->activateSmartcardAndDisableOthers($smartcardBeneficiary);

        return $smartcardBeneficiary;
    }

    private function getSmartcardForBeneficiaryBySerialNumber(
        string $serialNumber,
        Beneficiary $beneficiary,
        DateTimeInterface $dateOfEvent
    ): ?SmartcardBeneficiary {
        $smartcardBeneficiary = $this->smartcardBeneficiaryRepository->findBySerialNumberAndBeneficiary($serialNumber, $beneficiary);
        if ($smartcardBeneficiary) {
            $this->checkAndMarkDisabledSmartcardAsSuspicious($smartcardBeneficiary, $dateOfEvent);

            return $smartcardBeneficiary;
        }

        return null;
    }

    private function createSmartcardForBeneficiary(
        string $serialNumber,
        Beneficiary $beneficiary,
        DateTimeInterface $dateOfEvent
    ): SmartcardBeneficiary {
        $this->smartcardBeneficiaryRepository->disableBySerialNumber($serialNumber, SmartcardStates::REUSED, $dateOfEvent);
        $smartcardBeneficiary = new SmartcardBeneficiary($serialNumber, $dateOfEvent);
        $smartcardBeneficiary->setBeneficiary($beneficiary);
        $smartcardBeneficiary->setSuspicious(true, "Smartcard made adhoc");
        $this->smartcardBeneficiaryRepository->persist($smartcardBeneficiary);

        return $smartcardBeneficiary;
    }

    private function checkAndMarkDisabledSmartcardAsSuspicious(
        SmartcardBeneficiary $smartcardBeneficiary,
        DateTimeInterface $dateOfEvent
    ): void {
        $eventWasBeforeDisable = $smartcardBeneficiary->getDisabledAt()
            && $smartcardBeneficiary->getDisabledAt()->getTimestamp() > $dateOfEvent->getTimestamp();

        if (SmartcardStates::ACTIVE !== $smartcardBeneficiary->getState() && !$eventWasBeforeDisable) {
            $smartcardBeneficiary->setSuspicious(true, "Using disabled card");
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

    public function setMissingCurrencyToSmartcardAndPurchases(SmartcardBeneficiary $smartcardBeneficiary, ReliefPackage $reliefPackage): void
    {
        $this->setMissingCurrencyToSmartcard($smartcardBeneficiary, $reliefPackage);
        $this->setMissingCurrencyToPurchases($smartcardBeneficiary);
        $this->smartcardBeneficiaryRepository->save($smartcardBeneficiary);
    }

    private function setMissingCurrencyToSmartcard(SmartcardBeneficiary $smartcardBeneficiary, ReliefPackage $reliefPackage): void
    {
        if (null === $smartcardBeneficiary->getCurrency()) {
            $smartcardBeneficiary->setCurrency(SmartcardService::findCurrency($reliefPackage->getAssistanceBeneficiary()));
        }
    }

    private function setMissingCurrencyToPurchases(SmartcardBeneficiary $smartcardBeneficiary): void
    {
        foreach ($smartcardBeneficiary->getPurchases() as $purchase) {
            foreach ($purchase->getRecords() as $record) {
                if (null === $record->getCurrency()) {
                    $record->setCurrency($smartcardBeneficiary->getCurrency());
                    $this->smartcardBeneficiaryRepository->persist($record);
                }
            }
        }
    }

    public function getSmartcardByCode(string $smartcardCode): SmartcardBeneficiary
    {
        $smartcardBeneficiary = $this->smartcardBeneficiaryRepository->findOneBy(['serialNumber' => $smartcardCode]);

        if (!$smartcardBeneficiary) {
            throw new NotFoundHttpException("Card with code '{$smartcardCode}' does not exists");
        }

        return $smartcardBeneficiary;
    }

    private function activateSmartcardAndDisableOthers(SmartcardBeneficiary $smartcardForActivation): void
    {
        if (!$smartcardForActivation->getBeneficiary()) {
            throw new LogicException(
                "Smartcard must have assigned Beneficiary at this point. SmartcardId: {$smartcardForActivation->getId()}"
            );
        }
        $smartcardForActivation->setState(SmartcardStates::ACTIVE);

        $activatedSmartcardsByBeneficiary = $this->smartcardBeneficiaryRepository->findBy(
            ['beneficiary' => $smartcardForActivation->getBeneficiary(), 'state' => SmartcardStates::ACTIVE]
        );
        foreach ($activatedSmartcardsByBeneficiary as $smartcardBnf) {
            if ($smartcardForActivation->getId() !== $smartcardBnf->getId()) {
                $this->smartcardBeneficiaryRepository->disable($smartcardBnf);
            }
        }
    }
}
