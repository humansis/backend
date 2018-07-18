<?php


namespace BeneficiaryBundle\Utils;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\VulnerabilityCriterion;
use BeneficiaryBundle\Model\ImportStatistic;
use BeneficiaryBundle\Model\IncompleteLine;
use BeneficiaryBundle\Utils\DataTreatment\AbstractTreatment;
use BeneficiaryBundle\Utils\DataTreatment\DuplicateTreatment;
use BeneficiaryBundle\Utils\DataTreatment\LessTreatment;
use BeneficiaryBundle\Utils\DataTreatment\MoreTreatment;
use BeneficiaryBundle\Utils\DataTreatment\TypoTreatment;
use BeneficiaryBundle\Utils\DataVerifier\AbstractVerifier;
use BeneficiaryBundle\Utils\DataVerifier\DuplicateVerifier;
use BeneficiaryBundle\Utils\DataVerifier\LessVerifier;
use BeneficiaryBundle\Utils\DataVerifier\MoreVerifier;
use BeneficiaryBundle\Utils\DataVerifier\TypoVerifier;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use ProjectBundle\Entity\Project;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Kernel;

class HouseholdCSVService
{

    /**
     * @var EntityManagerInterface $em
     */
    private $em;

    /**
     * @var HouseholdService $householdService
     */
    private $householdService;

    /** @var Mapper $mapper */
    private $mapper;

    /** @var Container $container */
    private $container;

    /** @var BeneficiaryService $beneficiaryService */
    private $beneficiaryService;

    /** @var string $token */
    private $token;


    public function __construct(
        EntityManagerInterface $entityManager,
        HouseholdService $householdService,
        BeneficiaryService $beneficiaryService,
        Mapper $mapper,
        Container $container
    )
    {
        $this->em = $entityManager;
        $this->householdService = $householdService;
        $this->beneficiaryService = $beneficiaryService;
        $this->mapper = $mapper;
        $this->container = $container;
    }


    /**
     * Defined the reader and transform CSV to array
     *
     * @param $countryIso3
     * @param Project $project
     * @param UploadedFile $uploadedFile
     * @param int $step
     * @param $token
     * @return array
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function saveCSV($countryIso3, Project $project, UploadedFile $uploadedFile, int $step, $token)
    {
        // If it's the first step, we transform CSV to array mapped for corresponding to the entity Household
        // LOADING CSV
        $reader = new Csv();
        $reader->setDelimiter(",");
        $worksheet = $reader->load($uploadedFile->getRealPath())->getActiveSheet();
        $sheetArray = $worksheet->toArray(null, true, true, true);

        // Get the list of households from csv with their beneficiaries
        $listHouseholdsArray = $this->mapper->getListHouseholdArray($sheetArray, $countryIso3);

        return $this->foundErrors($countryIso3, $project, $listHouseholdsArray, $step, $token);

    }

    /**
     * @param $countryIso3
     * @param Project $project
     * @param array $listHouseholdsArray
     * @param int $step
     * @param $token
     * @return array|bool
     * @throws \Exception
     */
    public function foundErrors($countryIso3, Project $project, array $listHouseholdsArray, int $step, $token)
    {
        $this->token = $token;
        // If there is a treatment class for this step, call it
        $treatment = $this->guessTreatment($step);
        if ($treatment !== null)
            $listHouseholdsArray = $treatment->treat($project, $listHouseholdsArray);

        /** @var AbstractVerifier $verifier */
        $verifier = $this->guessVerifier($step);
        $return = [];
        if (null === $verifier)
            return true;

        foreach ($listHouseholdsArray as $index => $householdArray)
        {
            $returnTmp = $verifier->verify($countryIso3, $householdArray);
            // IF there is errors
            if (null !== $returnTmp && [] !== $returnTmp)
            {
                if ($returnTmp instanceof Household)
                    $return[] = ["old" => $returnTmp, "new" => $householdArray];
                elseif ($returnTmp !== false)
                    $return[] = $returnTmp;

                unset($listHouseholdsArray[$index]);
            }
        }

        $this->saveInCache($step, json_encode($listHouseholdsArray));

        return ["data" => $return, "token" => $this->token];
    }

    /**
     * If the token is null, create a new one
     * return the token
     * @return string
     */
    public function initOrGetToken()
    {
        $sizeToken = 50;
        if (null === $this->token)
            $this->token = bin2hex(random_bytes($sizeToken));

        return $this->token;
    }

    /**
     * Depends on the step, guess which verifier used
     * @param int $step
     * @return AbstractVerifier
     * @throws \Exception
     */
    private function guessVerifier(int $step)
    {
        switch ($step)
        {
            // CASE FOUND TYPO ISSUES
            case 1:
                return new TypoVerifier($this->em, $this->container, $this->initOrGetToken());
                break;
            // CASE FOUND DUPLICATED ISSUES
            case 2:
                return new DuplicateVerifier($this->em, $this->container, $this->initOrGetToken());
                break;
            // CASE FOUND MORE ISSUES
            case 3:
                return new MoreVerifier($this->em);
                break;
            // CASE FOUND LESS ISSUES
            case 4:
                return new LessVerifier($this->em);
                break;
            // CASE FOUND LESS ISSUES
            case 5:
                return null;
                break;
            // NOT FOUND CASE
            default:
                throw new \Exception("Step '$step' unknown.");
        }
    }

    /**
     * Depends on the step, guess which verifier used
     * @param int $step
     * @return AbstractTreatment
     * @throws \Exception
     */
    private function guessTreatment(int $step)
    {
        switch ($step)
        {
            case 1:
                return null;
                break;
            // CASE FOUND TYPO ISSUES
            case 2:
                return new TypoTreatment($this->em, $this->householdService, $this->beneficiaryService, $this->container, $this->initOrGetToken());
                break;
            // CASE FOUND DUPLICATED ISSUES
            case 3:
                return new DuplicateTreatment($this->em, $this->householdService, $this->beneficiaryService, $this->container, $this->initOrGetToken());
                break;
            // CASE FOUND MORE ISSUES
            case 4:
                return new MoreTreatment($this->em, $this->householdService, $this->beneficiaryService, $this->container, $this->initOrGetToken());
                break;
            // CASE FOUND LESS ISSUES
            case 5:
                return new LessTreatment($this->em, $this->householdService, $this->beneficiaryService, $this->container, $this->initOrGetToken());
                break;
            // NOT FOUND CASE
            default:
                throw new \Exception("Step '$step' unknown.");
        }
    }

    /**
     * @param int $step
     * @param $dataToSave
     * @throws \Exception
     */
    private function saveInCache(int $step, $dataToSave)
    {
        $this->initOrGetToken();

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);
        file_put_contents($dir_var . '/step_' . $step, $dataToSave);
    }

    /**
     * Check if a value is missing inside the array
     *
     * @param array $array
     * @return bool
     */
    private function isIncomplete(array $array)
    {
        $isIncomplete = true;
        foreach ($array as $key => $value)
        {
            if (is_array($value))
                $isIncomplete = $this->isIncomplete($value);
            if (!$isIncomplete || null === $value)
            {
                return false;
            }
        }

        return $isIncomplete;
    }
}