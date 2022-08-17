<?php
declare(strict_types=1);

namespace InputType;

use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class AssistanceTargetFilterInputType extends AbstractFilterInputType
{
    /**
     * @var string
     * @Assert\Choice(callback={"Enum\AssistanceType", "values"})
     */
    protected $type;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function hasType(): bool
    {
        return $this->has('type');
    }
}
