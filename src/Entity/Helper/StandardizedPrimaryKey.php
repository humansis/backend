<?php

declare(strict_types=1);

namespace Entity\Helper;

use Doctrine\ORM\Mapping as ORM;

trait StandardizedPrimaryKey
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int|null $id = null;

    public function getId(): int|null
    {
        return $this->id;
    }
}
