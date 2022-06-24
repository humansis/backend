<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\Assistance\Scoring;

use BeneficiaryBundle\Model\Vulnerability\Resolver;
use BeneficiaryBundle\Repository\HouseholdRepository;
use DistributionBundle\DTO\VulnerabilityScore;
use NewApiBundle\InputType\VulnerabilityScoreInputType;

final class ScoringService
{
    /** @var HouseholdRepository */
    private $householdRepository;

    public function __construct(Resolver $resolver, HouseholdRepository $householdRepository)
    {
        $this->resolver = $resolver;
        $this->householdRepository = $householdRepository;
    }

    public function computeTotalScore(VulnerabilityScoreInputType $vulnerabilityScoreInputType, string $countryCode): iterable
    {
        foreach ($vulnerabilityScoreInputType->getHouseholdIds() as $householdId) {
            $household = $this->householdRepository->find($householdId);

            //TODO compute protocol using resolver
            yield new VulnerabilityScore($household, ['totalScore' => $protocol->getTotalScore()]);
        }
    }
}
