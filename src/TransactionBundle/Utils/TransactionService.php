<?php

namespace TransactionBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use TransactionBundle\Utils\Provider\DefaultFinancialProvider;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\DistributionBeneficiary;
use UserBundle\Entity\User;
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
    public function sendMoney(string $countryISO3, DistributionData $distributionData, User $user)
    {
        try {            
            $this->financialProvider = $this->getFinancialProviderForCountry($countryISO3);
        } catch (\Exception $e) {
            throw $e;
        }
        
        if ($distributionData->getCommodities()[0]->getModalityType()->getModality()->getName() === "CTP") {
            $amountToSend = $distributionData->getCommodities()[0]->getValue();
            $currencyToSend = $distributionData->getCommodities()[0]->getUnit();
        } else {
            throw new \Exception("The commodity of the distribution does not allow this operation.");
        }
        
        $from = $user->getEmail();
        
        try {            
            return $this->financialProvider->sendMoneyToAll($distributionData, $amountToSend, $currencyToSend, $from);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Get the financial provider corresponding to the current country
     * @param  string $countryISO3 iso3 code of the country
     * @return Class
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
    
    /**
     * Send email to confirm transaction
     * @param  User             $user            
     * @param  DistributionData $distributionData
     * @return void
     */
    public function sendEmail(User $user, DistributionData $distributionData)
    {
        $code = random_int(100000, 999999);
        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/tmp';
        if (!is_dir($dir_var)) mkdir($dir_var);
        $file_confirmation_code = $dir_var . '/code_transaction_confirmation';
        file_put_contents($file_confirmation_code, $code);
        
        $commodity = $distributionData->getCommodities()->get(0);
        $numberOfBeneficiaries = count($distributionData->getDistributionBeneficiaries());
        $amountToSend = $numberOfBeneficiaries * $commodity->getValue();
        
        $message = (new \Swift_Message('Confirm transaction for distribution '. $distributionData->getName()))
           ->setFrom('admin@bmstaging.info')
           ->setTo($user->getEmail())
           ->setBody(
               $this->container->get('templating')->render(
                   'Emails/confirm_transaction.html.twig',
                   array(
                       'distribution' => $distributionData->getName(),
                       'amount' => $amountToSend . ' ' . $commodity->getUnit(),
                       'number' => $numberOfBeneficiaries,
                       'date' => new \DateTime(),
                       'email' => $user->getEmail(),
                       'code' => $code
                   )
               ),
               'text/html'
        );
        $this->container->get('mailer')->send($message);
    }
    
    /**
     * Verify confirmation code
     * @param  int    $code 
     * @return boolean
     */
    public function verifyCode(int $code)
    {
        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/tmp';
        if (! is_dir($dir_var)) {
            return false;
        }
        $file_confirmation_code = $dir_var . '/code_transaction_confirmation';
        $checkedAgainst = file_get_contents($file_confirmation_code);
        if (! $checkedAgainst) {
            return false;
        }
        
        $result = ($code === intval($checkedAgainst));
        if ($result) {
            unlink($file_confirmation_code);
        }
        return $result;
    }
    
    /**
     * Update transaction status
     * @param  DistributionData $distributionData 
     * @return array                             
     */
    public function updateTransactionStatus(DistributionData $distributionData)
    {
        try {            
            $this->financialProvider = $this->getFinancialProviderForCountry($countryISO3);
        } catch (\Exception $e) {
            throw $e;
        }
        
        try {            
            return $this->financialProvider->updateStatusDistribution($distributionData);
        } catch (\Exception $e) {
            throw $e;
        }
    }

}