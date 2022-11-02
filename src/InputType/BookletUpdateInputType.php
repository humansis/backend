<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[Assert\GroupSequence(['BookletUpdateInputType', 'PrimaryValidation', 'SecondaryValidation'])]
class BookletUpdateInputType implements InputTypeInterface
{
    #[Assert\Type('int')]
    #[Assert\GreaterThan(0)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $quantityOfVouchers;

    /**
     * @Assert\All(
     *     constraints={
     *         @Assert\Type("integer", groups={"SecondaryValidation"}),
     *         @Assert\GreaterThan(0, groups={"SecondaryValidation"}),
     *     },
     *     groups={"SecondaryValidation"}
     * )
     */
    #[Assert\NotNull]
    #[Assert\Type('array', groups: ['PrimaryValidation'])]
    #[Assert\Callback([\InputType\BookletBatchCreateInputType::class, 'validateIndividualValues'], groups: ['SecondaryValidation'])]
    private $values;

    #[Assert\Type('string')]
    private $password;

    #[Assert\Type('string')]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $currency;

    public static function validateIndividualValues($array, ExecutionContextInterface $context, $payload)
    {
        if ((is_countable($array) ? count($array) : 0) > $context->getObject()->getQuantityOfVouchers()) {
            $context->buildViolation('Too many individual values')
                ->atPath('individualValues')
                ->addViolation();
        }
    }

    /**
     * @return int
     */
    public function getQuantityOfVouchers()
    {
        return $this->quantityOfVouchers;
    }

    public function setQuantityOfVouchers($quantityOfVouchers)
    {
        $this->quantityOfVouchers = $quantityOfVouchers;
    }

    /**
     * @return array|int[]
     */
    public function getValues()
    {
        return $this->values;
    }

    public function setValues($values)
    {
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }
}
