<?php

declare(strict_types=1);

namespace InputType;

use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"CommodityOfflineFilterInputType", "Strict"})
 */
class CommodityOfflineFilterInputType extends AbstractFilterInputType
{
    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("string", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $notModalityTypes;

    public function hasNotModalityTypes(): bool
    {
        return $this->has('notModalityTypes');
    }

    public function getNotModalityTypes(): array
    {
        return $this->notModalityTypes;
    }
}
