<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Assert\GroupSequence({"AddRemoveBeneficiaryToAssistanceInputType", "Strict"})
 * @Assert\Callback({"NewApiBundle\InputType\AddRemoveBeneficiaryToAssistanceInputType", "validate"})
 */
class AddRemoveBeneficiaryToAssistanceInputType implements InputTypeInterface
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
     * @Assert\Type("array")
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("int", groups={"Strict"})
     *     },
     *     groups={"Strict"}
     * )
     */
    protected $beneficiaryIds;

    /**
     * @Assert\Type("string")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    protected $justification;

    public function validate($object, ExecutionContextInterface $context, $payload)
    {
        if ($object->getAdded() !== true && $object->getRemoved() !== true) {
            $context->buildViolation('One of added/removed attributes must be set.')
                ->addViolation()
            ;
        }
    }

    public function setBeneficiaryIds($beneficiaryIds)
    {
        $this->beneficiaryIds = $beneficiaryIds;
    }

    public function getBeneficiaryIds()
    {
        return $this->beneficiaryIds;
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
