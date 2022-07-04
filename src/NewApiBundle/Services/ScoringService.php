<?php

namespace NewApiBundle\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\Scoring;
use NewApiBundle\InputType\ScoringInputType;
use NewApiBundle\Repository\ScoringRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Security;
use UserBundle\Utils\UserService;

class ScoringService
{


    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * @var ScoringRepository $scoringRepository
     */
    private $scoringRepository;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @param ScoringRepository $scoringRepository
     */
    public function __construct(EntityManagerInterface  $em, ScoringRepository $scoringRepository, UserService $userService)
    {
        $this->scoringRepository = $scoringRepository;
        $this->userService = $userService;
        $this->em = $em;
    }

    public function create(ScoringInputType $scoringInput, $iso3): Scoring
    {
        $scoring = new Scoring();
        $scoring->setArchived(0)
            ->setName($scoringInput->getName())
            ->setContent($scoringInput->getContent())
            ->setCreatedBy($this->userService->getCurrentUser())
            ->setCountryIso3($iso3);
        $this->em->persist($scoring);
        $this->em->flush();
        return $scoring;
    }

    public function archive(Scoring $scoring)
    {
        if ($scoring->isArchived()) {
            throw new BadRequestHttpException("Scoring '{$scoring->getName()}' is already archived.");
        }
        $scoring->setArchived(true);
        $this->em->persist($scoring);
        $this->em->flush();
    }

}
