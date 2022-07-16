<?php
declare(strict_types=1);

namespace NewApiBundle\Entity\Helper;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Enum\SourceType;
use NewApiBundle\Entity\User;

trait CreatedBy
{
    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\User")
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
     *
     * @return self
     */
    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

}
