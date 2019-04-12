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
     * Treat the duplicate issues
     * The frontend returns:
     * [
     *     {
     *         id_old: '',
     *         id_duplicate: '', (not used in back)
     *         to_delete: '', (not used in back)
     *         id_tmp_cache: '',
     *         state: '', (1 = keep the old beneficiary, 0 = delete it)
     *         new: []
     *     }
     * ]
     *
     * @param Project $project
     * @param array $householdsArray
     * @param string $email
     * @return string
     * @throws \Exception
     */
    public function treat(Project $project, array $householdsArray, string $email)
    {
        foreach ($householdsArray as $index => $householdArray) {
            $idOldBeneficiary = $householdArray['old']['beneficiaries'][0]['id'];

            // If state is either keep new or keep both save the new one from the file (already in cache)
            if ($householdArray['state'] > 0) {
                // if state is keep new remove the old beneficiary
                if ($householdArray['state'] === 1) {
                    // Get old household, check if it already exists in the cache first

                    $oldHousehold = $this->getItemFromCache('to_update', $householdArray['id_tmp_cache'] . '-' . $index, $email);

                    if (empty($oldHousehold)) {
                        $oldHousehold = $this->em->getRepository(Household::class)->find($householdArray['old']['id']);

                        $oldHousehold = json_decode(
                            $this->container->get('jms_serializer')->serialize(
                                $oldHousehold,
                                'json',
                                SerializationContext::create()->setSerializeNull(true)->setGroups(['FullHousehold'])
                            ),
                            true
                        );
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
                foreach ($householdArray['new']['beneficiaries'] as $index => $beneficiary) {
                    if ($beneficiary['given_name'] === $duplicatedBeneficiary->getGivenName() &&
                        $beneficiary['family_name'] === $duplicatedBeneficiary->getFamilyName()) {
                        // if the beneficiary is head, throw an error
                        if ($beneficiary['status']) {
                            throw new \Exception('This beneficiary is a head of household. You can\'t delete them.');
                        }
                        unset($householdArray['new']['beneficiaries'][$index]);
                        break;
                    }
                    // Save to update the new household with its removed beneficiary
                    $this->updateInCache($householdArray['id_tmp_cache'], $householdArray['new'], $email);
                }

            }
        }

        return 'Done';

    }
}
