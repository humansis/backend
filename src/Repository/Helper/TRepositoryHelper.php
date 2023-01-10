<?php

declare(strict_types=1);

namespace Repository\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Entity\SmartcardDeposit;

/**
 * @property EntityManagerInterface $_em
 */
trait TRepositoryHelper
{
    public function persist(object $entity): void
    {
        $this->_em->persist($entity);
    }

    public function save(object $entity): void
    {
        $this->persist($entity);
        $this->_em->flush($entity);
    }
}
