<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\FilterFragment;
use Symfony\Component\Validator\Constraints as Assert;

trait FulltextFilterTrait
{
    /**
     * @var string
     * @Assert\Type("scalar")
     */
    protected $fulltext;

    public function hasFulltext(): bool
    {
        return $this->has('fulltext');
    }

    public function getFulltext()
    {
        return $this->fulltext;
    }
}
