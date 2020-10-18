<?php


namespace NewApiBundle\Repository;


class AssistanceRepository extends \DistributionBundle\Repository\AssistanceRepository
{
    public function getAllByProjectId(int $projectId)
    {
        $qb = $this->createQueryBuilder("dd")
            ->leftJoin("dd.project", "p")
            ->where("p.id = :projectId")
            ->setParameter("projectId", $projectId);
            //->andWhere("dd.archived = 0"); //TODO should be archived?
        return $qb->getQuery()->getResult();
    }
}