<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\Assistance\Scoring;

use BeneficiaryBundle\Model\Vulnerability\Resolver as OldResolver;
use BeneficiaryBundle\Repository\HouseholdRepository;
use DistributionBundle\DTO\VulnerabilityScore;
use NewApiBundle\Component\Assistance\Scoring\Model\Factory\ScoringFactory;
use NewApiBundle\Component\Assistance\Scoring\Resolver;
use NewApiBundle\InputType\VulnerabilityScoreInputType;

final class ScoringService
{
    /** @var HouseholdRepository */
    private $householdRepository;

    public function __construct(
        Resolver $resolver,
        OldResolver $oldResolver,
        HouseholdRepository $householdRepository,
        ScoringFactory $scoringFactory
    )
    {
        $this->resolver = $resolver;
        $this->oldResolver = $oldResolver;
        $this->householdRepository = $householdRepository;
        $this->scoringFactory = $scoringFactory;
    }

    /**
     * @param VulnerabilityScoreInputType $input
     * @param string $countryCode
     * @return iterable
     * @throws \BeneficiaryBundle\Exception\CsvParserException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function computeTotalScore(VulnerabilityScoreInputType $input, string $countryCode): iterable
    {
        $households = $this->householdRepository->findAllByHeadIds($input->getBeneficiaryIds());
        
        $scores = [];
        foreach ($households as $household) {
            
            $protocol = ($countryCode === 'UKR')
                ? $this->resolver->compute(
                    $household,
                    $this->scoringFactory->buildScoring($input->getScoringType()),
                    $countryCode
                  )
                //remove when SYR scoring is refactored as default
                : $this->oldResolver->compute($household, $countryCode, $input->getSector());

            $scores[] = new VulnerabilityScore($household, ['totalScore' => $protocol->getTotalScore()]);
        }
        
        return $scores;
    }
}
