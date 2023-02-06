<?php

declare(strict_types=1);

namespace Entity\Helper;

use Doctrine\ORM\Mapping as ORM;
use Enum\SourceType;
use Entity\User;

trait CreatedBy
{
    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="Entity\User")
     * @ORM\JoinColumn(name="created_by_user_id")
     */
    private ?User $createdBy;

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
