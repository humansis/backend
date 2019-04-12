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
    ) {
        $this->em = $entityManager;
        $this->householdService = $householdService;
        $this->beneficiaryService = $beneficiaryService;
        $this->container = $container;
        $this->token = $token;
    }
    
    /**
     * @param  boolean $needsToken
     * @return string
     */
    private function getDirectory($needsToken = false)
    {
        if (! $this->token && $needsToken) {
            return null;
        } else if (! $this->token && ! $needsToken) {
            $sizeToken = 50;
            $this->token = bin2hex(random_bytes($sizeToken));
        }

        $dir_root = $this->container->get('kernel')->getRootDir();
        
        $dir_var_token = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var_token)) {
            mkdir($dir_var_token);
        }
        
        return $dir_var_token;
    }
    
    /**
     * @param int $idCache
     * @param array $newData
     * @param string $email
     * @param array|null $oldData
     * @throws \Exception
     */
    protected function updateInCache(int $idCache, array $newData, string $email, $oldData = null)
    {
        $dir_var_token = $this->getDirectory(true);
        if (empty($dir_var_token)) {
            return;
        }

        // Update cache
        $dir_file_update = $dir_var_token . '/' . $email . '-to_update';
        if (is_file($dir_file_update)) {
            $listHHUpdate = json_decode(file_get_contents($dir_file_update), true);
        } else {
            $listHHUpdate = [];
        }
        
        if (array_key_exists($idCache, $listHHUpdate)) {
            $listHHUpdate[$idCache]['new'] = $newData;
            // update only if there is data in old
            $listHHUpdate[$idCache]['old'] = $oldData ? $oldData : $listHHUpdate[$idCache]['old'];
            file_put_contents($dir_file_update, json_encode($listHHUpdate));
            return;
        }
        
        // Create cache
        $dir_file_create = $dir_var_token . '/' . $email . '-to_create';
        if (is_file($dir_file_create)) {
            $listHHCreate = json_decode(file_get_contents($dir_file_create), true);
        } else {
            $listHHCreate = [];
        }
        
        if (array_key_exists($idCache, $listHHCreate)) {
            $listHHCreate[$idCache]['new'] = $newData;
            // update only if there is data in old
            $listHHCreate[$idCache]['old'] = $oldData ? $oldData : $listHHCreate[$idCache]['old'];
            file_put_contents($dir_file_create, json_encode($listHHCreate));
            return;
        }
    }
    
    /**
     * @param string $step
     * @param string | int $idCache
     * @param string $email
     * @throws \Exception
     */
    protected function getItemFromCache(string $step, $idCache, string $email)
    {
        dump($step, $idCache, $email);
        $dir_var_token = $this->getDirectory(true);
        dump($dir_var_token);
        if (empty($dir_var_token)) {
            return;
        }

        $dir_file = $dir_var_token . '/' . $email . '-' . $step;
        dump($dir_file);
        if (is_file($dir_file)) {
            $listHH = json_decode(file_get_contents($dir_file), true);
        } else {
            $listHH = [];
        }
        dump($listHH);
        if (array_key_exists($idCache, $listHH)) {
            return $listHH[$idCache];
        } else {
            return null;
        }
    }
    
    /**
     * @param string $step
     * @param int | string $cacheId
     * @param array $newData
     * @param string $email
     * @param array $household
     * @throws \Exception
     */
    protected function saveInCache(string $step, $cacheId, array $newData, string $email, array $oldData)
    {
        $dir_var_token = $this->getDirectory();
        if (empty($dir_var_token)) {
            return;
        }

        if (is_file($dir_var_token . '/' . $email . '-' . $step)) {
            $listHH = json_decode(file_get_contents($dir_var_token . '/' . $email . '-' . $step), true);
        } else {
            $listHH = [];
        }

        $listHH[$cacheId] = ['new' => $newData, 'old' => $oldData, 'id_tmp_cache' => $cacheId];
        file_put_contents($dir_var_token . '/' . $email . '-' . $step, json_encode($listHH));
    }

    /**
     * @param string $step
     * @param string $email
     * @throws \Exception
     */
    protected function getFromCache(string $step, string $email)
    {
        $dir_var_token = $this->getDirectory(true);
        if (empty($dir_var_token)) {
            return;
        }
        
        $dir_file = $dir_var_token . '/' . $email . '-' . $step;
        if (!is_file($dir_file)) {
            return;
        }
        
        return json_decode(file_get_contents($dir_file), true);
    }

    /**
     * @param string $cacheName
     * @param $householdsToSave
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function saveHouseholds(string $cacheName, $householdsToSave)
    {
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
            } else {
                array_push($householdsArray, $householdsToSave);
            }

            $cache->set($cacheName, $householdsArray);
        }
    }

    /**
     * @param string $cacheName
     */
    public function clearCache(string $cacheName)
    {
        $cache = new FilesystemCache();

        if ($cache->has($cacheName)) {
            $cache->delete($cacheName);
        }
    }
}
