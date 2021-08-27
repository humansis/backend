<?php

namespace BeneficiaryBundle\Enum;

use NewApiBundle\Enum\EnumKeyValueTransformationTrait;

class Vulnerabilities
{
    use EnumKeyValueTransformationTrait;

    public const CRITERION_DISABLED = 'disabled';
    public const CRITERION_SOLO_PARENT = 'soloParent';
    public const CRITERION_LACTATING = 'lactating';
    public const CRITERION_PREGNANT = 'pregnant';
    public const CRITERION_NUTRITIONAL_ISSUES = 'nutritionalIssues';
    public const CRITERION_CHRONICALLY_ILL = 'chronicallyIll';

    protected static $values = [
        self::CRITERION_DISABLED => 'Person with Disability',
        self::CRITERION_SOLO_PARENT => 'Solo Parent',
        self::CRITERION_LACTATING => 'Lactating',
        self::CRITERION_PREGNANT => 'Pregnant',
        self::CRITERION_NUTRITIONAL_ISSUES => 'Nutritional Issues',
        self::CRITERION_CHRONICALLY_ILL => 'Chronically Ill',
    ];

}
