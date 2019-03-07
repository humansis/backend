<?php


namespace BeneficiaryBundle\Utils\DataTreatment;


use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\BeneficiaryService;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Cache\Simple\FilesystemCache;


abstract class AbstractTreatment implements InterfaceTreatment
{

    /** @var EntityManagerInterface $em */
    protected $em;

    /** @var HouseholdService $householdService */
    protected $householdService;

    /** @var BeneficiaryService $beneficiaryService */
    protected $beneficiaryService;

    /** @var $token */
    protected $token;

    /** @var Container $container */
    protected $container;

    public function __construct(
        EntityManagerInterface $entityManager,
        HouseholdService $householdService,
        BeneficiaryService $beneficiaryService,
        Container $container,
        $token
    )
    {
        $this->em = $entityManager;
        $this->householdService = $householdService;
        $this->beneficiaryService = $beneficiaryService;
        $this->container = $container;
        $this->token = $token;
    }

    /**
     * @param $step
     * @param array $listHouseholdsArray
     * @param string $email
     * @throws \Exception
     */
    protected function getFromCache($step, array &$listHouseholdsArray, string $email)
    {
        if (null === $this->token)
            return;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);
        $dir_file = $dir_var . '/' . $email . '-' . $step;
        if (!is_file($dir_file))
            return;
        $fileContent = file_get_contents($dir_file);
        $householdsCached = json_decode($fileContent, true);
        foreach ($householdsCached as $householdCached)
        {
            $listHouseholdsArray[] = $householdCached;
        }
    }

    /**
     * @param string $cacheName
     * @param $householdsToSave
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function saveHouseholds(string $cacheName, $householdsToSave) {
        if (gettype($householdsToSave) == 'array') {
            $householdsToSave = $this->em->getRepository(Household::class)->findOneBy(
                [
                    'addressStreet' => $householdsToSave['address_street'],
                    'addressNumber' => $householdsToSave['address_number'],
                    'addressPostcode' => $householdsToSave['address_postcode'],
                    'livelihood' => $householdsToSave['livelihood'],
                    'notes' => $householdsToSave['notes'],
                    'latitude' => $householdsToSave['latitude'],
                    'longitude' => $householdsToSave['longitude'],
                ]
            );
        }


        if ($householdsToSave instanceof Household) {
            $cache = new FilesystemCache();

            $householdsArray = array();

            if ($cache->has($cacheName)) {
                $householdFromCache = $cache->get($cacheName);

                $householdsArray = $householdFromCache;
                array_push($householdsArray, $householdsToSave);
            } else
                array_push($householdsArray, $householdsToSave);

            $cache->set($cacheName, $householdsArray);
        }
    }

    /**
     * @param string $cacheName
     */
    public function clearCache(string $cacheName) {
        $cache = new FilesystemCache();

        if ($cache->has($cacheName))
            $cache->delete($cacheName);
    }
}