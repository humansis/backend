<?php


namespace BeneficiaryBundle\Utils\Distribution;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Household;
use NewApiBundle\Repository\BeneficiaryRepository;
use NewApiBundle\Repository\HouseholdRepository;
use DistributionBundle\Utils\Retriever\AbstractRetriever;
use Doctrine\ORM\EntityManagerInterface;

class DefaultRetriever extends AbstractRetriever
{

    /** @var BeneficiaryRepository $beneficiaryRepository */
    protected $beneficiaryRepository;
    /** @var HouseholdRepository $beneficiaryRepository */
    protected $householdRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->beneficiaryRepository = $entityManager->getRepository(Beneficiary::class);
        $this->householdRepository = $entityManager->getRepository(Household::class);
    }

    /**
     * @param string $distributionType
     * @param array $criteria
     * @throws \Exception
     */
    protected function preFinder(string $distributionType, array &$criteria)
    {
        if ($distributionType === 'household' || $distributionType === 'beneficiary') {
            foreach ($criteria as $index => $criterion) {
                $criteria[$index]["target"] = $this->getStatusBeneficiaryCriterion($criterion["target"]);
            }
        } else {
            throw new \Exception("The distribution type '$distributionType' is unknown.");
        }
    }

    /**
     * @param string $distributionType
     * @return BeneficiaryRepository|HouseholdRepository|\Doctrine\Common\Persistence\ObjectRepository
     * @throws \Exception
     */
    protected function guessRepository(string $distributionType)
    {
        switch (strtolower($distributionType)) {
            case 'household':
                return $this->householdRepository;
            case 'beneficiary':
                return $this->beneficiaryRepository;
        }
        throw new \Exception("This distribution type '$distributionType' is not implemented yet.");
    }

    /**
     * Return the value of the beneficiary status (is head of household or not)
     *
     * @param $target
     * @return int
     * @throws \Exception
     */
    protected function getStatusBeneficiaryCriterion($target)
    {
        $target = trim(strtolower(strval($target)));
        switch ($target) {
            case 'beneficiary':
                return 1;
            case 'household':
                return 0;
            case null:
                return null;
        }

        throw new \Exception("The target '$target' is not implemented yet.");
    }
}
