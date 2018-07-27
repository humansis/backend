<?php


namespace BeneficiaryBundle\Utils\Distribution;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Repository\BeneficiaryRepository;
use BeneficiaryBundle\Repository\HouseholdRepository;
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
        if ($distributionType === 'household')
        {
            foreach ($criteria as $index => $criterion)
            {
                $criteria[$index]["kind_beneficiary"] = $this->getStatusBeneficiaryCriterion($criterion["kind_beneficiary"]);
            }
        }
        elseif ($distributionType !== 'beneficiary')
        {
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
    protected function getStatusBeneficiaryCriterion($kindBeneficiary)
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