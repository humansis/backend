<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Utils\ImportProvider\DefaultApiProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ApiImportService
 * @package BeneficiaryBundle\Utils
 */
class ApiImportService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /** @var DefaultApiProvider $apiProvider */
    private $apiProvider;

    /**
     * HouseholdService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ContainerInterface $container
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container
    )
    {
        $this->em = $entityManager;
        $this->container= $container;
    }


    /**
     * Import beneficiaries from the API in the current country
     * @param  string $countryISO3
     * @param string $provider
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function import(string $countryISO3, string $provider, array $params)
    {
        try {
            $this->apiProvider = $this->getApiProviderForCountry($countryISO3, $provider);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }

        try {
            return $this->apiProvider->importData($countryISO3, $params);

        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }


    /**
     * Get the API provider corresponding to the current country
     * @param  string $countryISO3 iso3 code of the country
     * @param string $provider
     * @return DefaultApiProvider|object
     * @throws \Exception
     */
    private function getApiProviderForCountry(string $countryISO3, string $provider)
    {
        $provider = $this->container->get('beneficiary.' . strtolower($countryISO3) . '_api_provider_' . $provider);

        if (! ($provider instanceof DefaultApiProvider)) {
            throw new \Exception("The API provider for " . $countryISO3 . "is not properly defined");
        }
        return $provider;
    }
}