<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Assistance\Scoring\Model\Factory;

use BeneficiaryBundle\Exception\CsvParserException;
use NewApiBundle\Component\Assistance\Scoring\Exception\ScoreValidationException;
use NewApiBundle\Component\Assistance\Scoring\Model\Scoring;
use NewApiBundle\Component\Assistance\Scoring\ScoringCsvParser;
use NewApiBundle\Entity\ScoringBlueprint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Builds Scoring from given scoring type
 *
 * @package NewApiBundle\Component\Assistance\Scoring\Model\Factory
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
        //print_r(var_dump($scoringBlueprint->getName()));
        //die(var_dump(is_resource($scoringBlueprint->getContent())));
        //die(var_dump(fgetcsv($scoringBlueprint->getContent())));
        $scoringRules = $this->parser->parseStream($scoringBlueprint->getContent());
        $scoring = new Scoring($scoringBlueprint->getName(), $scoringRules);
        $violations = $this->validator->validate($scoring);
        if ($violations->count() === 0) {
            return $scoring;
        }
        throw new ScoreValidationException($scoringBlueprint->getName(), $violations);
    }
}
