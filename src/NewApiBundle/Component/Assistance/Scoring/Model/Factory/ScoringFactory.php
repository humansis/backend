<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Model\Factory;

use BeneficiaryBundle\Exception\CsvParserException;
use NewApiBundle\Component\Assistance\Scoring\Exception\ScoreValidationException;
use NewApiBundle\Component\Assistance\Scoring\Model\Scoring;
use NewApiBundle\Component\Assistance\Scoring\Parser;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Builds Scoring from given scoring type
 *
 * @package NewApiBundle\Component\Assistance\Scoring\Model\Factory
 */
final class ScoringFactory
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var array
     */
    private $scoringConfigurations;

    /** @var ValidatorInterface */
    private $validator;

    public function __construct(array $scoringConfigurations, ValidatorInterface $validator)
    {
        $this->parser = new Parser();
        $this->scoringConfigurations = $scoringConfigurations;
        $this->validator = $validator;
    }

    /**
     * @param string $scoringType
     *
     * @return Scoring
     *
     * @throws CsvParserException
     * @throws \Exception
     */
    public function getScoring(string $scoringType): Scoring
    {
        /** @var string|null $csvPath */
        $csvPath = null;

        foreach ($this->scoringConfigurations as $configuration) {
            if ($configuration['name'] === $scoringType) {
                $csvPath = $configuration['csvFile'];
            }
        }

        if (is_null($csvPath)) {
            throw new Exception("Scoring with name $scoringType was not found in configuration");
        }

        $scoringRules = $this->parser->parse($csvPath);

        $scoring = new Scoring($scoringType, $scoringRules);

        $violations = $this->validator->validate($scoring);

        if ($violations->count() === 0) {
            return $scoring;
        }

        throw new ScoreValidationException($scoringType, $violations);
    }
}
