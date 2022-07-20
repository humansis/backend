<?php
declare(strict_types=1);

namespace NewApiBundle\InputType\FilterFragment;
use Symfony\Component\Validator\Constraints as Assert;
use Happyr\Validator\Constraint\EntityExist;

trait AssistanceFilterTrait
{
    /**
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"}),
     *         @EntityExist(entity="\NewApiBundle\Entity\Assistance", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $assistances;

    public function hasAssistances(): bool
    {
        return $this->has('assistances');
    }

    /**
     * @return int[]
     */
    public function getAssistances(): array
    {
        return $this->assistances;
    }
}
