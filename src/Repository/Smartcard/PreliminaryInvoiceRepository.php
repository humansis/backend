<?php

declare(strict_types=1);

namespace Repository\Smartcard;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Entity\Smartcard\PreliminaryInvoice;
use InvalidArgumentException;
use Enum\VendorInvoicingState;
use Entity\Vendor;

class PreliminaryInvoiceRepository extends EntityRepository
{
    /**
     * @param Vendor $vendor
     * @param string|null $invoicingState
     *
     * @return PreliminaryInvoice[]
     */
    public function findByVendorAndState(Vendor $vendor, ?string $invoicingState = null): array
    {
        $qb = $this->createQueryBuilder('pi');
        $qb->where('pi.vendor = :vendor')
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
                    throw new InvalidArgumentException(
                        'Invoicing state should be one of [' . implode(
                            ',',
                            VendorInvoicingState::notCompletedValues()
                        ) . '], ' . $invoicingState . ' given.'
                    );
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
