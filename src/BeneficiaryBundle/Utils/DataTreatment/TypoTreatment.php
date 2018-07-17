<?php


namespace BeneficiaryBundle\Utils\DataTreatment;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\BeneficiaryService;
use BeneficiaryBundle\Utils\DataVerifier\DuplicateVerifier;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use Symfony\Component\DependencyInjection\Container;

class TypoTreatment extends AbstractTreatment
{

    /** @var string $token */
    private $token;

    /** @var Container $container */
    private $container;

    public function __construct(
        EntityManagerInterface $entityManager,
        HouseholdService $householdService,
        BeneficiaryService $beneficiaryService,
        Container $container,
        string &$token = null)
    {
        parent::__construct($entityManager, $householdService, $beneficiaryService);
        $this->token = $token;
        $this->container = $container;
    }

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
        foreach ($householdsArray as $index => $householdArray)
        {
            // CASE STATE IS TRUE AND NEW IS MISSING => WE KEEP ONLY THE OLD HOUSEHOLD, AND WE ADD IT TO THE CURRENT PROJECT
            if (boolval($householdArray['state']) && (!array_key_exists("new", $householdArray) || $householdArray['new'] === null))
            {
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
                    $this->householdService->update($oldHousehold, $project, $householdArray['new'], false);
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
                            $this->beneficiaryService->updateOrCreate($oldHousehold, $newHeadHH, true);
                    }
                }
            }

            if (array_key_exists('__country', $householdArray['new']))
                unset($householdArray['new']['__country']);

            // WE SAVE EVERY HOUSEHOLD WHICH HAVE BEEN TREATED BY THIS FUNCTION BECAUSE IN NEXT STEP WE HAVE TO KNOW WHICH
            // HOUSEHOLDS HAD TYPO ERRORS
            $this->saveInCache('1b', $householdArray['new']);
            $listHouseholds[] = $householdArray['new'];
        }
        return $listHouseholds;
    }

    /**
     * @param int $step
     * @param $dataToSave
     * @param string|null $token
     * @throws \Exception
     */
    private function saveInCache(string $step, array $dataToSave)
    {
        $sizeToken = 50;
        if (null === $this->token)
            $this->token = bin2hex(random_bytes($sizeToken));

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);
        if (!is_file($dir_var . '/step_' . $step))
        {
            file_put_contents($dir_var . '/step_' . $step, json_encode([$dataToSave]));
        }
        else
        {
            $listHH = json_decode(file_get_contents($dir_var . '/step_' . $step), true);
            $listHH[] = $dataToSave;
            file_put_contents($dir_var . '/step_' . $step, json_encode($dataToSave));
        }

    }
}