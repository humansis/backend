<?php


namespace BeneficiaryBundle\Utils\DataTreatment;

use ProjectBundle\Entity\Project;

class TypoTreatment extends AbstractTreatment
{

    /**
     * Treat the typo issues
     * The frontend returns:
     * {
     *  errors:
     *     [
     *         {
     *             old: [],
     *             new: [],
     *             id_tmp_cache: int,
     *             state: int
     *         }
     *     ]
     * }
     * @param Project $project
     * @param array $householdsArray
     * @param string $email
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RA\RequestValidatorBundle\RequestValidator\ValidationException
     * @throws \Exception
     */
    public function treat(Project $project, array &$householdsArray, string $email)
    {

        foreach ($householdsArray as $index => $householdArray) {
            // Get old household
//            $oldHousehold = $this->em->getRepository(Household::class)->find($householdArray['id_old']);
//            $oldHousehold = json_decode(
//                $this->container->get('jms_serializer')->serialize(
//                        $oldHousehold,
//                        'json',
//                        SerializationContext::create()->setSerializeNull(true)->setGroups(['FullHousehold'])
//                    ),
//                true
//            );

            // If state is equal to 0, keep the old household
            if ($householdArray['state'] === 0) {
                // save in update cache new as empty array and old as the existing household
                $this->saveInCache('to_update', $householdArray['id_tmp_cache'], [], $email, $householdArray['old']);
            }

            // If state is equal to 1, keep the new household
            elseif ($householdArray['state'] === 1) {
                // save in update cache new household and old as the previous existing one
                $this->saveInCache('to_update', $householdArray['id_tmp_cache'], $householdArray['new'], $email, $householdArray['old']);
                
            }
            // If state is equal to 2, keep both households
            elseif ($householdArray['state'] === 2) {
                // save in create cache new as new household array and old as empty
                $this->saveInCache('to_create', $householdArray['id_tmp_cache'], $householdArray['new'], $email, []);
            }
            unset($householdsArray[$index]);
        }
        
        return $this->getFromCache('to_update', $email);
    }
}
