<?php

namespace NewApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ScoringBlueprint;
use NewApiBundle\InputType\ScoringInputType;
use NewApiBundle\Repository\ScoringBlueprintRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UserBundle\Utils\UserService;

class ScoringBlueprintService
{


    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * @var ScoringBlueprintRepository $scoringBlueprintRepository
     */
    private $scoringBlueprintRepository;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @param ScoringBlueprintRepository $scoringBlueprintRepository
     */
    public function __construct(EntityManagerInterface  $em, ScoringBlueprintRepository $scoringBlueprintRepository, UserService $userService)
    {
        $this->scoringBlueprintRepository = $scoringBlueprintRepository;
        $this->userService = $userService;
        $this->em = $em;
    }

    public function create(ScoringInputType $scoringInput, $iso3): ScoringBlueprint
    {
        $scoring = new ScoringBlueprint();
        $scoring->setArchived(0)
            ->setName($scoringInput->getName())
            ->setContent($scoringInput->getContent())
            ->setCreatedBy($this->userService->getCurrentUser())
            ->setCountryIso3($iso3);
        $this->em->persist($scoring);
        $this->em->flush();
        return $scoring;
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
