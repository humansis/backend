<?php

declare(strict_types=1);

namespace Repository\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Entity\SmartcardDeposit;

/**
 * @property-read EntityManagerInterface $_em
 */
trait PersistAndFlush
{
    public function persist(object $entity): void
    {
        $this->_em->persist($entity);
    }

    public function persistAndFlush(object $entity): void
    {
        $this->persist($entity);
        $this->_em->flush($entity);
    }

    public function setNewInstanceOfClosedEntityManager(EntityManagerInterface $newInstanceOfEntityManager): void
    {
        if (!$this->_em->isOpen()) {
            $this->_em = $newInstanceOfEntityManager;
        }
    }
}
