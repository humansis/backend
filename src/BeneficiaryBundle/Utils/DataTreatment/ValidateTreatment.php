<?php


namespace BeneficiaryBundle\Utils\DataTreatment;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Beneficiary;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Cache\Simple\FilesystemCache;

class ValidateTreatment extends AbstractTreatment
{

    /**
     * Save the households in the cache into the database
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
        $to_create = $this->getFromCache('to_create', $email) ?: [];
        $to_update = $this->getFromCache('to_update', $email) ?: [];

        $householdsAdded = [];


        foreach ($to_create as $i => $household) {
            dump($to_create);
            $household = $this->householdService->createOrEdit($household['new'], array($project), null);
            $householdsAdded[] = $household;
        }

        foreach ($to_update as $i => $household) {
            $oldHousehold = $this->em->getRepository(Household::class)->find($household['old']['id']);
            if (! empty($household['new']) && ! array_key_exists('id', $household['new'])) {
                $household = $this->householdService->createOrEdit($household['new'], array($project), $oldHousehold);
            // If household was not previously managed and was fetched from database for duplication
            } elseif (! empty($household['new']) && array_key_exists('id', $household['new'])) {
                $household = $this->householdService->removeBeneficiaries($household['new']);
            } else {
                $this->householdService->addToProject($oldHousehold, $project);
                $household = $oldHousehold;
            }
            $householdsAdded[] = $household;
        }

        return $householdsAdded;
    }
}
