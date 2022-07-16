<?php
declare(strict_types=1);

namespace NewApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Assistance\Scoring\Exception\ScoreValidationException;
use NewApiBundle\Entity\ScoringBlueprint;
use NewApiBundle\Exception\CsvParserException;
use NewApiBundle\InputType\Assistance\Scoring\ScoringService;
use NewApiBundle\InputType\ScoringInputType;
use NewApiBundle\InputType\ScoringPatchInputType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UserBundle\Utils\UserService;

class ScoringBlueprintService
{


    /** @var EntityManagerInterface $em */
    private $em;


    /** @var UserService */
    private $userService;

    /** @var ScoringService */
    private $scoringService;

    /**
     * @param EntityManagerInterface $em
     * @param UserService            $userService
     * @param ScoringService         $scoringService
     */
    public function __construct(
        EntityManagerInterface  $em,
        UserService $userService,
        ScoringService $scoringService
    )
    {
        $this->userService = $userService;
        $this->em = $em;
        $this->scoringService = $scoringService;
    }

    /**
     * @param ScoringInputType $scoringInput
     * @param string           $iso3
     *
     * @return ScoringBlueprint
     * @throws CsvParserException
     * @throws ScoreValidationException
     */
    public function create(ScoringInputType $scoringInput,string $iso3): ScoringBlueprint
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

    /**
     * @param ScoringPatchInputType $scoringInput
     * @param ScoringBlueprint      $blueprint
     */
    public function patch(ScoringPatchInputType $scoringInput, ScoringBlueprint $blueprint): void
    {
        $blueprint->setValues($scoringInput->getFilledValues());
        $this->em->persist($blueprint);
        $this->em->flush();
    }

    /**
     * @param ScoringBlueprint $scoring
     */
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
