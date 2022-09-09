<?php

namespace NewApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\CreatedAt;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;

/**
 * @ORM\HasLifecycleCallbacks()
 */
abstract class AbstractEntity
{
    use StandardizedPrimaryKey;
    use CreatedAt;
}