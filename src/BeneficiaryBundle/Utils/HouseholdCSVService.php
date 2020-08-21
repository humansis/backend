<?php


namespace BeneficiaryBundle\Utils;

use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Utils\DataTreatment\AbstractTreatment;
use BeneficiaryBundle\Utils\DataTreatment\DuplicateTreatment;
use BeneficiaryBundle\Utils\DataTreatment\ValidateTreatment;
use BeneficiaryBundle\Utils\DataTreatment\LessTreatment;
use BeneficiaryBundle\Utils\DataTreatment\MoreTreatment;
use BeneficiaryBundle\Utils\DataTreatment\TypoTreatment;
use BeneficiaryBundle\Utils\DataTreatment\MissingTreatment;
use BeneficiaryBundle\Utils\DataVerifier\AbstractVerifier;
use BeneficiaryBundle\Utils\DataVerifier\DuplicateVerifier;
use BeneficiaryBundle\Utils\DataVerifier\ExistingHouseholdVerifier;
use BeneficiaryBundle\Utils\DataVerifier\LessVerifier;
use BeneficiaryBundle\Utils\DataVerifier\MoreVerifier;
use BeneficiaryBundle\Utils\DataVerifier\TypoVerifier;
use BeneficiaryBundle\Utils\Mapper\CSVToArrayMapper;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HouseholdCSVService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var HouseholdService $householdService */
    private $householdService;

    /** @var CSVToArrayMapper $CSVToArrayMapper */
    private $CSVToArrayMapper;

    /** @var Container $container */
    private $container;

    /** @var BeneficiaryService $beneficiaryService */
    private $beneficiaryService;

    /** @var string $token */
    private $token;

    /** @var int $step */
    private $step;

    private const NUMBER_OF_ROWS_FOR_PREVIEW  = 10;

    /**
     * HouseholdCSVService constructor.
     * @param EntityManagerInterface $entityManager
     * @param HouseholdService $householdService
     * @param BeneficiaryService $beneficiaryService
     * @param CSVToArrayMapper $CSVToArrayMapper
     * @param Container $container
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        HouseholdService $householdService,
        BeneficiaryService $beneficiaryService,
        CSVToArrayMapper $CSVToArrayMapper,
        Container $container
    ) {
        $this->em = $entityManager;
        $this->householdService = $householdService;
        $this->beneficiaryService = $beneficiaryService;
        $this->CSVToArrayMapper = $CSVToArrayMapper;
        $this->container = $container;
    }

    /**
     * Build structure for show preview on FE.
     *
     * @param string       $countryIso3
     * @param UploadedFile $uploadedFile
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function createPreview($countryIso3, UploadedFile $uploadedFile)
    {
        $reader = IOFactory::createReaderForFile($uploadedFile->getRealPath());
        $worksheet = $reader->load($uploadedFile->getRealPath())->getActiveSheet();

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data_csv';
        if (!is_dir($dir_var)) {
            mkdir($dir_var);
        }
        $uploadedFile->move($dir_var);

        $headers = $this->container->get('beneficiary.household_export_csv_service')->getHeaders($countryIso3);
        $header = reset($headers);
        $header = array_keys($header);
        $header = array_filter($header, 'trim');    // some header cells are empty (due to help messages). We need to strip them.

        $headerResult = [];
        $generator = new ExcelColumnsGenerator();
        foreach ($header as $item) {
            $headerResult[$generator->getNext()] = $item;
        }

        $dataRange = sprintf('A%s:%s%s',            // for example: A2:AB1000
            count($headers) + 1 + 1,                // number of header rows - we want to removed it from data
            $worksheet->getHighestColumn(),
            self::NUMBER_OF_ROWS_FOR_PREVIEW
        );
        $data = $worksheet->rangeToArray($dataRange, null, false, true, true);

        return [
            'header' => $headerResult,
            'data' => array_values($data),
            'mapping' => $this->CSVToArrayMapper->getMappingCSVOfCountry($countryIso3),
            'tmpFile' => $uploadedFile->getFilename(),
        ];
    }

    /**
     * Defined the reader and transform CSV to array.
     *
     * @param         $countryIso3
     * @param Project $project
     * @param string  $email
     *
     * @return array
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function saveCSVAndAnalyze($countryIso3, Project $project, $tmpFile, $mappingCSV, string $email)
    {
        $dir_root = $this->container->get('kernel')->getRootDir();
        $csvFile = $dir_root.'/../var/data_csv/'.$tmpFile;

        $sheetArray = $this->loadSheet($csvFile);

        return $this->transformAndAnalyze($countryIso3, $project, $sheetArray, $mappingCSV, $email);
    }

    /**
     * @param         $countryIso3
     * @param Project $project
     * @param array   $sheetArray
     * @param string  $email
     *
     * @return array|bool
     *
     * @throws \Exception
     */
    protected function transformAndAnalyze($countryIso3, Project $project, array $sheetArray, $mappingCSV, string $email)
    {
        $headers = $this->container->get('beneficiary.household_export_csv_service')->getHeaders($countryIso3);
        $rowHeader = $sheetArray[1];
        $sheetArray = array_slice($sheetArray, count($headers) + 1);

        $listHouseholdsArray = $this->CSVToArrayMapper->fromCSVToArray($sheetArray, $rowHeader, $countryIso3, $mappingCSV);

        return $this->foundErrors($countryIso3, $project, $listHouseholdsArray, null, $email);
    }

    /**
     * @param $countryIso3
     * @param Project $project
     * @param array $treatReturned
     * @param $token
     * @param string $email
     * @return array|bool
     * @throws \Exception
     */
    public function foundErrors($countryIso3, Project $project, array &$treatReturned, $token, string $email)
    {
        // Clean cache if timestamp is expired
        $this->clearExpiredSessions();
        $this->token = $token;

        do {
            // get step
            $this->step = $this->getStepFromCache();

            // Check if cache and token is still there
            if (!$this->checkTokenAndStep($this->step)) {
                throw new \Exception('Your session for this import has expired');
            }

            // If there is a treatment class for this step, call it
            /** @var AbstractTreatment $verifier */
            $treatment = $this->guessTreatment($this->step);



            if ($treatment) {
                $treatReturned = $treatment->treat($project, $treatReturned, $email);
                if (! $treatReturned) {
                    $treatReturned = [];
                }
            }

            if (is_array($treatReturned) && array_key_exists('miss', $treatReturned)) {
                throw new \Exception('A line is incomplete or not properly filled in the imported file: ' . $treatReturned['miss']);
            }

            /** @var AbstractVerifier $verifier */
            $verifier = $this->guessVerifier($this->step);


            // Return array
            $return = [];

            // if no verification needed
            if (! $verifier) {
                if ($this->step === 6) {
                    $this->clearCacheToken($this->token);
                    return $treatReturned;
                } else if ($this->step === 5) {
                    // update timestamp (10 minutes) and step
                    $this->updateTokenState();
                    break;
                }
            }

            $cacheId = 0;
            foreach ($treatReturned as $index => $householdArray) {
                // use the generated for the first step, and then use existing one
                $correctId = $this->step === 1 ? $cacheId : $householdArray['id_tmp_cache'];
                try {
                    $returnTmp = $verifier->verify($countryIso3, $householdArray, $correctId, $email);
                } catch (\Exception $e) {
                    $this->clearCacheToken($this->token);
                    throw $e;
                }
                // If there are errors
                if (! empty($returnTmp)) {
                    // Duplicate verifier returns already an array of duplicates
                    if ($verifier instanceof DuplicateVerifier) {
                        // to preserve values with the same keys
                        /* TEMPORAL CHANGE TO SKIP THE DUPLICATION STEP */
                        $return = []; //array_unique(array_merge($return, $returnTmp), SORT_REGULAR);
                    } else {
                        $return[] = $returnTmp;
                    }
                }
                $cacheId++;
                unset($treatReturned[$index]);
            }


            // update timestamp (10 minutes) and step
            $this->updateTokenState();
        } while (empty($return));


        return ['data' => $return, 'token' => $this->token, 'step' => $this->step];
    }

    /**
     * Depends on the step, guess which verifier used
     * @param int $step
     * @return AbstractTreatment
     * @throws \Exception
     */
    private function guessTreatment(int $step)
    {

        switch ($step) {
            case 1:
                return new MissingTreatment($this->em, $this->householdService, $this->beneficiaryService, $this->container, $this->initOrGetToken());

                break;
            // CASE FOUND TYPO ISSUES
            case 2:
                return new TypoTreatment($this->em, $this->householdService, $this->beneficiaryService, $this->container, $this->initOrGetToken());
                break;
            // CASE FOUND MORE ISSUES
            case 3:
                return new MoreTreatment($this->em, $this->householdService, $this->beneficiaryService, $this->container, $this->initOrGetToken());
                break;
            // CASE FOUND LESS ISSUES
            case 4:
                return new LessTreatment($this->em, $this->householdService, $this->beneficiaryService, $this->container, $this->initOrGetToken());
                break;
            // CASE FOUND DUPLICATED ISSUES
            case 5:
                return new DuplicateTreatment($this->em, $this->householdService, $this->beneficiaryService, $this->container, $this->initOrGetToken());
                break;
            // CASE VALIDATE
            case 6:
                return new ValidateTreatment($this->em, $this->householdService, $this->beneficiaryService, $this->container, $this->initOrGetToken());
                break;
            // NOT FOUND CASE
            default:
                throw new \Exception('Step ' . $step . ' unknown.');
        }
    }

    /**
     * Depends on the step, guess which verifier used
     * @param int $step
     * @return AbstractVerifier
     * @throws \Exception
     */
    private function guessVerifier(int $step)
    {
        switch ($step) {
            // CASE FOUND TYPO ISSUES
            case 1:
                return new ExistingHouseholdVerifier($this->em, $this->container, $this->initOrGetToken()); // new LevenshteinTypoVerifier($this->em, $this->container, $this->initOrGetToken());
                break;
            // CASE FOUND MORE ISSUES
            case 2:
                return new MoreVerifier($this->em);
                break;
            // CASE FOUND LESS ISSUES
            case 3:
                return new LessVerifier($this->em);
                break;
            // CASE FOUND DUPLICATED ISSUES
            case 4:
                return new DuplicateVerifier($this->em, $this->container, $this->initOrGetToken());
                break;
            // CASE LAST STEP
            case 5:
                return null;
                break;
            // CASE VALIDATION
            case 6:
                return null;
                break;
            // NOT FOUND CASE
            default:
                throw new \Exception('Step ' . $step. ' unknown.');
        }
    }

    /**
     * @param $step
     * @return bool
     * @throws \Exception
     */
    private function checkTokenAndStep($step)
    {
        if (intval($step) === 1) {
            return true;
        }

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_token = $dir_root . '/../var/data/token_state';
        if (is_file($dir_token)) {
            $tokensState = json_decode(file_get_contents($dir_token), true);
            if (! array_key_exists($this->token, $tokensState)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * If the token is null, create a new one
     * return the token
     * @return string
     * @throws \Exception
     */
    public function initOrGetToken()
    {

        $sizeToken = 25;
        if (null === $this->token) {
            $this->token = bin2hex(random_bytes($sizeToken));
        }

        return $this->token;
    }

    /**
     * @throws \Exception
     */
    private function updateTokenState()
    {
        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data';
        if (!is_dir($dir_var)) {
            mkdir($dir_var);
        }
        $dir_file = $dir_var . '/token_state';
        if (is_file($dir_file)) {
            $tokensState = json_decode(file_get_contents($dir_file), true);
        } else {
            $tokensState = [];
        }

        // Update step
        $this->step++;

        $dateExpiry = new \DateTime();
        $dateExpiry->add(new \DateInterval('PT10M'));
        $tokensState[$this->token] = [
            'timestamp' => $dateExpiry->getTimestamp(),
            'step' => $this->step
        ];

        file_put_contents($dir_var . '/token_state', json_encode($tokensState));
    }

    /**
     * @throws \Exception
     */
    private function clearExpiredSessions()
    {
        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data';
        if (!is_dir($dir_var)) {
            mkdir($dir_var);
        }
        $dir_file = $dir_var . '/token_state';
        if (is_file($dir_file)) {
            $tokensState = json_decode(file_get_contents($dir_file), true);
        } else {
            $this->rrmdir($dir_var);
            return;
        }

        foreach ($tokensState as $token => $item) {
            if ((new \DateTime())->getTimestamp() > $item['timestamp']) {
                if (is_dir($dir_var . '/' . $token)) {
                    $this->rrmdir($dir_var . '/' . $token);
                }
                unset($tokensState[$token]);
            }
        }

        file_put_contents($dir_var . '/token_state', json_encode($tokensState));
    }

    /**
     * @param $token
     * @throws \Exception
     */
    private function clearCacheToken($token)
    {
        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data';
        if (!is_dir($dir_var)) {
            mkdir($dir_var);
        }
        $dir_file = $dir_var . '/token_state';
        if (is_file($dir_file)) {
            $tokensState = json_decode(file_get_contents($dir_file), true);
        } else {
            $this->rrmdir($dir_var);
            return;
        }

        if (is_dir($dir_var . '/' . $token)) {
            $this->rrmdir($dir_var . '/' . $token);
        }
        if (array_key_exists($token, $tokensState)) {
            unset($tokensState[$token]);
        }

        file_put_contents($dir_var . '/token_state', json_encode($tokensState));
    }

    private function getStepFromCache()
    {
        $step = 1;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data';
        if (!is_dir($dir_var)) {
            mkdir($dir_var);
        }
        $dir_file = $dir_var . '/token_state';
        if (is_file($dir_file)) {
            $tokensState = json_decode(file_get_contents($dir_file), true);
            if ($this->token && array_key_exists($this->token, $tokensState) && array_key_exists('step', $tokensState[$this->token])) {
                $step = $tokensState[$this->token]['step'];
            }
        }

        return $step;
    }

    /**
     * @param $src
     */
    private function rrmdir($src)
    {
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    $this->rrmdir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }

    private function loadSheet(string $filename): array
    {
        $reader = IOFactory::createReaderForFile($filename);
        $worksheet = $reader->load($filename)->getActiveSheet();

        $sheetArray = $worksheet->rangeToArray('A1:'.$worksheet->getHighestColumn().$worksheet->getHighestRow(), null, false, true, true);

        // search for null columns from end to start
        foreach (array_reverse($sheetArray[1], true) as $c => $cell) {
            foreach ($sheetArray as $r => $row) {
                if (null !== $sheetArray[$r][$c]) {
                    // stop searching for null values if some found
                    break 2;
                }
            }

            foreach ($sheetArray as $r => $row) {
                unset($sheetArray[$r][$c]);
            }
        }

        return $sheetArray;
    }
}
