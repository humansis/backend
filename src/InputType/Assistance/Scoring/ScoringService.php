<?php

declare(strict_types=1);

namespace InputType\Assistance\Scoring;

use Component\Assistance\Scoring\Model\ScoringProtocol;
use Exception\CsvParserException;
use Repository\BeneficiaryRepository;
use DTO\VulnerabilityScore;
use Component\Assistance\Scoring\Exception\ScoreValidationException;
use Component\Assistance\Scoring\Model\Factory\ScoringFactory;
use Component\Assistance\Scoring\ScoringCsvParser;
use Component\Assistance\Scoring\ScoringResolver;
use InputType\VulnerabilityScoreInputType;
use Repository\ScoringBlueprintRepository;
use Utils\Floats;

final class ScoringService
{
    /**
     * @var ScoringResolver
     */
    private $resolver;

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

    /**
     * @var ScoringCsvParser
     */
    private $parser;

    public function __construct(
        ScoringResolver $resolver,
        ScoringFactory $scoringFactory,
        BeneficiaryRepository $beneficiaryRepository,
        ScoringBlueprintRepository $scoringBlueprintRepository
    ) {
        $this->resolver = $resolver;
        $this->scoringFactory = $scoringFactory;
        $this->beneficiaryRepository = $beneficiaryRepository;
        $this->scoringBlueprintRepository = $scoringBlueprintRepository;
        $this->parser = new ScoringCsvParser();
    }

    /**
     * @param VulnerabilityScoreInputType $input
     * @param string $countryCode
     *
     * @return VulnerabilityScore[]
     *
     * @throws CsvParserException
     */
    public function computeTotalScore(VulnerabilityScoreInputType $input, string $countryCode): iterable
    {
        $scores = [];

        $scoringBlueprint = $this->scoringBlueprintRepository->findActive(
            $input->getScoringBlueprintId(),
            $countryCode
        );

        if (is_null($scoringBlueprint)) {
            $scoringBlueprint = $this->scoringBlueprintRepository->findFirstInCountry($countryCode);
        }

        $scoring = isset($scoringBlueprint) ? $this->scoringFactory->buildScoring($scoringBlueprint) : null;
        foreach ($input->getBeneficiaryIds() as $beneficiaryId) {
            $beneficiary = $this->beneficiaryRepository->find($beneficiaryId);
            if (!isset($scoring)) {
                $protocol = new ScoringProtocol();
                $protocol->addScore('test', 99);
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
     * @param string $name
     * @param string $csv
     *
     * @return bool
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
