<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\Assistance\Scoring;

use BeneficiaryBundle\Model\Vulnerability\Resolver as OldResolver;
use BeneficiaryBundle\Repository\BeneficiaryRepository;
use DistributionBundle\DTO\VulnerabilityScore;
use NewApiBundle\Component\Assistance\Scoring\Model\Factory\ScoringFactory;
use NewApiBundle\Component\Assistance\Scoring\Resolver;
use NewApiBundle\InputType\VulnerabilityScoreInputType;

final class ScoringService
{
    /**
     * @var Resolver
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

    public function __construct(
        Resolver $resolver,
        OldResolver $oldResolver,
        ScoringFactory $scoringFactory,
        BeneficiaryRepository $beneficiaryRepository
    )
    {
        $this->resolver = $resolver;
        $this->oldResolver = $oldResolver;
        $this->scoringFactory = $scoringFactory;
        $this->beneficiaryRepository = $beneficiaryRepository;
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

            if ($input->getScoringType() === 'Default') {
                $protocol = $this->oldResolver->compute($beneficiary->getHousehold(), $countryCode, $input->getSector());
            } else {
                $protocol = $this->resolver->compute(
                    $beneficiary->getHousehold(),
                    $this->scoringFactory->buildScoring($input->getScoringType()),
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
