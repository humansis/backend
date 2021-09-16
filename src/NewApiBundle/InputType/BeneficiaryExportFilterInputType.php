<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\InputType\FilterFragment\PrimaryIdFilterTrait;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"BeneficiaryExportFilterInputType", "Strict"})
 */
class BeneficiaryExportFilterInputType extends AbstractFilterInputType
{
    use PrimaryIdFilterTrait;
}
