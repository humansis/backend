<?php
declare(strict_types=1);

namespace NewApiBundle\Entity\Helper;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Enum\SourceType;
use UserBundle\Entity\User;

trait CreatedBy
{
    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by_user_id")
     */
    private $createdBy;

    /**
     * @return User|null
     */
    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    /**
     * @param User|null $createdBy
     */
    public function setCreatedBy(?User $createdBy)
    {
        $this->createdBy = $createdBy;
        return $this;
    }

}
