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
     * @param string $email
     * @return array|null
     * @throws \Exception
     */
    public function verify(string $countryISO3, array $householdArray, int $cacheId, string $email)
    {
        // GET THE SIMILAR HOUSEHOLD FROM THE DB, IF ISSET
        if (array_key_exists('old', $householdArray)) {
            $similarOldHousehold = $householdArray['old'];
        } else {
            $similarOldHousehold = $this->getOldHouseholdFromCache($householdArray['id_tmp_cache'], $email);
        }

        $listDuplicateBeneficiaries = [];
        
        $newHouseholdEmpty = $householdArray['new'];
        $newHouseholdEmpty['beneficiaries'] = [];
        
        foreach ($householdArray['new']['beneficiaries'] as $newBeneficiary)
        {
            $existingBeneficaries = $this->em->getRepository(Beneficiary::class)->findBy(
                [
                    'givenName' => trim($newBeneficiary['given_name']),
                    'familyName' => trim($newBeneficiary['family_name'])
                ]
            );
            foreach ($existingBeneficaries as $existingBeneficary) {
                if ($existingBeneficary->getHousehold()->getId() !== $similarOldHousehold['id']) {
                    $newHouseholdEmpty['beneficiaries'][] = $newBeneficiary;
                    
                    $clonedHH = clone $existingBeneficary->getHousehold();
                    $old = $clonedHH->resetBeneficiaries()->addBeneficiary($existingBeneficary);
                    
                    $arrayTmp = [
                        "new" => $newHouseholdEmpty,
                        "old" => $old,
                        "id_tmp_cache" => $householdArray["id_tmp_cache"],
                        "new_household" => $householdArray["new"]
                    ];
                    
                    $listDuplicateBeneficiaries[] = $arrayTmp;
                    break;
                }
            }
            $newHouseholdEmpty['beneficiaries'] = [];
        }

        if (!empty($listDuplicateBeneficiaries)) {
            return $listDuplicateBeneficiaries;
        }
        return null;
    }

    /**
     * @param $id_tmp_cache
     * @param string $email
     * @return null
     * @throws \Exception
     */
    private function getOldHouseholdFromCache($id_tmp_cache, string $email)
    {
        if (null === $this->token)
            return null;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);
        $dir_mapping = $dir_var . '/' . $email . '-mapping_new_old';
        if (!is_file($dir_mapping))
            return null;

        $fileContent = file_get_contents($dir_var . '/' . $email . '-mapping_new_old');
        $householdsCached = json_decode($fileContent, true);
        if (array_key_exists($id_tmp_cache, $householdsCached))
            return $householdsCached[$id_tmp_cache]['old'];

        return null;
    }
}