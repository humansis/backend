<?php

namespace VoucherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Booklet
 *
 * @ORM\Table(name="booklet")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\BookletRepository")
 */
class Booklet
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     */
    private $code;

    /**
     * @var int
     *
     * @ORM\Column(name="number_vouchers", type="integer")
     */
    private $numberVouchers;

    /**
     * @var int
     *
     * @ORM\Column(name="individual_value", type="integer")
     */
    private $individualValue;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=255)
     */
    private $currency;

    /**
     * @var int|null
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     */
    private $password;


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return Booklet
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set numberVouchers.
     *
     * @param int $numberVouchers
     *
     * @return Booklet
     */
    public function setNumberVouchers($numberVouchers)
    {
        $this->numberVouchers = $numberVouchers;

        return $this;
    }

    /**
     * Get numberVouchers.
     *
     * @return int
     */
    public function getNumberVouchers()
    {
        return $this->numberVouchers;
    }

    /**
     * Set individualValue.
     *
     * @param int $individualValue
     *
     * @return Booklet
     */
    public function setIndividualValue($individualValue)
    {
        $this->individualValue = $individualValue;

        return $this;
    }

    /**
     * Get individualValue.
     *
     * @return int
     */
    public function getIndividualValue()
    {
        return $this->individualValue;
    }

    /**
     * Set currency.
     *
     * @param string $currency
     *
     * @return Booklet
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return Booklet
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set password.
     *
     * @param string|null $password
     *
     * @return Booklet
     */
    public function setPassword($password = null)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }
}
