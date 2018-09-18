<?php

namespace TransactionBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use TransactionBundle\Utils\Provider\DefaultFinancialProvider;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\DistributionBeneficiary;

abstract class TransactionService {

    /** @var EntityManagerInterface $em */
    private $em;
    
    /** @var DefaultFinancialProvider $retriever */
    private $financialProvider;

    /**
     * DefaultFinancialProvider constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }
    
    /**
     * Send money to distribution beneficiaries
     * @param  string           $countryISO3      
     * @param  DistributionData $distributionData 
     * @return [type]                             
     */
    public function sendMoney(string $countryISO3, DistributionData $distributionData)
    {
        try {            
            $this->financialProvider = $this->getFinancialProviderForCountry($countryISO3);
        } catch (\Exception $e) {
            throw new \Exception("The financial provider for " . $countryISO3 . "is not properly defined");
        }
        
        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)->findBy(['distributionData' => $distributionData]);
        
        return $this->financialProvider->sendMoneyToAll($distributionBeneficiaries);
    }
    
    /**
     * Get the financial provider corresponding to the current country
     * @param  string $countryISO3 iso3 code of the country
     * @return Class             
     */
    public function getFinancialProviderForCountry(string $countryISO3)
    {
        $class = new \ReflectionClass('TransactionBundle\\Utils\\Provider\\' 
        . strtoupper($countryISO3) 
        . 'FinancialProvider');
        return $class->newInstanceArgs([$this->em]);
    }

}