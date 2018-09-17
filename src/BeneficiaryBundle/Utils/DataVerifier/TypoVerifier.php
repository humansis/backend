<?php


namespace BeneficiaryBundle\Utils\DataVerifier;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\DependencyInjection\Container;

/**
 * @deprecated Use the Levenshtein Typo Verifier (which use the Levenshtein algorithm)
 * Class TypoVerifier
 * @package BeneficiaryBundle\Utils\DataVerifier
 */
class TypoVerifier extends AbstractVerifier
{

    /**
     * Minimum percent to detect a similar household
     * @var int
     */
    private $minimumPercentSimilar = 90;

    /** @var Container $container */
    private $container;

    private $token;

    /** @var array $listHouseholdsSaved */
    private $listHouseholdsSaved;

    /** @var array $mappingHouseholdAndHead */
    private $mappingHouseholdAndHead;


    /**
     * TypoVerifier constructor.
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
     * @return array|bool
     * @throws \Exception
     */
    public function verify(string $countryISO3, array $householdArray, int $cacheId)
    {
        $householdRepository = $this->em->getRepository(Household::class);
        $beneficiaryRepository = $this->em->getRepository(Beneficiary::class);
        if (null === $this->listHouseholdsSaved)
        {
            $this->listHouseholdsSaved = $householdRepository
                ->getAllBy($countryISO3, [], [
                    'hh.id',
                    'hh.addressStreet',
                    'hh.addressNumber',
                    'hh.addressPostcode'
                ]);
        }
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

        $similarHousehold = null;
        $percent = $this->minimumPercentSimilar;
        /** @var Household $oldHousehold */
        foreach ($this->listHouseholdsSaved as $oldHousehold)
        {
            if (null === $this->mappingHouseholdAndHead || !array_key_exists($oldHousehold['id'], $this->mappingHouseholdAndHead))
            {
                // Get the head of the current household
                /** @var Beneficiary $oldHead */
                $oldHead = $beneficiaryRepository->getHeadOfHouseholdId($oldHousehold['id']);
                if (!$oldHead instanceof Beneficiary)
                    continue;
                $this->mappingHouseholdAndHead['id'] = $oldHead;
            }

            $oldHead = $this->mappingHouseholdAndHead['id'];

            similar_text(
                $householdArray["address_street"] .
                $householdArray["address_number"] .
                $householdArray["address_postcode"] .
                $newHead["given_name"] .
                $newHead["family_name"],
                $oldHousehold['addressStreet'] .
                $oldHousehold['addressNumber'] .
                $oldHousehold['addressPostcode'] .
                $oldHead->getGivenName() .
                $oldHead->getFamilyName(),
                $tmpPercent
            );

            if (100 == $tmpPercent)
            {
                // SAVE 100% SIMILAR IN 1_typo
                $this->saveInCache(
                    'mapping_new_old',
                    $cacheId,
                    $householdArray,
                    $householdRepository->find($oldHousehold['id'])
                );
                return false;
            }
            elseif ($percent < $tmpPercent)
            {
                $similarHousehold = $oldHousehold;
                $percent = $tmpPercent;
            }
        }
        if ($this->minimumPercentSimilar < $percent)
        {
            $return = [
                "old" => $householdRepository->find($similarHousehold['id']),
                "new" => $householdArray, "id_tmp_cache" => $cacheId
            ];
            return $return;
        }
        $this->saveInCache('no_typo', $cacheId, $householdArray, null);
        return null;
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
            $arrayNewHousehold = json_decode($this->container->get('jms_serializer')
                ->serialize($household, 'json', SerializationContext::create()->setSerializeNull(true)), true);
        else
            $arrayNewHousehold = json_encode([]);

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

        $listHH[$cacheId] = ["new" => $dataToSave, "old" => $arrayNewHousehold, "id_tmp_cache" => $cacheId];
        file_put_contents($dir_var_token . '/' . $step, json_encode($listHH));

    }
}