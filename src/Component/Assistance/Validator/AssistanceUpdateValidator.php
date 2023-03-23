<?php

declare(strict_types=1);

namespace Component\Assistance\Validator;

use Doctrine\ORM\EntityNotFoundException;
use Entity\Assistance;
use Enum\AssistanceState;
use InputType\Assistance\UpdateAssistanceInputType;
use Repository\LocationRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class AssistanceUpdateValidator extends ConstraintValidator
{
    private Assistance $assistance;

    private UpdateAssistanceInputType $inputType;

    public function __construct(
        private readonly LocationRepository $locationRepository,
    ) {
    }

    /**
     * @throws EntityNotFoundException
     */
    public function validate(mixed $value, Constraint $constraint)
    {
        if (!$value instanceof UpdateAssistanceInputType) {
            throw new UnexpectedTypeException($value, UpdateAssistanceInputType::class);
        }

        if (!$constraint instanceof AssistanceUpdate) {
            throw new UnexpectedTypeException($constraint, AssistanceUpdate::class);
        }

        $this->assistance = $constraint->getAssistance();
        $this->inputType = $value;

        $this->checkName();
        $this->checkLocation();
        $this->checkDateOfAssistance();
        $this->checkRound();
    }

    private function checkName(): void
    {
        if (!$this->inputType->hasName()) {
            return;
        }

        if (!in_array($this->assistance->getState(), [AssistanceState::NEW, AssistanceState::CLOSED])) {
            $this->context->buildViolation('Name of assistance can be changed only for new or closed assistance.')
                ->atPath('name')
                ->addViolation();
        }
    }

    /**
     * @throws EntityNotFoundException
     */
    private function checkLocation(): void
    {
        if (!$this->inputType->hasLocationId()) {
            return;
        }

        $location = $this->locationRepository->getById($this->inputType->getLocationId());

        if (!in_array($this->assistance->getState(), [AssistanceState::NEW, AssistanceState::CLOSED])) {
            $this->context->buildViolation('Location of assistance can be changed only for new or closed assistance.')
                ->atPath('locationId')
                ->addViolation();

            return;
        }

        if ($location->getLvl() < 3) {
            $this->context->buildViolation('Location must be ADM 3 or ADM 4. You can not update ADM1 or ADM 2 of created assistance.')
                ->atPath('locationId')
                ->addViolation();

            return;
        }

        if ($this->assistance->getLocation()->getAdm2Id() !== null && $location->getAdm2Id() !== $this->assistance->getLocation()->getAdm2Id()) {
            $this->context->buildViolation('Location must be in the same ADM 2 as current assistance location.')
                ->atPath('locationId')
                ->addViolation();
        }

        if ($location->getAdm1Id() !== $this->assistance->getLocation()->getAdm1Id()) {
            $this->context->buildViolation('Location must be in the same ADM 1 as current assistance location.')
                ->atPath('locationId')
                ->addViolation();
        }
    }

    private function checkDateOfAssistance(): void
    {
        if (!$this->inputType->hasDateDistribution()) {
            return;
        }

        if ($this->assistance->getState() != AssistanceState::NEW) {
            $this->context->buildViolation('Date of assistance can be changed only for new assistance.')
                ->atPath('dateOfAssistance')
                ->addViolation();
        }
    }

    private function checkRound(): void
    {
        if (!$this->inputType->hasRound()) {
            return;
        }

        if (!in_array($this->assistance->getState(), [AssistanceState::NEW, AssistanceState::CLOSED])) {
            $this->context->buildViolation('Round can be changed only for new or closed assistance.')
                ->atPath('round')
                ->addViolation();
        }
    }
}
