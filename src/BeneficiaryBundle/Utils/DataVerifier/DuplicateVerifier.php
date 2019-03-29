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
        // Get the old household the new one corresponds to if it exists
        if (array_key_exists('old', $householdArray) && ! empty($householdArray['old'])) {
            $similarOldHousehold = $householdArray['old'];
        } else {
            $similarOldHousehold = null;
        }

        // Initialize the list of potential duplicates
        $listDuplicateBeneficiaries = [];
        
        // Duplicate the new household array
        $newHouseholdSingleBeneficiary = $householdArray['new'];
        
        foreach ($householdArray['new']['beneficiaries'] as $newBeneficiary) {
            // reset the new households beneficiaries
            $newHouseholdSingleBeneficiary['beneficiaries'] = [];
            
            // get beneficiaries with the same first name and last name
            $existingBeneficaries = $this->em->getRepository(Beneficiary::class)->findBy(
                [
                    'givenName'  => trim($newBeneficiary['given_name']),
                    'familyName' => trim($newBeneficiary['family_name'])
                ]
            );
            foreach ($existingBeneficaries as $existingBeneficary) {
                // if there is one in a different household than the new househlold, it's a potential duplicate
                if (! $similarOldHousehold || $existingBeneficary->getHousehold()->getId() !== $similarOldHousehold['id']) {
                    $newHouseholdSingleBeneficiary['beneficiaries'][] = $newBeneficiary;
                    
                    // reset the existing household's beneficiaries to include only the duplicate
                    $oldHousehold = json_decode(
                        $this->container->get('jms_serializer')->serialize(
                                $existingBeneficary->getHousehold(),
                                'json',
                                SerializationContext::create()->setSerializeNull(true)->setGroups(['FullHousehold'])
                            ),
                        true
                    );
                    $oldHousehold['beneficiaries'] = [$existingBeneficary];
                    
                    $arrayTmp = [
                        'new'           => $newHouseholdSingleBeneficiary,
                        'old'           => $oldHousehold,
                        'id_tmp_cache'  => $householdArray['id_tmp_cache'],
                        'new_household' => $householdArray['new']
                    ];
                    
                    $listDuplicateBeneficiaries[] = $arrayTmp;
                    break;
                }
            }
        }

        return $listDuplicateBeneficiaries;
    }
}
