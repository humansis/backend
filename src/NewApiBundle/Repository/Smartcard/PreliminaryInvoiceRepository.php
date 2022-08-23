<?php declare(strict_types=1);

namespace NewApiBundle\Repository\Smartcard;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
use NewApiBundle\Enum\VendorInvoicingState;
use VoucherBundle\Entity\Vendor;

class PreliminaryInvoiceRepository extends EntityRepository
{
    /**
     * @param Vendor      $vendor
     * @param string|null $invoicingState
     *
     * @return Vendor[]
     */
    public function findByVendorAndState(Vendor $vendor, ?string $invoicingState = null): array
    {
        $qb = $this->createQueryBuilder('pi');
        $qb->join('pi.vendor', 'v');
        $qb->andWhere('v.id = :vendor')
            ->setParameter('vendor', $vendor->getId());

        if ($invoicingState) {
            switch ($invoicingState) {
                case VendorInvoicingState::SYNC_REQUIRED:
                    $qb->andWhere('pi.project IS NULL');
                    break;
                case VendorInvoicingState::TO_REDEEM:
                    $qb->andWhere('pi.project IS NOT NULL');
                    break;
                default:
                    throw new InvalidArgumentException('Invoicing state should be one of ['.implode(',',
                            VendorInvoicingState::notCompletedValues()).'], '.$invoicingState.' given.');
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $alias
     *
     * @return QueryBuilder
     */
    public function provideQueryBuilder(string $alias = 'pi'): QueryBuilder
    {
        return $this->createQueryBuilder($alias);
    }
}
