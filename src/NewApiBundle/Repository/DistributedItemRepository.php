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

class DistributedItemRepository extends EntityRepository
{
    /**
     * @param string                              $countryIso3
     * @param DistributedItemFilterInputType|null $filter
     * @param Pagination|null                     $pagination
     *
     * @return Paginator|DistributedItem[]
     */
    public function findByParams(string $countryIso3, ?DistributedItemFilterInputType $filter, ?Pagination $pagination): Paginator
    {
        $qbr = $this->createQueryBuilder('di')
            ->join('di.project', 'pr')
            ->andWhere('pr.iso3 = :iso3')
            ->setParameter('iso3', $countryIso3);

        if ($filter) {
            if ($filter->hasFulltext()) {
                $qbr->join('di.beneficiary', 'b')
                    ->join('b.person', 'p')
                    ->leftJoin('p.nationalIds', 'ni')
                    ->andWhere('(b.id = :fulltext OR
                                p.localGivenName LIKE :fulltextLike OR 
                                p.localFamilyName LIKE :fulltextLike OR
                                p.localParentsName LIKE :fulltextLike OR
                                p.enParentsName LIKE :fulltextLike OR
                                (ni.idNumber LIKE :fulltextLike AND ni.idType = :niType))')
                    ->setParameter('niType', NationalId::TYPE_NATIONAL_ID)
                    ->setParameter('fulltext', $filter->getFulltext())
                    ->setParameter('fulltextLike', '%'.$filter->getFulltext().'%');
            }
            if ($filter->hasProjects()) {
                $qbr->andWhere('pr.id IN (:projects)')
                    ->setParameter('projects', $filter->getProjects());
            }
            if ($filter->hasAssistances()) {
                $qbr->join('di.assistance', 'as')
                    ->andWhere('as.id IN (:assistances)')
                    ->setParameter('assistances', $filter->getAssistances());
            }
            if ($filter->hasLocations()) {
                $qbr->join('di.location', 'l')
                    ->andWhere('l.id IN (:locations)')
                    ->setParameter('locations', $filter->getLocations());
            }
            if ($filter->hasModalityTypes()) {
                $qbr->andWhere('di.modalityType IN (:modalityTypes)')
                    ->setParameter('modalityTypes', $filter->getModalityTypes());
            }
        }

        if ($pagination) {
            $qbr->setMaxResults($pagination->getLimit())
                ->setFirstResult($pagination->getOffset());
        }

        $qbr->orderBy('di.dateDistribution', 'ASC');

        return new Paginator($qbr);
    }

    /**
     * @param Beneficiary $beneficiary
     *
     * @return Paginator|DistributedItem[]
     */
    public function findByBeneficiary(Beneficiary $beneficiary): Paginator
    {
        $qbr = $this->createQueryBuilder('di')
            ->andWhere('di.beneficiaryType = :type')
            ->andWhere('di.beneficiary = :beneficiary')
            ->setParameter('type', 'Beneficiary')
            ->setParameter('beneficiary', $beneficiary);

        return new Paginator($qbr);
    }

    /**
     * @param Household $household
     *
     * @return Paginator|DistributedItem[]
     */
    public function findByHousehold(Household $household): Paginator
    {
        $qbr = $this->createQueryBuilder('di')
            ->andWhere('di.beneficiaryType = :type')
            ->andWhere('di.beneficiary = :household')
            ->setParameter('type', 'Household')
            ->setParameter('household', $household);

        return new Paginator($qbr);
    }

    /**
     * @param Project $project
     *
     * @return Paginator|DistributedItem[]
     */
    public function findByProject(Project $project): Paginator
    {
        $qbr = $this->createQueryBuilder('di')
            ->andWhere('di.project = :project')
            ->setParameter('project', $project);

        return new Paginator($qbr);
    }
}
