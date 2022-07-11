<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\Assistance\Scoring;

use BeneficiaryBundle\Model\Vulnerability\Resolver as OldResolver;
use BeneficiaryBundle\Repository\BeneficiaryRepository;
use DistributionBundle\DTO\VulnerabilityScore;
use NewApiBundle\Component\Assistance\Scoring\Model\Factory\ScoringFactory;
use NewApiBundle\Component\Assistance\Scoring\ScoringResolver;
use NewApiBundle\InputType\VulnerabilityScoreInputType;
use NewApiBundle\Repository\ScoringBlueprintRepository;

final class ScoringService
{
    /**
     * @var ScoringResolver
     */
    private $resolver;

    /**
     * @var OldResolver
     */
    private $oldResolver;

    /**
     * @var ScoringFactory
     */
    private $scoringFactory;

    /**
     * @var BeneficiaryRepository
     */
    private $beneficiaryRepository;

    /**
     * @var ScoringBlueprintRepository
     */
    private $scoringBlueprintRepository;

    public function __construct(
        ScoringResolver $resolver,
        OldResolver $oldResolver,
        ScoringFactory $scoringFactory,
        BeneficiaryRepository $beneficiaryRepository,
        ScoringBlueprintRepository $scoringBlueprintRepository
    )
    {
        $this->resolver = $resolver;
        $this->oldResolver = $oldResolver;
        $this->scoringFactory = $scoringFactory;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->scoringBlueprintRepository = $scoringBlueprintRepository;
    }

    /**
     * @param VulnerabilityScoreInputType $input
     * @param string $countryCode
     *
     * @return VulnerabilityScore[]
     *
     * @throws \BeneficiaryBundle\Exception\CsvParserException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function computeTotalScore(VulnerabilityScoreInputType $input, string $countryCode): iterable
    {
        $scores = [];

        foreach ($input->getBeneficiaryIds() as $beneficiaryId) {
            $beneficiary = $this->beneficiaryRepository->find($beneficiaryId);

            $scoringBlueprint = $this->scoringBlueprintRepository->findActive($input->getScoringBlueprint(), $countryCode);
            if (!isset($scoringBlueprint)) {
                $protocol = $this->oldResolver->compute($beneficiary->getHousehold(), $countryCode, $input->getSector());
            } else {
                $protocol = $this->resolver->compute(
                    $beneficiary->getHousehold(),
                    $this->scoringFactory->buildScoring($scoringBlueprint),
                    $countryCode
                );
            }

            if (!is_null($input->getThreshold()) && $protocol->getTotalScore() < $input->getThreshold()) {
                continue;
            }

            $scores[] = new VulnerabilityScore($beneficiary, $protocol);
        }
        
        return $scores;
    }
}
