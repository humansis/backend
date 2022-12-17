<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Entity\SmartcardDepositLog;
use Repository\Helper\PersistAndFlush;

class SmartcardDepositLogRepository extends EntityRepository
{
    use PersistAndFlush;

    public function persistAndFlush(object $entity): void
    {
//        $this->reopenEntityManagerIfIsClosed();
        $this->_em->persist($entity);
        $this->_em->flush($entity);
    }
}
