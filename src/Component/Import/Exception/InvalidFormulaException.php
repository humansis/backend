<?php

declare(strict_types=1);

namespace Component\Import\Exception;

use Exception;

class InvalidFormulaException extends Exception
{
    public function __construct(private string $formula, $message = "")
    {
        parent::__construct($message);
    }

    public function getFormula(): string
    {
        return $this->formula;
    }

    public function setFormula(string $formula): void
    {
        $this->formula = $formula;
    }
}
