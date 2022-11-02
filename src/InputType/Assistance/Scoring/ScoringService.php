<?php

declare(strict_types=1);

namespace InputType\Assistance\Scoring;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception\CsvParserException;
use Model\Vulnerability\Resolver as OldResolver;
use Repository\BeneficiaryRepository;
use DTO\VulnerabilityScore;
use Component\Assistance\Scoring\Exception\ScoreValidationException;
use Component\Assistance\Scoring\Model\Factory\ScoringFactory;
use Component\Assistance\Scoring\ScoringCsvParser;
use Component\Assistance\Scoring\ScoringResolver;
use InputType\VulnerabilityScoreInputType;
use Repository\ScoringBlueprintRepository;

final class ScoringService
{
    private readonly \Component\Assistance\Scoring\ScoringCsvParser $parser;

    public function __construct(
        private readonly ScoringResolver $resolver,
        private readonly OldResolver $oldResolver,
        private readonly ScoringFactory $scoringFactory,
        private readonly BeneficiaryRepository $beneficiaryRepository,
        private readonly ScoringBlueprintRepository $scoringBlueprintRepository
    ) {
        $this->parser = new ScoringCsvParser();
    }

    /**
     *
     * @return VulnerabilityScore[]
     *
     * @throws CsvParserException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function computeTotalScore(VulnerabilityScoreInputType $input, string $countryCode): iterable
    {
        $scores = [];

        $scoringBlueprint = $this->scoringBlueprintRepository->findActive(
            $input->getScoringBlueprintId(),
            $countryCode
        );
        $scoring = isset($scoringBlueprint) ? $this->scoringFactory->buildScoring($scoringBlueprint) : null;
        foreach ($input->getBeneficiaryIds() as $beneficiaryId) {
            $beneficiary = $this->beneficiaryRepository->find($beneficiaryId);
            if (!isset($scoring)) {
                $protocol = $this->oldResolver->compute(
                    $beneficiary->getHousehold(),
                    $countryCode,
                    $input->getSector()
                );
            } else {
                $protocol = $this->resolver->compute(
                    $beneficiary->getHousehold(),
                    $scoring,
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

    /**
     * @throws CsvParserException
     * @throws ScoreValidationException
     */
    public function validateScoring(string $name, string $csv): bool
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csv);
        rewind($stream);
        $scoringRules = $this->parser->parseStream($stream);
        $this->scoringFactory->createScoring($name, $scoringRules);

        return true;
    }
}
