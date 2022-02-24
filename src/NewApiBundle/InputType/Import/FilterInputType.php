<?php declare(strict_types=1);

namespace NewApiBundle\InputType\Import;

use NewApiBundle\InputType\FilterFragment\FulltextFilterTrait;
use NewApiBundle\InputType\FilterFragment\ProjectFilterTrait;
use NewApiBundle\Request\FilterInputType\AbstractFilterInputType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"FilterInputType", "Strict"})
 */
class FilterInputType extends AbstractFilterInputType
{
    use FulltextFilterTrait;
    use ProjectFilterTrait;

    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Choice(callback={"NewApiBundle\Enum\ImportState", "values"})
     *     },
     *     groups={"Strict"}
     * )
     */
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