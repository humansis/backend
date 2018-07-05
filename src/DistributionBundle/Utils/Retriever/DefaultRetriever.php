<?php


namespace DistributionBundle\Utils\Retriever;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Repository\BeneficiaryRepository;
use BeneficiaryBundle\Repository\HouseholdRepository;
use Doctrine\ORM\EntityManagerInterface;

class DefaultRetriever extends AbstractRetriever
{

    /** @var BeneficiaryRepository $beneficiaryRepository */
    private $beneficiaryRepository;
    /** @var HouseholdRepository $beneficiaryRepository */
    private $householdRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->beneficiaryRepository = $entityManager->getRepository(Beneficiary::class);
        $this->householdRepository = $entityManager->getRepository(Household::class);
    }


    /**
     * @param string $countryISO3
     * @param string $distributionType
     * @param array $criteria
     * @return mixed
     * @throws \Exception
     */
    public function getReceivers(string $countryISO3, string $distributionType, array $criteria)
    {
        $formattedCriteria = [];
        foreach ($criteria as $criterion)
        {
            $criterion["group"] = $this->getStatusBeneficiaryCriterion($criterion["group"]);
            $formattedCriteria[] = $criterion;
        }

        $receivers = $this->guessRepository($distributionType)->findByCriteria($countryISO3, $formattedCriteria);
        return $receivers;
    }

    /**
     * @param string $distributionType
     * @return BeneficiaryRepository|HouseholdRepository|\Doctrine\Common\Persistence\ObjectRepository
     * @throws \Exception
     */
    private function guessRepository(string $distributionType)
    {
        switch (strtolower($distributionType))
        {
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
     * @param $group
     * @return int
     * @throws \Exception
     */
    private function getStatusBeneficiaryCriterion($group)
    {
        $group = trim(strtolower(strval($group)));
        switch ($group)
        {
            case 'beneficiary':
                return 1;
            case 'dependent':
                return 0;
            case null:
                return null;
        }

        throw new \Exception("The group '$group' is not implemented yet.");
    }
}