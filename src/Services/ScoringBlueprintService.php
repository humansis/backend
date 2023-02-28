<?php

declare(strict_types=1);

namespace Services;

use Doctrine\ORM\EntityManagerInterface;
use Component\Assistance\Scoring\Exception\ScoreValidationException;
use Entity\ScoringBlueprint;
use Exception\CsvParserException;
use InputType\Assistance\Scoring\ScoringService;
use InputType\ScoringInputType;
use InputType\ScoringPatchInputType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Utils\UserService;

class ScoringBlueprintService
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly UserService $userService, private readonly ScoringService $scoringService)
    {
    }

    /**
     * @throws CsvParserException
     * @throws ScoreValidationException
     */
    public function create(ScoringInputType $scoringInput, string $iso3): ScoringBlueprint
    {
        $this->scoringService->validateScoring($scoringInput->getName(), $scoringInput->getContent());
        $scoringBlueprint = new ScoringBlueprint();
        $scoringBlueprint->setArchived(false)
            ->setName($scoringInput->getName())
            ->setContent($scoringInput->getContent())
            ->setCreatedBy($this->userService->getCurrentUser())
            ->setCountryIso3($iso3);
        $this->em->persist($scoringBlueprint);
        $this->em->flush();

        return $scoringBlueprint;
    }

    public function patch(ScoringPatchInputType $scoringInput, ScoringBlueprint $blueprint): void
    {
        $blueprint->setValues($scoringInput->getFilledValues());
        $this->em->persist($blueprint);
        $this->em->flush();
    }

    public function archive(ScoringBlueprint $scoring): void
    {
        if ($scoring->isArchived()) {
            throw new BadRequestHttpException("Scoring '{$scoring->getName()}' is already archived.");
        }
        $scoring->setArchived(true);
        $this->em->persist($scoring);
        $this->em->flush();
    }
}
