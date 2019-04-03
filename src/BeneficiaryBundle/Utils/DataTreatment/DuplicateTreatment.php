<?php


namespace BeneficiaryBundle\Utils\DataTreatment;

use BeneficiaryBundle\Entity\Beneficiary;
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
     * @return array|Response
     * @throws ValidationException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    public function treat(Project $project, array $householdsArray, string $email)
    {
        dump('treat');
        foreach ($householdsArray as $index => $householdArray) {            
            // If there is data in new save the new one from the file (already in cache)
            if (array_key_exists('new', $householdArray) && ! empty($householdArray['new'])) {
                // if state is false remove the old beneficiary
                if (! boolval($householdArray['state'])) {
                    // Get old household, check if it already exists in the cache first
                    $oldHousehold = $this->getItemFromCache('to_update', $householdArray['id_tmp_cache'] . '-' . $index, $email);
                    if (empty($oldHousehold)) {
                        $oldHousehold = $this->em->getRepository(Beneficiary::class)->find($householdArray['id_old'])->getHousehold();
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
                        if ($beneficiary['id'] === $householdArray['id_old']) {
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
            // If there is no new remove the beneficiary from the new one
            else {
                $duplicatedBeneficiary = $this->em->getRepository(Beneficiary::class)->find($householdArray['id_old']);
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
        dump('done');

        return 'Done';
    }
}
