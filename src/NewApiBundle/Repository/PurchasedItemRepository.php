<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\Entity\DistributedItem;
use NewApiBundle\InputType\DistributedItemFilterInputType;
use NewApiBundle\Request\Pagination;
use ProjectBundle\Entity\Project;

class PurchasedItemRepository extends EntityRepository
{
}
