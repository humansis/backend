<?php

declare(strict_types=1);

namespace BeneficiaryBundle\Utils\Mapper;

use ArrayObject;
use DateInterval;
use DateTime;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\File;
use Throwable;
use function in_array;
use function sprintf;

class SyriaFileToTemplateMapper
{
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

    /**
     * Used to avoid copy of this array during copy
     *
     * @var array $outputHeaderRow
     */
    private $outputHeaderRow = [];

    /**
     * Used to avoid copy of this array during copy
     *
     * @var array $mapping
     */
    private $mapping = [];

    public function __construct()
    {
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
     *   outputFile: File
     * }
     */
    public function map(array $input) : array
    {
        /** @var File $file */
        $file  = $input['file'];

        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $worksheet = $reader->load($file->getRealPath())
            ->getActiveSheet();

        // security to avoid infinite loop during test
        // TODO: remove it when the the function is coded
        set_time_limit(30);

        $sheetArray = $worksheet->toArray(null, true, true, true);

        $output = $this->doMap($sheetArray);

        return [
            'outputFile' => $output
        ];
    }

    /**
     * Map the uploaded file to the standard template
     *
     * @param array $sheetArray The uploaded file converted to an array
     *
     * @return null|File
     */
    private function doMap(array $sheetArray) //: ?File
    {
        $outputRows = [];
        $defaultMapping = $this->getMapping();
        $J2Int = ord('J');
        $V2Int = ord('V');

        foreach ($sheetArray as $indexRow => $row) {
            // I. HANDLE SHARED COLUMNS

            /**
             * we use the same variable to store the columns shared between
             * members of a family
             * @var mixed[] $mutualOutputRow
             */
            $mutualOutputRow = [];

            foreach ($row as $letter => $cell) {
                if (in_array($letter, ['C', 'D'])) { // auto filling following the mapping
                    $mutualOutputRow[$defaultMapping[$letter]] = $cell;
                } else {
                    switch ($letter) {
                        case 'E' : // Phone number extraction
                            break;
                        case 'F' : // Status: IPD
                            if (intval($cell) === 1) {
                                $mutualOutputRow['O'] = $cell;
                            }
                            break;
                        case 'G' : // Status: Resident
                            if (intval($cell) === 1) {
                                $mutualOutputRow['O'] = $cell;
                            }
                            break;
                        case 'J' :
                            break 2; // break switch and foreachcase 'J' :
                        case 'W' :
                            // sex of the beneficiary:
                            // column N will be erased for each beneficiaries in the household
                            $mutualOutputRow['N'] = intval($cell) === 1 ? self::FEMALE : self::MALE;
                            break 2;
                    }
                }
            }

            // B. LET ADD HEAD OF HOUSEHOLD
            $headOfHouseholdRow = new ArrayObject($mutualOutputRow);
            $outputRows[] = $headOfHouseholdRow;

            // C. HANDLE NON SHARED COLUMNS: Let add each beneficiary

            // starting from here, we create a row per value of column
            $mutualOutputRowToArrayObject = new ArrayObject($mutualOutputRow);
            $letters = range('J', 'V');
            for ($i = 0; $i < count($letters); $i++) {
                $column = $letters[$i];
                // count members of family in a age class
                try{
                    $ageGroupCount = intval($row[$column]);
                } catch (Throwable $exception) {
                    $ageGroupCount = null;
                }

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
                    $outputRow['L'] = sprintf("%s_%s_%s", $outputRow['M'], $column, $j);

                    // other beneficiary properties
                    switch ($i + $J2Int) {
                        case ord('J'):
                            $outputRow['N'] = self::MALE;
                            $outputRow['P'] = $this->getBirthday('P3M');
                            break;
                        case ord('K'):
                            $outputRow['N'] = self::FEMALE;
                            $outputRow['P'] = $this->getBirthday('P3M');
                            break;
                        case ord('L'):
                            $outputRow['N'] = self::MALE;
                            $outputRow['P'] = $this->getBirthday('P1Y'); // - 12 months
                            break;
                        case ord('M'):
                            $outputRow['N'] = self::FEMALE;
                            $outputRow['P'] = $this->getBirthday('P1Y'); // - 12 months
                            break;
                        case ord('N'):
                            $outputRow['N'] = self::MALE;
                            $outputRow['P'] = $this->getBirthday('P21M');
                            break;
                        case ord('O'):
                            $outputRow['N'] = self::FEMALE;
                            $outputRow['P'] = $this->getBirthday('P21M');
                            break;
                        case ord('P'):
                            $outputRow['N'] = '??????????????????????????????????????????????';
                            $outputRow['P'] = $this->getBirthday('P3Y');
                            break;
                        case ord('Q'):
                            $outputRow['N'] = self::MALE;
                            $outputRow['P'] = $this->getBirthday('P11Y');
                            break;
                        case ord('R'):
                            $outputRow['N'] = self::FEMALE;
                            $outputRow['P'] = $this->getBirthday('P11Y');
                            break;
                        case ord('S'):
                            $outputRow['N'] = self::MALE;
                            $outputRow['P'] = $this->getBirthday('P39Y');
                            break;
                        case ord('T'):
                            $outputRow['N'] = self::FEMALE;
                            $outputRow['P'] = $this->getBirthday('P39Y');
                            break;
                        case ord('U'):
                            $outputRow['N'] = self::MALE;
                            $outputRow['P'] = $this->getBirthday('P61Y');
                            break;
                        case ord('V'):
                            $outputRow['N'] = self::FEMALE;
                            $outputRow['P'] = $this->getBirthday('P61Y');
                            break;
                    }

                    $outputRows[] = $outputRow;
                }
            }

            unset($mutualOutputRowToArrayObject);
        }

        return $outputRows;
        // write new file

        // Write header
        foreach ($this->prepareOutputHeaderRow() as $letter => $value) {
            //
        }

        // Write content

        // Download file


        return null;
    }

    /**
     * Returns the 1st row of the output file
     *
     * @return array
     */
    private function &prepareOutputHeaderRow() : array
    {
        if (! empty($this->outputHeaderRow)) {
            return $this->outputHeaderRow;
        }

        $this->outputHeaderRow = [
            'A' => 'Address street',
            'B' => 'Address number',
            'C' => 'Address postcode',
            'D' => 'Livelihood',
            'E' => 'Notes',
            'F' => 'Latitude',
            'G' => 'Longitude',
            'H' => 'Adm1',
            'I' => 'Adm2',
            'J' => 'Adm3',
            'K' => 'Adm4',
            'L' => 'Given name',
            'M' => 'Family name',
            'N' => 'Gender',
            'O' => 'Status',
            'P' => 'Date of birth',
            'Q' => 'Vulnerability criteria',
            'R' => 'Type phone 1',
            'S' => 'Prefix phone 1',
            'T' => 'Number phone 1',
            'U' => 'Proxy phone 1',
            'V' => 'Type phone 2',
            'W' => 'Prefix phone 2',
            'X' => 'Number phone 2',
            'Y' => 'Proxy phone 2',
            'Z' => 'Type national ID',
            'AA' => 'Number national ID',
        ];

        return $this->outputHeaderRow;
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
            'A' => '',  // id
            'B' => '',  // tent number
            'C' => 'M',  // name of beneficiary
            'D' => 'AA',  // id number ob beneficiary
            'E' => 'RSTU',  // phone number 1 (there are not 2 hone numbers)
            'F' => 'O',  // Status: IPD
            'G' => 'O',  // Status: Resident
            'H' => '',  // Number of the 1st check
            'I' => '',  // Number of the last check
            'J' => 'MNP',  // Number of persons in family::0-2years::0-5monthsM
            'K' => 'MNP',  // Number of persons in family::0-2years::0-5monthsF
            'L' => 'MNP',  // Number of persons in family::0-2years::6-17monthsM
            'M' => 'MNP',  // Number of persons in family::0-2years::6-17monthsF
            'N' => 'MNP',  // Number of persons in family::0-2years::18-23monthsM
            'O' => 'MNP',  // Number of persons in family::0-2years::18-23monthsF
            'P' => 'MNP',  // Number of persons in family::2-5years
            'Q' => 'MNP',  // Number of persons in family::5-17yearsM
            'R' => 'MNP',  // Number of persons in family::5-17yearsF
            'S' => 'MNP',  // Number of persons in family::18-59yearsM
            'T' => 'MNP',  // Number of persons in family::18-59yearsF
            'U' => 'MNP',  // Number of persons in family::p60yearsM
            'V' => 'MNP',  // Number of persons in family::p60yearsF
            'W' => 'N',  // Gender of head of family M
            'X' => 'N',  // Gender of head of family F
            'Y' => '',  // Signature / Thumbprint of beneficiary
        ];

        return $this->mapping;
    }

    /**
     * Compute the birthday from the given interval specification.
     * Ex: P3M will remove 3 months to the current date and return the matching date
     *
     * @param string $intervalSpec The interface specification
     *
     * @return string The formated date
     */
    private function getBirthday(string $intervalSpec) : string
    {
        return (clone self::$TODAY)->sub(new DateInterval($intervalSpec))
            ->format('Y-m-d');
    }
}