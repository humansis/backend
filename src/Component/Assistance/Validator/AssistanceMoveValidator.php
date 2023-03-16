<?php

declare(strict_types=1);

namespace Component\Assistance\Validator;

use Entity\Assistance;
use Entity\Project;
use Enum\AssistanceState;
use Repository\ProjectRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class AssistanceMoveValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof Assistance) {
            throw new UnexpectedTypeException($constraint, Assistance::class);
        }

        if (!$constraint instanceof AssistanceMove) {
            throw new UnexpectedTypeException($constraint, AssistanceMove::class);
        }

        $originalProjectId = $constraint->moveAssistanceInputType->getOriginalProjectId();
        $targetProjectId = $constraint->moveAssistanceInputType->getTargetProjectId();

        $currentProject = $value->getProject();
        $targetProject = $this->projectRepository->find($targetProjectId);

        $this->checkOriginalProjectIsValid($value, $originalProjectId);
        $this->checkAssistanceWithinProjectDates($value, $targetProject);
        $this->checkTargetProjectContainsAssistanceSectors($value, $targetProject);
        $this->checkTargetProjectContainsSameCategoryTypes($currentProject, $targetProject);
        $this->checkTargetProjectFromSameCountry($currentProject, $targetProject);
        $this->checkAssistanceAllowedState($value);
    }

    private function checkOriginalProjectIsValid(Assistance $assistance, int $originalProjectId): void
    {
        $currentProjectId = $assistance->getProject()->getId();

        if ($currentProjectId !== $originalProjectId) {
            $this->context->buildViolation(
                'Assistance is not in current project anymore, currently in project with ID {{ currentProjectId }}, please refresh the page.'
            )
                ->setParameter('{{ originalProjectId }}', (string) $originalProjectId)
                ->setParameter('{{ currentProjectId }}', (string) $currentProjectId)
                ->addViolation();
        }
    }

    private function checkAssistanceWithinProjectDates(Assistance $assistance, Project $targetProject): void
    {
        $assistanceDate = $assistance->getDateDistribution();
        $projectStartDate = $targetProject->getStartDate();
        $projectEndDate = $targetProject->getEndDate();

        if ($projectStartDate > $assistanceDate || $projectEndDate < $assistanceDate) {
            $this->context->buildViolation(
                'Assistance date {{ assistanceDate }} must match with target project dates (start: {{ projectStartDate }},  end {{ projectEndDate }}).'
            )
                ->setParameter('{{ assistanceDate }}', $assistanceDate->format('Y-m-d'))
                ->setParameter('{{ projectStartDate }}', $projectStartDate->format('Y-m-d'))
                ->setParameter('{{ projectEndDate }}', $projectEndDate->format('Y-m-d'))
                ->addViolation();
        }
    }

    private function checkTargetProjectContainsAssistanceSectors(
        Assistance $assistance,
        Project $targetProject,
    ): void {
        $doesProjectContainNeededSector = false;
        $neededSector = $assistance->getSector();
        $targetProjectSectors = $targetProject->getSectors();

        foreach ($targetProjectSectors as $sector) {
            if ($sector->getName() === $neededSector) {
                $doesProjectContainNeededSector = true;
                break;
            }
        }

        if (!$doesProjectContainNeededSector) {
            $this->context->buildViolation(
                'Target project is missing assistance sector: {{ neededSector }}.'
            )
                ->setParameter('{{ neededSector }}', $neededSector)
                ->addViolation();
        }
    }

    private function checkTargetProjectContainsSameCategoryTypes(
        Project $originalProject,
        Project $targetProject,
    ): void {
        $originalAllowedProductCategoryTypes = $originalProject->getAllowedProductCategoryTypes();
        $targetAllowedProductCategoryTypes = $targetProject->getAllowedProductCategoryTypes();

        $requiredTypes = [];
        foreach ($originalAllowedProductCategoryTypes as $productCategoryType) {
            if (!in_array($productCategoryType, $targetAllowedProductCategoryTypes)) {
                $requiredTypes[] = $productCategoryType;
            }
        }

        if (count($requiredTypes) > 0) {
            $this->context->buildViolation(
                'Target project does not allow product required category types: {{ missingProductCategoryTypes }}.'
            )
                ->setParameter('{{ missingProductCategoryTypes }}', join(', ', $requiredTypes))
                ->addViolation();
        }
    }

    private function checkTargetProjectFromSameCountry(Project $currentProject, Project $targetProject): void
    {
        $originalCountry = $currentProject->getCountryIso3();
        $targetCountry = $targetProject->getCountryIso3();

        if ($originalCountry !== $targetCountry) {
            $this->context->buildViolation(
                'Target project country ({{ targetProjectCountry }}) differs from original project country ({{ originalProjectCountry }}).'
            )
                ->setParameter('{{ targetProjectCountry }}', $targetCountry)
                ->setParameter('{{ originalProjectCountry }}', $originalCountry)
                ->addViolation();
        }
    }

    private function checkAssistanceAllowedState(Assistance $assistance): void
    {
        if ($assistance->getState() !== AssistanceState::NEW && $assistance->getState() !== AssistanceState::CLOSED) {
            $this->context->buildViolation(
                'Only NEW or CLOSED assistances can be moved.'
            )
                ->addViolation();
        }
    }
}
