<?php


namespace BeneficiaryBundle\Utils\DataTreatment;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\BeneficiaryService;
use BeneficiaryBundle\Utils\DataVerifier\DuplicateVerifier;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use ProjectBundle\Entity\Project;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Cache\Simple\FilesystemCache;

class TypoTreatment extends AbstractTreatment
{

    /**
     * Treat the typo issues
     * The frontend returns:
     * [
     *     {
     *         id_old: '',
     *         index: '', (not used in back)
     *         id_tmp_cache: '',
     *         state: '',
     *         new: []
     *     }
     * ]
     * @param Project $project
     * @param array $householdsArray
     * @param string $email
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     * @throws \Exception
     */
    public function treat(Project $project, array $householdsArray, string $email)
    {
        foreach ($householdsArray as $index => $householdArray) {
            // Get old household
            $oldHousehold = $this->em->getRepository(Household::class)->find($householdArray['id_old']);
            $oldHousehold = json_decode(
                $this->container->get('jms_serializer')->serialize(
                        $oldHousehold,
                        'json',
                        SerializationContext::create()->setSerializeNull(true)->setGroups(['FullHousehold'])
                    ),
                true
            );
            
            // If state is true and there is no new we keep the old household
            if (boolval($householdArray['state']) && (!array_key_exists('new', $householdArray) || empty($householdArray['new']))) {
                // save in update cache new as empty array and old as the existing household
                $this->saveInCache('to_update', $householdArray['id_tmp_cache'], [], $email, $oldHousehold);
            }
            
            // If state is false and new contains a household array we update the old with the data from the new (the one in the file)
            elseif (!boolval($householdArray['state']) && array_key_exists('new', $householdArray) && !empty($householdArray['new'])) {
                // save in update cache new houshold and old as the previous existing one
                $this->saveInCache('to_update', $householdArray['id_tmp_cache'], $householdArray['new'], $email, $oldHousehold);
                
            } 
            // If state is true and new contains a household array we don't change the old and create a new household
            elseif (boolval($householdArray['state']) && array_key_exists('new', $householdArray) && $householdArray['new'] !== null) {
                // save in create cache new as new household array and old as empty
                $this->saveInCache('to_create', $householdArray['id_tmp_cache'], $householdArray['new'], $email, []);
            }
            unset($householdsArray[$index]);
        }
        
        return $this->getFromCache('to_update', $email);
    }
}
