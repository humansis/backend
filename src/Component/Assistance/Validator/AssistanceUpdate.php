<?php

declare(strict_types=1);

namespace Component\Assistance\Validator;

use Entity\Assistance;
use Symfony\Component\Validator\Constraint;

class AssistanceUpdate extends Constraint
{
    private Assistance $assistance;

    public function __construct(Assistance $assistance)
    {
        parent::__construct();

        $this->assistance = $assistance;
    }

    public function getAssistance(): Assistance
    {
        return $this->assistance;
    }
}
