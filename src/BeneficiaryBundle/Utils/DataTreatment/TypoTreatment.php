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

class TypoTreatment extends AbstractTreatment
{

    /**
     * ET RETURN ONLY IF WE ADD THE NEW
     * @param Project $project
     * @param array $householdsArray
     * @return array
     * @throws \Exception
     */
    public function treat(Project $project, array $householdsArray)
    {
        $listHouseholds = [];
        $id_tmp = 1;
        foreach ($householdsArray as $index => $householdArray)
        {
            // CASE STATE IS TRUE AND NEW IS MISSING => WE KEEP ONLY THE OLD HOUSEHOLD, AND WE ADD IT TO THE CURRENT PROJECT
            if (boolval($householdArray['state']) && (!array_key_exists("new", $householdArray) || $householdArray['new'] === null))
            {
                $oldHousehold = $this->em->getRepository(Household::class)->find($householdArray['id_old']);
                $this->householdService->addToProject($oldHousehold, $project);
                unset($householdsArray[$index]);
                continue;
            }
            // IF STATE IS FALSE AND NEW CONTAINS A ARRAY OF HOUSEHOLD => WE UPDATE THE OLD WITH DATA FROM THE NEW
            elseif (!boolval($householdArray['state']) && array_key_exists("new", $householdArray) && $householdArray['new'] !== null)
            {
                $oldHousehold = $this->em->getRepository(Household::class)->find($householdArray['id_old']);
                if ($oldHousehold instanceof Household)
                {
                    // Update only the object Household
                    $this->householdService->update($oldHousehold, $project, $householdArray['new'], false);
                    // Found data in order to update the head of this household
                    $oldHeadHH = $this->em->getRepository(Beneficiary::class)->getHeadOfHousehold($oldHousehold);
                    if ($oldHeadHH instanceof Beneficiary)
                    {
                        $newHeadHH = null;
                        foreach ($householdArray['new']['beneficiaries'] as $newBeneficiary)
                        {
                            if (boolval($newBeneficiary['status']))
                            {
                                $newHeadHH = $newBeneficiary;
                                $newHeadHH['id'] = $oldHeadHH->getId();
                                break;
                            }
                        }
                        if (null !== $newHeadHH)
                            // Update the head
                            $this->beneficiaryService->updateOrCreate($oldHousehold, $newHeadHH, true);
                    }
                }
                $householdArray['new']['id_tmp_cache'] = $id_tmp;
                $this->saveInCache('1_typo', $householdArray['new'], $oldHousehold, $id_tmp);
                $id_tmp++;
            }


            // WE SAVE EVERY HOUSEHOLD WHICH HAVE BEEN TREATED BY THIS FUNCTION BECAUSE IN NEXT STEP WE HAVE TO KNOW WHICH
            // HOUSEHOLDS HAD TYPO ERRORS
            $listHouseholds[] = $householdArray['new'];
        }
        $this->getFromCache($listHouseholds);
        return $listHouseholds;
    }

    /**
     * @param string $step
     * @param array $dataToSave
     * @param Household $household
     * @param $index
     * @throws \Exception
     */
    private function saveInCache(string $step, array $dataToSave, Household $household, $index)
    {
        $arrayNewHousehold = json_decode($this->container->get('jms_serializer')
            ->serialize($household, 'json', SerializationContext::create()->setSerializeNull(true)), true);

        $sizeToken = 50;
        if (null === $this->token)
            $this->token = bin2hex(random_bytes($sizeToken));

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);
        if (!is_file($dir_var . '/step_' . $step))
        {
            file_put_contents($dir_var . '/step_' . $step, json_encode([$index => ["new" => $dataToSave, "old" => $arrayNewHousehold]]));
        }
        else
        {
            $listHH = json_decode(file_get_contents($dir_var . '/step_' . $step), true);
            $listHH[$index] = ["new" => $dataToSave, "old" => $arrayNewHousehold];
            file_put_contents($dir_var . '/step_' . $step, json_encode($listHH));
        }

    }

    /**
     * @param array $listHouseholdsArray
     * @throws \Exception
     */
    private function getFromCache(array &$listHouseholdsArray)
    {
        if (null === $this->token)
            return;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);

        $fileContent = file_get_contents($dir_var . '/step_1');
        $householdsCached = json_decode($fileContent, true);
        foreach ($householdsCached as $householdCached)
        {
            $listHouseholdsArray[] = $householdCached;
        }
    }
}