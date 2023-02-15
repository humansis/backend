<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * Referral
 */
#[ORM\Table(name: 'referral')]
#[ORM\Entity(repositoryClass: 'Repository\ReferralRepository')]
class Referral
{
    use StandardizedPrimaryKey;

    /**
     * Mapping of possible referral types
     */
    final public const REFERRALTYPES = [
        '1' => 'Health',
        '2' => 'Protection',
        '3' => 'Shelter',
        '4' => 'Nutrition',
        '5' => 'Other',
    ];

    #[ORM\Column(name: 'type', type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(name: 'comment', type: 'string', length: 255)]
    private string $comment;

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Referral
     */
    public function setType(string $type): Referral
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType(): string
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
    public function setComment(string $comment): Referral
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    public static function types(): array
    {
        $keys = [];
        foreach (Referral::REFERRALTYPES as $key => $value) {
            $keys[] = (string) $key;
        }

        return $keys;
    }
}
