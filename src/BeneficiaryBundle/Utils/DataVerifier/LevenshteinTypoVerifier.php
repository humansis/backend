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
    private $maximumDistanceLevenshtein = 4;

    /** @var Container $container */
    private $container;

    private $token;


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
        $beneficiaryRepository = $this->em->getRepository(Beneficiary::class);

        $newHead = null;

        foreach ($householdArray['beneficiaries'] as $newBeneficiaryArray)
        {
            if (1 === intval($newBeneficiaryArray['status']))
            {
                $newHead = $newBeneficiaryArray;
                break;
            }
        }

        if (null === $newHead)
            return null;

        $stringToCompare = $householdArray["address_street"] .
            $householdArray["address_number"] .
            $householdArray["address_postcode"] .
            $newHead["given_name"] .
            $newHead["family_name"];

        $similarHouseholds = $householdRepository->foundSimilarLevenshtein(
            $stringToCompare,
            $this->maximumDistanceLevenshtein);

        if (empty($similarHouseholds))
        {
            $this->saveInCache('no_typo', $cacheId, $householdArray, null);
            return null;
        }
        elseif (1 === sizeof($similarHouseholds))
        {
            $oldHead = $beneficiaryRepository->getHeadOfHouseholdId(current($similarHouseholds)->getId());
            $distanceTmp = levenshtein(
                $stringToCompare,
                current($similarHouseholds)->getAddressStreet() .
                current($similarHouseholds)->getAddressNumber() .
                current($similarHouseholds)->getAddressPostcode() .
                $oldHead->getGivenName() .
                $oldHead->getFamilyName()
            );
            if (0 == $distanceTmp)
            {
                // SAVE 100% SIMILAR IN 1_typo
                $this->saveInCache(
                    'mapping_new_old',
                    $cacheId,
                    $householdArray,
                    $householdRepository->find(current($similarHouseholds))
                );
                return false;
            }
            $return = [
                "old" => $householdRepository->find(current($similarHouseholds)),
                "new" => $householdArray, "id_tmp_cache" => $cacheId
            ];

            return $return;
        }
        else
        {
            $distance = null;
            $bestSimilarHousehold = null;
            /** @var Household $similarHousehold */
            foreach ($similarHouseholds as $similarHousehold)
            {
                /** @var Beneficiary $oldHead */
                $oldHead = $beneficiaryRepository->getHeadOfHouseholdId($similarHousehold->getId());
                $distanceTmp = levenshtein(
                    $stringToCompare,
                    $similarHousehold->getAddressStreet() .
                    $similarHousehold->getAddressNumber() .
                    $similarHousehold->getAddressPostcode() .
                    $oldHead->getGivenName() .
                    $oldHead->getFamilyName()
                );
                if (0 == $distanceTmp)
                {
                    // SAVE 100% SIMILAR IN 1_typo
                    $this->saveInCache(
                        'mapping_new_old',
                        $cacheId,
                        $householdArray,
                        $householdRepository->find($similarHousehold)
                    );
                }
                elseif ($distance === null || $distanceTmp < $distance)
                {
                    $bestSimilarHousehold = $similarHousehold;
                    $distance = $distanceTmp;
                }
            }
            $return = [
                "old" => $householdRepository->find($bestSimilarHousehold),
                "new" => $householdArray, "id_tmp_cache" => $cacheId
            ];
            return $return;
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
                ->serialize($household, 'json', SerializationContext::create()->setSerializeNull(true)), true);
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