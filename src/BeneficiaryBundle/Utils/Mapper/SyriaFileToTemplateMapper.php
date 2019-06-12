<?php

declare(strict_types=1);

namespace BeneficiaryBundle\Utils\Mapper;

use ArrayObject;
use BeneficiaryBundle\Exception\MapperException;
use CommonBundle\Utils\ExportService;
use DateInterval;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Exception as PhpOfficeException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as PhpOfficeReaderException;
use PhpOffice\PhpSpreadsheet\Writer\Exception as PhpOfficeWriterException;
use Symfony\Component\HttpFoundation\File\File;
use Throwable;
use BeneficiaryBundle\Entity\CountrySpecific;
use Doctrine\ORM\EntityManagerInterface;
use function explode;
use function implode;
use function in_array;
use function microtime;
use function sprintf;
use function set_time_limit;
use function strpos;
use function str_replace;
use function trim;

class SyriaFileToTemplateMapper
{

    /** @var EntityManagerInterface $em */
    private $em;
    
    /**
     * Date of the day when the script is executed.
     * Used to compute birthdays.
     *
     * @var DateTime $TODAY
     */
    private static $TODAY;

    /*
     * Handle sex constants
     */
    private const MALE   = 'Male';
    private const FEMALE = 'Female';

    private const INPUT_COLUMN_START = 'A';
    private const INPUT_COLUMN_END   = 'AB';

    private const SPACE_SEPARATOR   = ' ';
    private const BACKSLASH_SEPARATOR = '/';
    
    private const FIRST_LETTER_NON_STATIC = 'O';
    
    private $numberCountrySpecifics = 0;

    /**
     * Used to avoid copy of this array during copy
     *
     * @var array $mapping
     */
    private $mapping = [];

    /**
     * Used to avoid copy of this array during copy
     *
     * @var array $birthdays
     */
    private $birthdays = [];

    /** @var ExportService $defaultExportService */
    private $defaultExportService;


    public function __construct(EntityManagerInterface $entityManager, ExportService $defaultExportService)
    {
        $this->em = $entityManager;
        $this->defaultExportService = $defaultExportService;
        self::$TODAY = new DateTime();
    }

    /**
     * Map a file within the Syria Model to the default template
     *
     * @param mixed[] $input An array with all required informations
     *                       {
     *                       file: File,
     *                       options?: jsonArray
     *                       }
     *
     * @return array
     * {
     *   outputFile: File,
     *   loadingTime: Numeric,
     *   executionTime: Numeric,
     *   writeTime: Numeric,
     * }
     * @throws MapperException
     */
    public function map(array $input) : array
    {
        try {
            /** @var File $file */
            $file     = $input['file'];
            $location = $input['location'];

            // Load input file
            $time        = microtime(true);
            $reader      = IOFactory::createReaderForFile($file->getRealPath());
            $worksheet   = $reader->load($file->getRealPath())->getActiveSheet();
            $loadingTime = microtime(true) - $time;
            
            // Generate header row
            $headerRow = $this->prepareOutputHeaderRow();

            // Map and generate output content
            // security to avoid infinite loop during test
            set_time_limit(60); // after 60 seconds it should crash to avoid server termination
            $time          = microtime(true);
            $highestRow    = $worksheet->getHighestRow();
            $sheetArray    = $worksheet->rangeToArray('A1:Z' . $highestRow, null, true, true, true);
            $output        = $this->doMap($sheetArray, [
                'location' => $location,
            ]);
            $executionTime = microtime(true) - $time;

            // create new speadsheet
            $time        = microtime(true);
            $spreadsheet = new Spreadsheet();
            $spreadsheet->createSheet();
            $worksheet = $spreadsheet->getActiveSheet();

            // Write header
            $currentIndex = 1;
            foreach ($headerRow as $letter => $value) {
                $worksheet->setCellValue($letter . $currentIndex, $value);
            }

            // Write content
            $currentIndex = 5;
            foreach ($output as $row) {
                $currentIndex++;
                foreach ($row as $letter => $cell) {
                    $worksheet->setCellValue($letter . $currentIndex, $cell);
                }
            }

            $filename  = $this->defaultExportService->generateFile(
                $spreadsheet,
                'syriaToStandard' . (new DateTime())->getTimestamp(),
                ExportService::FORMAT_XLS
            );
            $writeTime = microtime(true) - $time;

            return [
                'outputFile' => $filename,
                'loadingTime' => $loadingTime,
                'executionTime' => $executionTime,
                'writeTime' => $writeTime,
            ];
        } catch (PhpOfficeReaderException|PhpOfficeWriterException|PhpOfficeException $exception) {
            throw new MapperException(sprintf('[PhpOffice] %s', $exception->getMessage()));
        } catch (Throwable $exception) {
            throw new MapperException($exception->getMessage());
        }
    }

    /**
     * Map the given array to the standard template
     *
     * @param array $sheetArray The uploaded file converted to an array
     *
     * @param array $parameters An array with all required informations
     *                       {
     *                          location: {
     *                              admIndex: number,
     *                              name: string,
     *                          },
     *                       }
     * @return string[][]
     * @throws MapperException
     */
    private function doMap(array $sheetArray, $parameters = []) : array
    {
        $location = $parameters['location'];

        $this->initializeBirthdays();
        $defaultMapping = $this->getMapping();
        $outputRows = [];

        $addressStreet = trim(str_replace('LOCATION:', '', $sheetArray[2]['A']));

        foreach ($sheetArray as $indexRow => $row) {
            if ($indexRow < 10) {
                // we start at row 10
                continue;
            }

            if (empty($row['A'])) {
                // we break at the first empty row
                break;
            }

            // A. HANDLE SHARED COLUMNS
            /**
             * we use the same variable to store the columns shared between
             * members of a family
             * @var mixed[] $mutualOutputRow
             */
            $mutualOutputRow = [$this->getColumnLetter('AL') => $indexRow];
            $secondBeneficiaryValues = [$this->getColumnLetter('AL') => $indexRow];
            $secondBeneficiaryExists = false;

            /**
             * Names
             * M => Family name
             * L => Given name
             */
            $beneficiaryFirstNames = $row['B'];
            
            // If there is a slash in the beneficiary name
            if (strpos($beneficiaryFirstNames, self::BACKSLASH_SEPARATOR) !== false) {
                // A second beneficiary exists
                $secondBeneficiaryExists = true;
                // We store these names in an array
                $beneficiaryFirstNames = explode(self::BACKSLASH_SEPARATOR, $beneficiaryFirstNames);
                // Get the first name of the family's head and write it in column L
                $mutualOutputRow[$this->getColumnLetter('O')] = trim($beneficiaryFirstNames[0]);
                // Get the first name of of the second beneficiary and write it in column L
                $secondBeneficiaryValues[$this->getColumnLetter('O')] = trim($beneficiaryFirstNames[1]);
                ;
            } else { // If only one name is found => second beneficiary doesn't exist
                // If there is a space in the beneficiary's name
                $mutualOutputRow[$this->getColumnLetter('O')] = trim($beneficiaryFirstNames);
            }
            $beneficiaryLastNames = $row['C'];
            // If there is a slash in the beneficiary name
            if (strpos($beneficiaryLastNames, self::BACKSLASH_SEPARATOR) !== false) {
                // A second beneficiary exists
                $secondBeneficiaryExists = true;
                // We store these names in an array
                $beneficiaryLastNames = explode(self::BACKSLASH_SEPARATOR, $beneficiaryLastNames);
                // Get the last name of the family's head and write it in column M
                $mutualOutputRow[$this->getColumnLetter('P')] = trim($beneficiaryLastNames[0]);
                // Get the last name of of the second beneficiary and write it in column M
                $secondBeneficiaryValues[$this->getColumnLetter('P')] = trim($beneficiaryLastNames[1]);
                ;
            } else { // If only one name is found => second beneficiary doesn't exist
                // If there is a space in the beneficiary's name
                $mutualOutputRow[$this->getColumnLetter('P')] = trim($beneficiaryLastNames);
            }

            // Get beneficiary's id
            $beneficiaryId = strval($row['D']);
            if (!empty($beneficiaryId)) {
                // Writes 'ID Card' in the column AC
                $mutualOutputRow[$this->getColumnLetter('AF')] = 'ID Card';
                // If there is a slash in the id => two ids
                if (strpos($beneficiaryId, self::BACKSLASH_SEPARATOR) !== false) {
                    $beneficiariesId = explode(self::BACKSLASH_SEPARATOR, $beneficiaryId);
                    $mutualOutputRow[$this->getColumnLetter($defaultMapping['C'])] = trim($beneficiariesId[0]);

                    if ($secondBeneficiaryExists) {
                        $secondBeneficiaryValues[$this->getColumnLetter($defaultMapping['C'])] = trim($beneficiariesId[1]);
                        $secondBeneficiaryValues[$this->getColumnLetter('AF')] = 'ID Card';
                    } else {
                        // File badly filled in
                        // throw new Exception('Die' . $indexRow);
                    }
                } else { // only one Id found
                    $mutualOutputRow[$this->getColumnLetter($defaultMapping['C'])] = $beneficiaryId;
                }
            }

            // Get the gender of the family's head
            $headGender = $row['U'];
            // Writes the gender in column N
            $mutualOutputRow[$this->getColumnLetter('S')] = intval($headGender) === 1 ? self::FEMALE : self::MALE;
            // Residency status
            $houseHoldResidencyStatus = intval($row['F']) === 1 ? 'IDP' : 'resident';
            $mutualOutputRow[$this->getColumnLetter('U')] = $houseHoldResidencyStatus;
            $secondBeneficiaryValues[$this->getColumnLetter('U')] = $houseHoldResidencyStatus;

            // Set head of household status
            $mutualOutputRow[$this->getColumnLetter('T')] = 'true';
            if ($secondBeneficiaryExists) {
                $secondBeneficiaryValues[$this->getColumnLetter('T')] = 'false';
            }

            // B. LET ADD HEAD OF HOUSEHOLD and its second
            $headOfHouseholdRow = new ArrayObject($mutualOutputRow);
            // address
            $headOfHouseholdRow[$this->getColumnLetter('D')] = $addressStreet;
            $headOfHouseholdRow[$this->getColumnLetter('E')] = $row['A'];
            $headOfHouseholdRow[$this->getColumnLetter($defaultMapping['adm1'])] = $location[0];
            $headOfHouseholdRow[$this->getColumnLetter($defaultMapping['adm2'])] = $location[1];
            $headOfHouseholdRow[$this->getColumnLetter($defaultMapping['adm3'])] = $location[2];
            $headOfHouseholdRow[$this->getColumnLetter($defaultMapping['adm4'])] = $location[3];

            // Head phone number
            if (!empty($row['E'])) {
                $headOfHouseholdRow[$this->getColumnLetter('X')] = 'Mobile';
                $headOfHouseholdRow[$this->getColumnLetter('Y')] = '\'+963';
                $headOfHouseholdRow[$this->getColumnLetter('Z')] = '\'' . $row['E'];
                $headOfHouseholdRow[$this->getColumnLetter('AA')] = 'N';
            }

            /**
             * remove and potential second from list of beneficiaries by guessing their ages
             * Strategy:
             * Find the oldest person having the sex of the head
             * Find the second adult person
             */
            $mainHeadRemoved = false;
            $subHeadRemoved  = false;
            $letters = range('J', 'S');
            $genders = [self::FEMALE, self::MALE];

            for ($i = count($letters) - 1; $i>=0; $i--) {
                $letter = $letters[$i];
                $cellValue = intval($row[$letter]);
                if ($cellValue <= 0) {
                    // we ignore an empty column
                    continue;
                }
                // the 1st index (column V) is odd and matches a woman
                // if the current person has the same sex than the main and the main has not been removed yet
                if (!$mainHeadRemoved) {
                    // odd means woman, $headGender===1 also means woman
                    if (($i % 2 != 0 && intval($headGender) === 1) || ($i % 2 == 0 && intval($headGender) === 0)) {
                        //we potentially found the first older person having the head of household sex
                        //we remove him

                        $headOfHouseholdRow[$this->getColumnLetter('V')] = $this->getBirthday($letter);

                        $row[$letter] = --$cellValue;
                        $mainHeadRemoved = true;
                        if (!$secondBeneficiaryExists) {
                            break;
                        }
                    }
                }

                // there's another person in the same sex-age group
                // and the sub has not been removed yet
                if ($secondBeneficiaryExists && $cellValue > 0 && ! $subHeadRemoved) {
                    $row[$letter] = intval($row[$letter]) - 1;
                    $subHeadRemoved = true;
                    // set second beneficiary sex and birthday: odd means woman
                    $secondBeneficiaryValues[$this->getColumnLetter('S')] = $i % 2 != 0 ? self::FEMALE : self::MALE;
                    $secondBeneficiaryValues[$this->getColumnLetter('V')] = $this->getBirthday($letter);

                    if ($mainHeadRemoved) {
                        break;
                    }
                }
            }
            unset($letters);

            $outputRows[] = $headOfHouseholdRow;
            if ($secondBeneficiaryExists && $subHeadRemoved) {
                $outputRows[] = new ArrayObject($secondBeneficiaryValues);
            }

            // C. HANDLE NON SHARED COLUMNS: Let add each beneficiary
            // knowing that mainhead and subhead have been removed

            // remove head status for beneficiaries
            $mutualOutputRow[$this->getColumnLetter('T')] = 'false';
            // starting from here, we create a row per value of column
            $mutualOutputRowToArrayObject = new ArrayObject($mutualOutputRow);
            $letters = range('J', 'S');
            for ($i = 0; $i < count($letters); $i++) {
                // $i is MALE
                $column = $letters[$i];
                // count members of family in a age class
                $ageGroupCount = intval($row[$column]);

                // ignore null or 0 values
                if ($ageGroupCount === 0) {
                    continue;
                }

                /**
                 * For each beneficiary of a household, fill these fields:
                 * - L: given name: concatenate
                 *      "head of family name (column C)" + ageGroup + countInAgeGroup
                 * - M: family name: keep "head of family name (column C)"
                 *    -- same than the head of family
                 *    -- this column is handled in the part I.
                 * - N: sex: Male or Female
                 * - P: date delay:
                 */
                for ($j=0; $j < $ageGroupCount; $j++) {
                    $outputRow = $mutualOutputRowToArrayObject->getArrayCopy();

                    // given name
                    $outputRow[$this->getColumnLetter('O')] = sprintf("%s_%s_%s", $outputRow[$this->getColumnLetter('P')], $column, $j);

                    // birthday
                    $outputRow[$this->getColumnLetter('V')] = $this->getBirthday($column);

                    // sex
                    if (in_array($column, ['K', 'M', 'O', 'Q', 'S'])) {
                        $outputRow[$this->getColumnLetter('S')] = self::FEMALE;
                    } elseif (in_array($column, ['J', 'L', 'N', 'P', 'R'])) {
                        $outputRow[$this->getColumnLetter('S')] = self::MALE;
                    } else {
                        $outputRow[$this->getColumnLetter('S')] = $genders[array_rand($genders)];
                    }

                    $outputRows[] = $outputRow;
                }
            }

            unset($mutualOutputRowToArrayObject);
        }

        return $outputRows;
    }

    /**
     * Returns the 1st row of the output file
     *
     * @return array
     */
    private function prepareOutputHeaderRow() : array
    {
        $countrySpecifics = $this->em->getRepository(CountrySpecific::class)->findByCountryIso3('SYR');
        $this->numberCountrySpecifics = sizeof($countrySpecifics);
        
        $headerRow = [
            'A' => 'Address street',
            'B' => 'Address number',
            'C' => 'Address postcode',
            'D' => 'Camp',
            'E' => 'Tent number',
            'F' => 'Livelihood',
            'G' => 'Income level',
            'H' => 'Notes',
            'I' => 'Latitude',
            'J' => 'Longitude',
            'K' => 'Adm1',
            'L' => 'Adm2',
            'M' => 'Adm3',
            'N' => 'Adm4'
        ];
        
        $dynamicColums = [
            'O' => 'Local given name',
            'P' => 'Local family name',
            'Q' => 'English given name',
            'R' => 'English family name',
            'S' => 'Gender',
            'T' => 'Head',
            'U' => 'Residency Status',
            'V' => 'Date of birth',
            'W' => 'Vulnerability criteria',
            'X' => 'Type phone 1',
            'Y' => 'Prefix phone 1',
            'Z' => 'Number phone 1',
            'AA' => 'Proxy phone 1',
            'AB' => 'Type phone 2',
            'AC' => 'Prefix phone 2',
            'AD' => 'Number phone 2',
            'AE' => 'Proxy phone 2',
            'AF' => 'Type national ID',
            'AG' => 'Number national ID',
            'AH' => 'Referral Type',
            'AI' => 'Referral Comment',
        ];
        
        $column = self::FIRST_LETTER_NON_STATIC;
        if (! empty($countrySpecifics)) {
            foreach ($countrySpecifics as $countrySpecific) {
                $headerRow[$column] = $countrySpecific->getFieldString();
                $column++;
            }
            
            foreach ($dynamicColums as $key => $value) {
                $headerRow[$this->getColumnLetter($key)] = $value;
            }
        } else {
            $headerRow = $headerRow + $dynamicColums;
        }
        
        return $headerRow;
    }

    /**
     * Gives the mapping column by column to trancript a Syria file to the default
     * template
     *
     * @return array
     */
    private function &getMapping() : array
    {
        if (! empty($this->mapping)) {
            return $this->mapping;
        }

        $this->mapping = [
            self::INPUT_COLUMN_START => '',  // id
            'B' => 'E',  // tent number
            'C' => 'AG',  // id number ob beneficiary
            'adm1' => 'K',
            'adm2' => 'L',
            'adm3' => 'M',
            'adm4' => 'N',
            self::INPUT_COLUMN_END => '',  // Signature / Thumbprint of beneficiary
        ];

        return $this->mapping;
    }
    
    /**
     * Get the right column letter, by adding the number of country specifics to the default
     * Example : A + 2 = C  Or  Z + 1 = AA  OR  AY + 2 = BA
     * @param string $letter
     * @param $number
     * @return string
     */
    private function getColumnLetter(string $letter)
    {
        if ($letter >= self::FIRST_LETTER_NON_STATIC || strlen($letter)  > strlen(self::FIRST_LETTER_NON_STATIC)) {

            if (strlen($letter) < 2) {
                $ascii = ord($letter) + $this->numberCountrySpecifics;
            } else {
                // Here we add 26 if the column is a AA, AB, AC, etc...
                $ascii = 26 + ord(substr($letter[1], 0, 1)) + $this->numberCountrySpecifics;
            }
            $prefix = '';
            if ($ascii > 90) {
                $prefix = 'A';
                $ascii -= 26;
                while ($ascii > 90) {
                    $prefix++;
                    $ascii -= 90;
                }
            }
            return $prefix . chr($ascii);
        } else {
            return $letter;
        }
    }

    /**
     * Retrieve the birthday from the given column.
     *
     * @param string $column The column from the input file
     *
     * @return string The formated date
     */
    private function getBirthday(string $column) : string
    {
        return $this->birthdays[$column];
    }

    private function initializeBirthdays() : void
    {
        $this->birthdays = [
            'J' => (clone self::$TODAY)->sub(new DateInterval('P1Y'))->format('d-m-Y'),
            'K' => (clone self::$TODAY)->sub(new DateInterval('P1Y'))->format('d-m-Y'),
            'L' => (clone self::$TODAY)->sub(new DateInterval('P3Y'))->format('d-m-Y'),
            'M' => (clone self::$TODAY)->sub(new DateInterval('P3Y'))->format('d-m-Y'),
            'N' => (clone self::$TODAY)->sub(new DateInterval('P11Y'))->format('d-m-Y'),
            'O' => (clone self::$TODAY)->sub(new DateInterval('P11Y'))->format('d-m-Y'),
            'P' => (clone self::$TODAY)->sub(new DateInterval('P39Y'))->format('d-m-Y'),
            'Q' => (clone self::$TODAY)->sub(new DateInterval('P39Y'))->format('d-m-Y'),
            'R' => (clone self::$TODAY)->sub(new DateInterval('P61Y'))->format('d-m-Y'),
            'S' => (clone self::$TODAY)->sub(new DateInterval('P61Y'))->format('d-m-Y'),
        ];
    }
}
