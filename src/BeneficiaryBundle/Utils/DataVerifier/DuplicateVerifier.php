<?php


namespace BeneficiaryBundle\Utils\DataVerifier;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
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
        
        if(!empty($householdArray['new'])) {

            foreach ($householdArray['new']['beneficiaries'] as $newBeneficiary) {
                // get beneficiaries with the same first name and last name
                $existingBeneficiaries = $this->em->getRepository(Beneficiary::class)->findByUnarchived(
                    [
                        'localGivenName'  => trim($newBeneficiary['local_given_name']),
                        'localFamilyName' => trim($newBeneficiary['local_family_name'])
                    ]
                );
                foreach ($existingBeneficiaries as $existingBeneficiary) {
                    // if there is one in a different household than the new household, it's a potential duplicate
                    if (! $similarOldHousehold || $existingBeneficiary->getHousehold()->getId() !== $similarOldHousehold['id']) {
                        $newHouseholdSingleBeneficiary['beneficiaries'][] = $newBeneficiary;

                        // reset the existing household's beneficiaries to include only the duplicate
                        $oldHousehold = json_decode(
                            $this->container->get('jms_serializer')->serialize(
                                    $existingBeneficiary->getHousehold(),
                                    'json',
                                    SerializationContext::create()->setSerializeNull(true)->setGroups(['FullHousehold'])
                                ),
                            true
                        );

                        $arrayTmp = [
                            'new'           => $newBeneficiary,
                            'old'           => $existingBeneficiary,
                            'id_tmp_cache'  => $householdArray['id_tmp_cache'],
                            'new_household' => $householdArray['new'],
                            'old_household' => $oldHousehold
                        ];

                        $listDuplicateBeneficiaries[] = $arrayTmp;
                        break;
                    }
                }
            }

        }
        return $listDuplicateBeneficiaries;
    }
}
