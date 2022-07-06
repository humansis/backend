<?php

namespace NewApiBundle\Services;

use BeneficiaryBundle\Exception\CsvParserException;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Component\Assistance\Scoring\Model\Factory\ScoringFactory;
use NewApiBundle\Entity\ScoringBlueprint;
use NewApiBundle\InputType\ScoringInputType;
use NewApiBundle\InputType\ScoringPatchInputType;
use NewApiBundle\Repository\ScoringBlueprintRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UserBundle\Utils\UserService;

class ScoringBlueprintService
{


    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ScoringBlueprintRepository $scoringBlueprintRepository */
    private $scoringBlueprintRepository;

    /** @var UserService */
    private $userService;

    /** @var ScoringFactory */
    private $scoringFactory;

    /**
     * @param ScoringBlueprintRepository $scoringBlueprintRepository
     */
    public function __construct(
        EntityManagerInterface  $em,
        ScoringBlueprintRepository $scoringBlueprintRepository,
        UserService $userService,
        ScoringFactory $scoringFactory
    )
    {
        $this->scoringBlueprintRepository = $scoringBlueprintRepository;
        $this->userService = $userService;
        $this->em = $em;
        $this->scoringFactory = $scoringFactory;
    }

    /**
     * @param ScoringInputType $scoringInput
     * @param                  $iso3
     *
     * @return ScoringBlueprint
     * @throws CsvParserException
     * @throws \NewApiBundle\Component\Assistance\Scoring\Exception\ScoreValidationException
     */
    public function create(ScoringInputType $scoringInput, $iso3): ScoringBlueprint
    {
            $this->scoringFactory->validateScoring($scoringInput->getName(), $scoringInput->getContent());
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

    public function patch(ScoringPatchInputType $scoringInput, ScoringBlueprint $blueprint)
    {
        $blueprint->setValues($scoringInput->getFilledValues());
        $this->em->persist($blueprint);
        $this->em->flush();
    }

    public function archive(ScoringBlueprint $scoring)
    {
        if ($scoring->isArchived()) {
            throw new BadRequestHttpException("Scoring '{$scoring->getName()}' is already archived.");
        }
        $scoring->setArchived(true);
        $this->em->persist($scoring);
        $this->em->flush();
    }

}
