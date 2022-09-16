<?php
declare(strict_types=1);

namespace InputType\FilterFragment;
use Symfony\Component\Validator\Constraints as Assert;

trait PrimaryIdFilterTrait
{
    /**
     * @var int[]
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $id;

    /**
     * @return bool
     */
    public function hasIds(): bool
    {
        return $this->has('id');
    }

    /**
     * @return int[]
     */
    public function getIds(): array
    {
        return $this->id;
    }
}
