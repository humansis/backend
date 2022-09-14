<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * Referral
 *
 * @ORM\Table(name="referral")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\ReferralRepository")
 */
class Referral extends AbstractEntity
{

    /**
    * Mapping of possible referral types
    */
    const REFERRALTYPES = [
        '1' => 'Health',
        '2' => 'Protection',
        '3' => 'Shelter',
        '4' => 'Nutrition',
        '5' => 'Other',
    ];

    public static function types(): array
    {
        $keys = [];
        foreach (Referral::REFERRALTYPES as $key => $value) {
            $keys[] = (string) $key;
        }

        return $keys;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "ValidatedAssistance"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=255)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "ValidatedAssistance"})
     */
    private $comment;


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
