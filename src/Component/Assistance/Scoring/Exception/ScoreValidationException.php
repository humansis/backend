<?php

declare(strict_types=1);

namespace Component\Assistance\Scoring\Exception;

use Exception;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ScoreValidationException extends Exception
{
    public function __construct(string $scoringBlueprintName, ConstraintViolationListInterface $violationList)
    {
        $message = "Scoring $scoringBlueprintName could not be loaded because some values in source CSV are not correct: ";

        /** @var ConstraintViolationInterface $violation */
        foreach ($violationList as $violation) {
            $message .= $violation->getMessage() . ', ';
        }

        parent::__construct($message);
    }
}
