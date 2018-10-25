<?php

namespace TransactionBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use TransactionBundle\Utils\Provider\DefaultFinancialProvider;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\DistributionBeneficiary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TransactionService
 * @package TransactionBundle\Utils
 */
class TransactionService {

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;
    
    /** @var DefaultFinancialProvider $financialProvider */
    private $financialProvider;

    /**
     * TransactionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    /**
     * Send money to distribution beneficiaries
     * @param  string $countryISO3
     * @param  DistributionData $distributionData
     * @return object 
     * @throws \Exception
     */
    public function sendMoney(string $countryISO3, DistributionData $distributionData)
    {
        try {            
            $this->financialProvider = $this->getFinancialProviderForCountry($countryISO3);
        } catch (\Exception $e) {
            throw $e;
        }
        
        $distributionBeneficiaries = $this->em->getRepository(DistributionBeneficiary::class)->findBy(['distributionData' => $distributionData]);
        if ($distributionData->getCommodities()[0]->getModalityType()->getModality()->getName() === "CTP") {
            $amountToSend = $distributionData->getCommodities()[0]->getValue();
            $currencyToSend = $distributionData->getCommodities()[0]->getUnit();
        }
        try {            
            return $this->financialProvider->sendMoneyToAll($distributionBeneficiaries, $amountToSend, $currencyToSend);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Get the financial provider corresponding to the current country
     * @param  string $countryISO3 iso3 code of the country
     * @return object|Class|DefaultFinancialProvider
     * @throws \Exception
     */
    private function getFinancialProviderForCountry(string $countryISO3)
    {
        $provider = $this->container->get('transaction.' . strtolower($countryISO3) . '_financial_provider');
        
        if (! ($provider instanceof DefaultFinancialProvider)) {
            throw new \Exception("The financial provider for " . $countryISO3 . "is not properly defined");
        }
        return $provider;
    }

}