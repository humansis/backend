<?php
declare(strict_types=1);

namespace VoucherBundle\DTO;

class PurchaseDetail implements \JsonSerializable
{
    /** @var \DateTimeInterface */
    private $date;

    /** @var int */
    private $beneficiaryId;

    /** @var string */
    private $beneficiaryEnName;

    /** @var string */
    private $beneficiaryLocalName;

    /** @var float */
    private $amount;

    /**
     * PurchaseDetail constructor.
     *
     * @param \DateTimeInterface $date
     * @param int                $beneficiaryId
     * @param string             $beneficiaryEnName
     * @param string             $beneficiaryLocalName
     * @param mixed              $amount
     */
    public function __construct(\DateTimeInterface $date, int $beneficiaryId, string $beneficiaryEnName, string $beneficiaryLocalName, $amount)
    {
        $this->date = $date;
        $this->beneficiaryId = $beneficiaryId;
        $this->beneficiaryEnName = $beneficiaryEnName;
        $this->beneficiaryLocalName = $beneficiaryLocalName;
        $this->amount = $amount;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return int
     */
    public function getBeneficiaryId(): int
    {
        return $this->beneficiaryId;
    }

    /**
     * @return string
     */
    public function getBeneficiaryEnName(): string
    {
        return $this->beneficiaryEnName;
    }

    /**
     * @return string
     */
    public function getBeneficiaryLocalName(): string
    {
        return $this->beneficiaryLocalName;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    public function jsonSerialize()
    {
        return [
            'purchase_datetime' => $this->getDate()->format('U'),
            'purchase_date' => $this->getDate()->format('d-m-Y'),
            'purchase_amount' => $this->getAmount(),
            'beneficiary_id' => $this->getBeneficiaryId(),
            'beneficiary_local_name' => $this->getBeneficiaryLocalName(),
            'beneficiary_en_name' => $this->getBeneficiaryEnName(),
        ];
    }

}
