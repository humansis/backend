<?php


namespace BeneficiaryBundle\Utils\DataTreatment;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Cache\Simple\FilesystemCache;

class DuplicateTreatment extends AbstractTreatment
{

    /**
     * Treat the typo issues
     * The frontend returns:
     * {
     *  errors:
     *     [
     *         {
     *             old: {},
     *             new: {},
     *             old_household: {},
     *             new_household: {},
     *             id_tmp_cache: int,
     *             state: int
     *         }
     *     ]
     * }
     * @param Project $project
     * @param array $householdsArray
     * @param string $email
     * @return string
     * @throws \Exception
     */
    public function treat(Project $project, array $householdsArray, string $email)
    {
        foreach ($householdsArray as $index => $householdArray) {
            $idOldBeneficiary = $householdArray['old']['id'];

            // If state is either keep new or keep both save the new one from the file (already in cache)
            if ($householdArray['state'] > 0) {
                // if state is keep new remove the old beneficiary
                if ($householdArray['state'] === 1) {
                    // Get old household, check if it already exists in the cache first
                    $oldHousehold = $this->getItemFromCache('to_update', $householdArray['id_tmp_cache'] . '-' . $index, $email);
                    if (empty($oldHousehold)) {
                        $oldHousehold = $householdArray['old_household'];
                    }

                    // Remove beneficiary
                    $householdRemovedBeneficiary = $oldHousehold;
                    foreach ($householdRemovedBeneficiary['beneficiaries'] as $index => $beneficiary) {
                        if ($beneficiary['id'] === $idOldBeneficiary) {
                            // if the beneficiary is head, throw an error
                            if ($beneficiary['status']) {
                                throw new \Exception('This beneficiary is a head of household. You can\'t delete them.');
                            }
                            unset($householdRemovedBeneficiary['beneficiaries'][$index]);
                            break;
                        }
                    }
                    // Save to update the existing household with its removed beneficiary
                    // Create a new id_tmp_cache as we are modifying a household that wasn't previously managed
                    $this->saveInCache('to_update', $householdArray['id_tmp_cache'] . '-' . $index, $householdRemovedBeneficiary, $email, $oldHousehold);
                }
            }
            // If state is keep old remove the beneficiary from the new one
            else {
                $duplicatedBeneficiary = $this->em->getRepository(Beneficiary::class)->find($idOldBeneficiary);
                foreach ($householdArray['new_household']['beneficiaries'] as $index => $beneficiary) {
                    if ($beneficiary['given_name'] === $householdArray['new']['given_name'] &&
                        $beneficiary['family_name'] === $householdArray['new']['family_name']) {
                        // if the beneficiary is head, throw an error
                        if ($beneficiary['status']) {
                            throw new \Exception('This beneficiary is a head of household. You can\'t delete them.');
                        }
                        unset($householdArray['new_household']['beneficiaries'][$index]);
                        break;
                    }
                    // Save to update the new household with its removed beneficiary
                    $this->updateInCache($householdArray['id_tmp_cache'], $householdArray['new_household'], $email);
                }

            }
        }

        return 'Done';
    }
}
