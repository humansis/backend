<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Referral
 *
 * @ORM\Table(name="referral")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\ReferralRepository")
 */
class Referral
{
     /**
     * Mapping of possible referral types
     */
    const REFERRALTYPES = [
        '1' => 'Type 1',
        '2' => 'Type 2',
        '3' => 'Type 3',
        '4' => 'Type 4',
        '5' => 'Type 5',
    ];

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
     * @ORM\Column(name="type", type="string", length=255)
     * @Groups({"FullHousehold", "SmallHousehold", "ValidatedDistribution"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=255)
     * @Groups({"FullHousehold", "SmallHousehold", "ValidatedDistribution"})
     */
    private $comment;


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
     * Set type.
     *
     * @param string $type
     *
     * @return Referral
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set comment.
     *
     * @param string $comment
     *
     * @return Referral
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }
}
