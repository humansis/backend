<?php

declare(strict_types=1);

namespace Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CreatedAt;
use Entity\Helper\CreatedBy;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * @ORM\Table(name="smartcard_deposit_log")
 * @ORM\Entity(repositoryClass="Repository\SmartcardDepositLogRepository")
 */
class SmartcardDepositLog
{
    use StandardizedPrimaryKey;
    use CreatedAt;
    use CreatedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Entity\SmartcardDeposit", inversedBy="logs", cascade={"persist", "remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private SmartcardDeposit $smartcardDeposit;

    /**
     * @ORM\Column(name="request_data", type="json")
     */
    private array $requestData;

    /**
     * @ORM\Column(name="message", type="string")
     */
    private string $message;

    public function __construct(
        User $createdBy,
        SmartcardDeposit $smartcardDeposit,
        array $requestData,
        string $message,
    ) {
        $this->createdBy = $createdBy;
        $this->requestData = $requestData;
        $this->smartcardDeposit = $smartcardDeposit;
        $this->message = $message;
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * @param array $requestData
     */
    public function setRequestData(array $requestData): void
    {
        $this->requestData = $requestData;
    }

    /**
     * @return SmartcardDeposit
     */
    public function getSmartcardDeposit(): SmartcardDeposit
    {
        return $this->smartcardDeposit;
    }

    /**
     * @param SmartcardDeposit $smartcardDeposit
     */
    public function setSmartcardDeposit(SmartcardDeposit $smartcardDeposit): void
    {
        $this->smartcardDeposit = $smartcardDeposit;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
