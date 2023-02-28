<?php

declare(strict_types=1);

namespace InputType;

use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

class AssistanceTargetFilterInputType extends AbstractFilterInputType
{
    /**
     * @var string
     */
    #[Assert\Choice(callback: [\Enum\AssistanceType::class, 'values'])]
    protected $type;

    public function getType(): string
    {
        return $this->type;
    }

    public function hasType(): bool
    {
        return $this->has('type');
    }
}
