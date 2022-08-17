<?php
declare(strict_types=1);

namespace Component\Assistance\Scoring\Model\Factory;

use Exception\CsvParserException;
use Component\Assistance\Scoring\Exception\ScoreValidationException;
use Component\Assistance\Scoring\Model\Scoring;
use Component\Assistance\Scoring\Model\ScoringRule;
use Component\Assistance\Scoring\ScoringCsvParser;
use Entity\ScoringBlueprint;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Builds Scoring from given scoring type
 *
 * @package Component\Assistance\Scoring\Model\Factory
 */
final class ScoringFactory
{
    /**
     * @var ScoringCsvParser
     */
    private $parser;


    /** @var ValidatorInterface */
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->parser = new ScoringCsvParser();
        $this->validator = $validator;
    }

    /**
     * @param ScoringBlueprint $scoringBlueprint
     *
     * @return Scoring
     *
     * @throws CsvParserException
     * @throws \Exception
     */
    public function buildScoring(ScoringBlueprint $scoringBlueprint): Scoring
    {
        $scoringRules = $this->parser->parseStream($scoringBlueprint->getStream());
        return $this->createScoring($scoringBlueprint->getName(), $scoringRules);
    }

    /**
     * @param string        $name
     * @param ScoringRule[] $scoringRules
     *
     * @return Scoring
     * @throws ScoreValidationException
     */
    public function createScoring(string $name, array $scoringRules): Scoring
    {
        $scoring = new Scoring($name, $scoringRules);
        $violations = $this->validator->validate($scoring);
        if ($violations->count() === 0) {
            return $scoring;
        }
        throw new ScoreValidationException($name, $violations);
    }
}
