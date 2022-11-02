<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ResetingReliefPackageInputType implements InputTypeInterface
{
    /**
     * @var int
     *
     * @Assert\Type("integer")
     * @Assert\NotNull
     * @Assert\GreaterThan(0)
     */
    private $assistanceId;

    /**
     * @var int
     *
     * @Assert\Type("integer")
     * @Assert\NotNull
     * @Assert\GreaterThan(0)
     */
    private $beneficiaryId;

    /**
     * @var string
     *
     * @Assert\NotBlank
     * @Assert\Type(type="string")
     */
    private $smartcardCode;

    /**
     * @return int
     */
    public function getAssistanceId(): int
    {
        return $this->assistanceId;
    }

    /**
     * @param int $assistanceId
     */
    public function setAssistanceId(int $assistanceId): void
    {
        $this->assistanceId = $assistanceId;
    }

    /**
     * @return int
     */
    public function getBeneficiaryId(): int
    {
        return $this->beneficiaryId;
    }

    /**
     * @param int $beneficiaryId
     */
    public function setBeneficiaryId(int $beneficiaryId): void
    {
        $this->beneficiaryId = $beneficiaryId;
    }

    /**
     * @return string
     */
    public function getSmartcardCode(): string
    {
        return $this->smartcardCode;
    }

    /**
     * @param string $smartcardCode
     */
    public function setSmartcardCode(string $smartcardCode): void
    {
        $this->smartcardCode = strtoupper($smartcardCode);
    }
}
