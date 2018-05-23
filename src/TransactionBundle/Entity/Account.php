<?php

namespace TransactionBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Account
 *
 * @ORM\Table(name="account")
 * @ORM\Entity(repositoryClass="TransactionBundle\Repository\AccountRepository")
 */
class Account
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
     * @ORM\Column(name="accountnumber", type="string", length=45)
     */
    private $accountnumber;

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
     * Set accountnumber.
     *
     * @param string $accountnumber
     *
     * @return Account
     */
    public function setAccountnumber($accountnumber)
    {
        $this->accountnumber = $accountnumber;

        return $this;
    }

    /**
     * Get accountnumber.
     *
     * @return string
     */
    public function getAccountnumber()
    {
        return $this->accountnumber;
    }
}
