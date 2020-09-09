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
    public function verify(string $countryISO3, array &$householdArray, int $cacheId, string $email)
    {
        // Get the old household the new one corresponds to if it exists
        $similarOldHousehold = $householdArray['old'] ?? null;

        // Initialize the list of potential duplicates
        $listDuplicateBeneficiaries = [];
        
        if(empty($householdArray['new']) || empty($householdArray['new']['beneficiaries'])) {
            return $listDuplicateBeneficiaries;
        }

        foreach ($householdArray['new']['beneficiaries'] as $newBeneficiary) {

            /** @var Beneficiary[] $existingBeneficiaries */
            $existingBeneficiaries = $this->em->getRepository(Beneficiary::class)->findByName(
                trim($newBeneficiary['local_given_name']),
                trim($newBeneficiary['local_family_name'])
            );

            $match = false;

            foreach ($existingBeneficiaries as $existingBeneficiary) {
                if ($similarOldHousehold && $existingBeneficiary->getHousehold()->getId() === $similarOldHousehold['id']) {
                    $match = true;
                }
            }

            if (! $match && ! empty($existingBeneficiaries)) {
                // reset the existing household's beneficiaries to include only the duplicate
                $oldHousehold = json_decode(
                    $this->container->get('serializer')->serialize(
                        $existingBeneficiaries[0]->getHousehold(),
                        'json',
                        ['groups' => ['FullHousehold'], 'datetime_format' => 'd-m-Y']
                    ),
                    true
                );

                $arrayTmp = [
                    'new'           => $newBeneficiary,
                    'old'           => $existingBeneficiaries[0],
                    'id_tmp_cache'  => $householdArray['id_tmp_cache'],
                    'new_household' => $householdArray['new'],
                    'old_household' => $oldHousehold
                ];

                $listDuplicateBeneficiaries[] = $arrayTmp;
            }
        }

        return $listDuplicateBeneficiaries;
    }
}
