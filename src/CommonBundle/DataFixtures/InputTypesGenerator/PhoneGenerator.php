<?php declare(strict_types=1);

namespace CommonBundle\DataFixtures\InputTypesGenerator;

use NewApiBundle\Enum\PhoneTypes;
use NewApiBundle\InputType\Beneficiary\PhoneInputType;
use NewApiBundle\Utils\ValueGenerator\ValueGenerator;

class PhoneGenerator
{
    public static function generate(): PhoneInputType
    {
        $phoneInputType = new PhoneInputType();
        $phoneInputType->setType(ValueGenerator::fromEnum(PhoneTypes::class));
        $phoneInputType->setNumber(ValueGenerator::int(100000000, 999999999));
        $phoneInputType->setPrefix((string) ValueGenerator::int(400, 500));

        return $phoneInputType;
    }

    public static function fromArray($phone): PhoneInputType
    {
        $phoneInputType = new PhoneInputType();
        $phoneInputType->setType($phone['phone_type']);
        $phoneInputType->setNumber($phone['phone_number']);
        $phoneInputType->setPrefix($phone['phone_prefix']);

        return $phoneInputType;
    }
}
