<?php declare(strict_types=1);

namespace CommonBundle\DataFixtures\InputTypesGenerator;

use NewApiBundle\Enum\NationalIdType;
use NewApiBundle\InputType\Beneficiary\NationalIdCardInputType;
use NewApiBundle\Utils\ValueGenerator\ValueGenerator;

class NationalIdCardGenerator
{
    public static function generate(): NationalIdCardInputType
    {
        $nationalInputType = new NationalIdCardInputType();
        $nationalInputType->setNumber(ValueGenerator::string(10));
        $nationalInputType->setType(ValueGenerator::fromEnum(NationalIdType::class));

        return $nationalInputType;
    }
}
