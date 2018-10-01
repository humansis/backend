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
     * @param string $countryCode
     * @param Project $project
     * @return array
     * @throws \Exception
     */
    public function import(string $countryISO3, string $provider, string $countryCode, Project $project)
    {
        try {
            $this->apiProvider = $this->getApiProviderForCountry($countryISO3, $provider);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }

        try {
            return $this->apiProvider->importData($countryISO3, $countryCode, $project);

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

    /**
     * @param string $countryISO3
     * @return array
     */
    public function getApiNames(string $countryISO3) {
        $countryISO3 = strtoupper($countryISO3);
        $listAPI = array();
        foreach(glob('../src/BeneficiaryBundle/Utils/ImportProvider/'.$countryISO3.'/*.*') as $file) {

            $firstExplode = explode('API', $file);
            $secondExplode = explode($countryISO3, $firstExplode[0]);



            array_push($listAPI, array(
                    'APIName' => $secondExplode[2]
                )
            );
        }

        return ['listAPI' => $listAPI];
    }

    /**
     * @param string $countryISO3
     * @param string $api
     * @return array
     */
    public function getParams(string $countryISO3, string $api){
        $countryISO3 = strtolower($countryISO3);
        $api = strtolower($api);

        $provider = $this->container->get('beneficiary.' . $countryISO3 . '_api_provider_' . $api);

        $paramsFunction = $provider->getParams();
        $params = array();

        foreach ($paramsFunction->getParameters() as $parameter) {
            if($parameter->getName() != 'project' && $parameter->getName() != 'countryIso3'){
                array_push($params, array(
                        'paramName' => $parameter->getName(),
                        'paramType' => $parameter->getType()->getName()
                    )
                );
            }
        }

        return $params;
    }
}