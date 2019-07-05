<?php


namespace BeneficiaryBundle\Utils\DataTreatment;

use BeneficiaryBundle\Entity\Household;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\HttpFoundation\Response;

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
    public function treat(Project $project, array &$householdsArray, string $email)
    {
        $createdHouseholds = $this->createHouseholds($project, $email);
        $updatedHouseholds = $this->updateHouseholds($project, $email);

        return array_merge($createdHouseholds, $updatedHouseholds);
    }

    /**
     * @throws \Exception
     */
    private function createHouseholds(Project &$project, string $email)
    {
        $householdsToCreate = $this->getFromCache('to_create', $email) ?: [];
        $createdHouseholds  = [];

        foreach ($householdsToCreate as $index => $household) {
            $createdHouseholds[] = $this->householdService->createOrEdit($household['new'], [$project], null, false);

            if ($index !== 0 && $index % 300 === 0) {
                $this->em->flush();
            }
        }

        return $createdHouseholds;
    }

    /**
     * @throws \Exception
     */
    private function updateHouseholds(Project &$project, string $email)
    {
        $householdsToUpdate = $this->getFromCache('to_update', $email) ?: [];
        $householdsUpdated  = [];
        $householdsIds      = [];

        foreach ($householdsToUpdate as $household) {
            $householdsIds[] = $household['old']['id'];
        }

        $oldHouseholds = $this->em->getRepository(Household::class)->getAllByIds($householdsIds);

        foreach ($oldHouseholds as $index => $oldHousehold) {
            if (! empty($household['new']) && ! array_key_exists('id', $household['new'])) {
                $household = $this->householdService->createOrEdit($household['new'], array($project), $oldHousehold, false);
                // If household was not previously managed and was fetched from database for duplication
            } elseif (! empty($household['new']) && array_key_exists('id', $household['new'])) {
                $household = $this->householdService->removeBeneficiaries($household['new']);
            } else {
                $this->householdService->addToProject($oldHousehold, $project);
                $household = $oldHousehold;
            }

            if ($index !== 0 && $index % 300 === 0) {
                $this->em->flush();
            }

            $householdsUpdated[] = $household;
        }

        return $householdsUpdated;
    }
}
