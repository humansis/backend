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

        // Get the ids of the households to update
        foreach ($householdsToUpdate as $household) {
            $householdsIds[] = $household['old']['id'];
        }

        // Fetch the households from the database
        /** @var Household[] $oldHouseholds */
        $oldHouseholds = $this->em->getRepository(Household::class)->getAllByIds($householdsIds);

        // Create a hash id => household
        $oldHouselholdsMap = [];
        foreach ($oldHouseholds as $oldHousehold) {
            $oldHouselholdsMap[$oldHousehold->getId()] = $oldHousehold;
        }

        foreach ($householdsToUpdate as $index => $household) {
            // Get the household fetched from the database
            $oldHousehold = $oldHouselholdsMap[$household['old']['id']];

            // If the household is new, create it
            if (! empty($household['new']) && ! array_key_exists('id', $household['new'])) {
                $household = $this->householdService->createOrEdit($household['new'], array($project), $oldHousehold, false);
            }
            // If the household is in new but has an id, delete its beneficiaries
            elseif (! empty($household['new']) && array_key_exists('id', $household['new'])) {
                $household = $this->householdService->removeBeneficiaries($household['new']);
            }
            // Else add the household to the given project
            else {
                $this->householdService->addToProject($oldHousehold, $project);
                $household = $oldHousehold;
            }

            // Flush every N modifications
            if ($index !== 0 && $index % 300 === 0) {
                $this->em->flush();
            }

            $householdsUpdated[] = $household;
        }

        return $householdsUpdated;
    }
}
