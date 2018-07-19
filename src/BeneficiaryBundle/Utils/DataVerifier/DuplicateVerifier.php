<?php


namespace BeneficiaryBundle\Utils\DataVerifier;


use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Container;

class DuplicateVerifier extends AbstractVerifier
{

    private $token;

    private $container;

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
     * @return array|null
     * @throws \Exception
     */
    public function verify(string $countryISO3, array $householdArray, int $cacheId)
    {
        dump($householdArray);
        $oldBeneficiaries = $this->em->getRepository(Beneficiary::class)->findByCriteria($countryISO3, []);
        // GET THE SIMILAR HOUSEHOLD FROM THE DB, IF ISSET
        if (array_key_exists('id_tmp_cache', $householdArray))
            $similarOldHousehold = $this->getOldHouseholdFromCache($householdArray['id_tmp_cache']);
        else
            $similarOldHousehold = null;

        $listDuplicateBeneficiaries = [];
        $newHouseholdEmpty = $householdArray['new'];
        $newHouseholdEmpty['beneficiaries'] = [];
        foreach ($householdArray['new']['beneficiaries'] as $newBeneficiary)
        {
            $stringOldHousehold = strtolower(trim($newBeneficiary['given_name']) . "//" . trim($newBeneficiary['family_name']));
            /** @var Beneficiary $oldBeneficiary */
            foreach ($oldBeneficiaries as $oldBeneficiary)
            {
                if (
                    $oldBeneficiary->getHousehold()->getId() !== $similarOldHousehold['id']
                    &&
                    strtolower(trim($oldBeneficiary->getGivenName()) . "//" . trim($oldBeneficiary->getFamilyName()))
                    ===
                    $stringOldHousehold
                )
                {
                    $newHouseholdEmpty['beneficiaries'][] = $newBeneficiary;
                    $arrayTmp = [
                        "new" => $newHouseholdEmpty,
                        "old" => $oldBeneficiary->getHousehold()->resetBeneficiaries()->addBeneficiary($oldBeneficiary)
                    ];


                    $listDuplicateBeneficiaries[] = $arrayTmp;
                    break;
                }
            }
            $newHouseholdEmpty['beneficiaries'] = [];
        }

        if (!empty($listDuplicateBeneficiaries))
        {
            if (array_key_exists("id_tmp_cache", $householdArray))
                return [
                    "new_household" => $householdArray['new'],
                    "id_tmp_cache" => $householdArray["id_tmp_cache"],
                    "data" => $listDuplicateBeneficiaries
                ];

            return [
                "new_household" => $householdArray['new'],
                "data" => $listDuplicateBeneficiaries
            ];
        }

        return null;
    }

    /**
     * @param $id_tmp_cache
     * @return null
     * @throws \Exception
     */
    private function getOldHouseholdFromCache($id_tmp_cache)
    {
        if (null === $this->token)
            return null;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);
        $dir_mapping = $dir_var . '/mapping_new_old';
        if (!is_file($dir_mapping))
            return null;

        $fileContent = file_get_contents($dir_var . '/mapping_new_old');
        $householdsCached = json_decode($fileContent, true);
        if (array_key_exists($id_tmp_cache, $householdsCached))
            return $householdsCached[$id_tmp_cache]['old'];

        return null;
    }
}