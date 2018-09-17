<?php


namespace BeneficiaryBundle\Utils\DataVerifier;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\DependencyInjection\Container;

class LevenshteinTypoVerifier extends AbstractVerifier
{

    /**
     * Maximum distance between two strings with the Levenshtein algorithm
     * @var int
     */
    private $maximumDistanceLevenshtein = 2;

    /** @var Container $container */
    private $container;

    private $token;


    /**
     * LevenshteinTypoVerifier constructor.
     * @param EntityManagerInterface $entityManager
     * @param Container $container
     * @param $token
     */
    public function __construct(EntityManagerInterface $entityManager, Container $container, $token)
    {
        parent::__construct($entityManager);

        $this->token = $token;
        $this->container = $container;
    }

    /**
     * @param string $countryISO3
     * @param array $householdArray
     * @param int $cacheId
     * @return array|bool|null
     * @throws \Exception
     */
    public function verify(string $countryISO3, array $householdArray, int $cacheId)
    {
        $householdRepository = $this->em->getRepository(Household::class);
        $newHead = null;
        foreach ($householdArray['beneficiaries'] as $newBeneficiaryArray)
        {
            if (1 == $newBeneficiaryArray['status'])
            {
                $newHead = $newBeneficiaryArray;
                break;
            }
        }

        if (null === $newHead)
            return null;

        $similarHouseholds = $householdRepository->foundSimilarLevenshtein(
            $householdArray["address_street"] .
            $householdArray["address_number"] .
            $householdArray["address_postcode"] .
            $newHead["given_name"] .
            $newHead["family_name"],
            $this->maximumDistanceLevenshtein);

        if (empty($similarHouseholds))
        {
            $this->saveInCache('no_typo', $cacheId, $householdArray, null);
            return null;
        }
        else
        {
            if (0 == intval(current($similarHouseholds)["levenshtein"]))
            {
                // SAVE 100% SIMILAR IN 1_typo
                $this->saveInCache(
                    'mapping_new_old',
                    $cacheId,
                    $householdArray,
                    $householdRepository->find(current($similarHouseholds)["household"])
                );
                return false;
            }

            return [
                "old" => $householdRepository->find(current($similarHouseholds)["household"]),
                "new" => $householdArray, "id_tmp_cache" => $cacheId
            ];
        }
    }

    /**
     * @param string $step
     * @param int $cacheId
     * @param array $dataToSave
     * @param Household|null $household
     * @throws \Exception
     */
    private function saveInCache(string $step, int $cacheId, array $dataToSave, Household $household = null)
    {
        if (null !== $household)
            $arrayOldHousehold = json_decode($this->container->get('jms_serializer')
                ->serialize($household, 'json', SerializationContext::create()->setSerializeNull(true)->setGroups(["FullHousehold"])), true);
        else
            $arrayOldHousehold = json_encode([]);

        $sizeToken = 50;
        if (null === $this->token)
            $this->token = bin2hex(random_bytes($sizeToken));

        $dir_root = $this->container->get('kernel')->getRootDir();

        $dir_var = $dir_root . '/../var/data';
        if (!is_dir($dir_var))
            mkdir($dir_var);

        $dir_var_token = $dir_var . '/' . $this->token;
        if (!is_dir($dir_var_token))
            mkdir($dir_var_token);

        if (is_file($dir_var_token . '/' . $step))
        {
            $listHH = json_decode(file_get_contents($dir_var_token . '/' . $step), true);
        }
        else
        {
            $listHH = [];
        }

        $listHH[$cacheId] = ["new" => $dataToSave, "old" => $arrayOldHousehold, "id_tmp_cache" => $cacheId];
        file_put_contents($dir_var_token . '/' . $step, json_encode($listHH));
    }
}