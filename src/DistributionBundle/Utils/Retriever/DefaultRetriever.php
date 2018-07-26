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
     * @param bool $onlyCount
     * @param array $configurationCriteria
     * @return mixed
     * @throws \Exception
     */
    public function getReceivers(
        string $countryISO3,
        string $distributionType,
        array $criteria,
        array $configurationCriteria,
        bool $onlyCount = false
    )
    {
        if ($distributionType === 'household')
        {
            foreach ($criteria as $index => $criterion)
            {
                $criteria[$index]["kind_beneficiary"] = $this->getStatusBeneficiaryCriterion($criterion["kind_beneficiary"]);
            }
        }
        elseif ($distributionType === 'beneficiary')
        {

        }
        else
        {
            throw new \Exception("The distribution type '$distributionType' is unknown.");
        }
        $receivers = $this->guessRepository($distributionType)->findByCriteria($countryISO3, $criteria, $configurationCriteria, $onlyCount);

        // If we only want the number of beneficiaries, return only the number
        if ($onlyCount)
        {
            $receivers = ["number" => intval(current($receivers)[1])];
        }
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
     * @param $kindBeneficiary
     * @return int
     * @throws \Exception
     */
    private function getStatusBeneficiaryCriterion($kindBeneficiary)
    {
        $kindBeneficiary = trim(strtolower(strval($kindBeneficiary)));
        switch ($kindBeneficiary)
        {
            case 'beneficiary':
                return 1;
            case 'dependent':
                return 0;
            case null:
                return null;
        }

        throw new \Exception("The kindBeneficiary '$kindBeneficiary' is not implemented yet.");
    }
}