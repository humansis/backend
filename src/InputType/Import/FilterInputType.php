<?php

declare(strict_types=1);

namespace InputType\Import;

use InputType\FilterFragment\FulltextFilterTrait;
use InputType\FilterFragment\ProjectFilterTrait;
use Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['FilterInputType', 'Strict'])]
class FilterInputType extends AbstractFilterInputType
{
    use FulltextFilterTrait;
    use ProjectFilterTrait;

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"Enum\ImportState", "values"})
     *     },
     *     groups={"Strict"}
     * )
     */
    #[Assert\Type('array')]
    protected $status;

    public function hasStatus(): bool
    {
        return $this->has('status');
    }

    /**
     * @return array
     */
    public function getStatus()
    {
        return $this->status;
    }
}
