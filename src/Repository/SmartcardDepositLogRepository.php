<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Entity\SmartcardDepositLog;
use Repository\Helper\TRepositoryHelper;

class SmartcardDepositLogRepository extends EntityRepository
{
    use TRepositoryHelper;
}
