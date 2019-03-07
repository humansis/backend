<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Utils\ImportProvider\DefaultApiProvider;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ApiImportService
 * @package BeneficiaryBundle\Utils
 */
class APIImportService
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
     * @param Project $project
     * @return array
     * @throws \Exception
     */
    public function import(string $countryISO3, string $provider, array $params, Project $project)
    {
        try {
            $this->apiProvider = $this->getApiProviderForCountry($countryISO3, $provider);
            return $this->apiProvider->importData($countryISO3, $params, $project);
        } catch (\Exception $e) {
            throw $e;
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

    /**
     * @param string $countryISO3
     * @return array
     */
    public function getAllAPI(string $countryISO3) {
        $countryISO3 = strtoupper($countryISO3);

        $listAPI = array();
        foreach(glob('../src/BeneficiaryBundle/Utils/ImportProvider/'.$countryISO3.'/*.*') as $file) {

            $beginFile = explode('API', $file);
            $providerKey = explode($countryISO3, $beginFile[0]);

            $provider = $this->container->get('beneficiary.' . strtolower($countryISO3) . '_api_provider_' . strtolower($providerKey[2]));
            $params = $provider->getParams();

            /** @var object $api */
            $api = (object) array();
            $api->APIName = $providerKey[2];
            $api->params = $params;

            array_push($listAPI, $api);
        }

        return array('listAPI' => $listAPI);
    }
}