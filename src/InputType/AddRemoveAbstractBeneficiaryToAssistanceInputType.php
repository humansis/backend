<?php
declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Assert\Callback({"InputType\AddRemoveBeneficiaryToAssistanceInputType", "validate"})
 */
class AddRemoveAbstractBeneficiaryToAssistanceInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("boolean")
     */
    protected $added;

    /**
     * @Assert\Type("boolean")
     */
    protected $removed;

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    protected $justification;

    public static function validate($object, ExecutionContextInterface $context, $payload)
    {
        if ($object->getAdded() !== true && $object->getRemoved() !== true) {
            $context->buildViolation('One of added/removed attributes must be set.')
                ->addViolation()
            ;
        }
    }

    public function setJustification($justification)
    {
        $this->justification = $justification;
    }

    public function getJustification()
    {
        return $this->justification;
    }

    public function setAdded($added)
    {
        $this->added = $added;
    }

    public function getAdded()
    {
        return $this->added;
    }

    public function setRemoved($removed)
    {
        $this->removed = $removed;
    }

    public function getRemoved()
    {
        return $this->removed;
    }
}
