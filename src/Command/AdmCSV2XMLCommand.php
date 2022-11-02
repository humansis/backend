<?php

namespace Command;

use Exception;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class AdmCSV2XMLCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:adm:convert')
            ->setDescription('Check CSV file consistency and transform it into importable xml file')
            ->addArgument('sourceFile', InputArgument::REQUIRED, 'Source file in CSV format');
    }

    /**
     *
     * @return int|void|null
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sourceFilePath = $this->getSourceFilepath($input);

        $countryCode = $this->getHelper('question')->ask($input, $output, $this->createCountryQuestion());
        $admLevel = $this->getHelper('question')->ask($input, $output, $this->createLevelQuestion());

        $targetFilepath = $this->createTargetFilepath($countryCode);
        $this->createTargetFileIfNeeded($targetFilepath, $admLevel, $input, $output);

        // VALIDATION
        echo "Validate $sourceFilePath";

        $count = $this->getCount($sourceFilePath);
        echo " (file size is $count lines)\n";

        // $header = $this->getCSVHeader($sourceFilePath);
        $codeColumn = $this->getCodeColumnIndex($input, $output, $sourceFilePath);
        $nameColumn = $this->getNameColumnIndex($input, $output, $sourceFilePath);
        $parentCodeColumn = $this->getParentCodeColumnIndex($input, $output, $sourceFilePath);

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();
        try {
            $this->validate(
                $progressBar,
                $targetFilepath,
                $sourceFilePath,
                $codeColumn,
                $nameColumn,
                $parentCodeColumn
            );
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";

            return 1;
        }
        $progressBar->finish();
        echo "\n";

        $onlyValidation = new ConfirmationQuestion('Validation were OK, generate changes? [Y/n] ', true);
        if (!$this->getHelper('question')->ask($input, $output, $onlyValidation)) {
            return;
        }

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        [$added, $omitted] = $this->saveNewLocations(
            $targetFilepath,
            $sourceFilePath,
            $parentCodeColumn,
            $codeColumn,
            $nameColumn,
            $progressBar,
            $admLevel
        );

        $progressBar->finish();
        echo "\nDONE, added $added, omitted $omitted\n";

        return 0;
    }

    /**
     * @return string[]
     */
    private function getSourceFilepath(InputInterface $input): string
    {
        $filepath = $input->getArgument('sourceFile');
        if (!file_exists($filepath)) {
            throw new InvalidArgumentException('Unable to find source file with Adms for import: ' . $filepath);
        }

        return $filepath;
    }

    private function createTargetFilepath(string $countryCode): string
    {
        return __DIR__ . '/../Resources/locations/' . $countryCode . '.xml';
    }

    private function getCount(string $filepath): int
    {
        $count = 0;
        foreach ($this->getCSVLines($filepath) as $line) {
            ++$count;
        }

        return $count;
    }

    private function getCSVHeader(string $filePath): array
    {
        $file = fopen($filePath, 'r');
        $line = fgetcsv($file);
        fclose($file);

        return $line;
    }

    private function getCSVLines(string $filePath): iterable
    {
        $file = fopen($filePath, 'r');
        $first = true;
        while (($line = fgetcsv($file)) !== false) {
            if (!$first) {
                yield $line;
            } else {
                $first = false;
            }
        }
        fclose($file);
    }

    protected function createCountryQuestion(): Question
    {
        $question = new Question('Country code in ISO3 format? (example: KHM) ');
        $question->setValidator(function ($answer) {
            if (!is_string($answer) || 3 != strlen($answer)) {
                throw new RuntimeException('Please use ISO3 format');
            }

            return $answer;
        });
        $question->setNormalizer(fn($value) => $value ? strtolower(trim((string) $value)) : '');

        return $question;
    }

    protected function createLevelQuestion(): Question
    {
        $question = new Question('Which level do you want import? (example: ADM2) ');
        $question->setValidator(function ($answer) {
            if (!is_string($answer) || 4 != strlen($answer) || !preg_match('/adm\d/', strtolower($answer))) {
                throw new RuntimeException('Please use ADMx format');
            }

            return $answer;
        });
        $question->setNormalizer(fn($value) => $value ? strtolower(trim((string) $value)) : '');

        return $question;
    }

    private function createCountryNameQuestion(): Question
    {
        $question = new Question('What is country name in EN? ');
        $question->setValidator(function ($answer) {
            if (!is_string($answer) || 2 >= strlen($answer)) {
                throw new RuntimeException('Please use at least 3 letters');
            }

            return $answer;
        });

        return $question;
    }

    private function createCountryADM0Question(): Question
    {
        $question = new Question('What is ADM0/ISO2 country abbrev.? (example: CZ) ');
        $question->setValidator(function ($answer) {
            if (!is_string($answer) || 2 != strlen($answer)) {
                throw new RuntimeException('Please use ISO2 format');
            }

            return $answer;
        });
        $question->setNormalizer(fn($value) => $value ? strtoupper(trim((string) $value)) : '');

        return $question;
    }

    private function createTargetFileIfNeeded(
        string $targetFilepath,
        string $admLevel,
        InputInterface $input,
        OutputInterface $output
    ): void {
        if (!file_exists($targetFilepath)) {
            if ($admLevel === 'adm1') {
                $countryName = $this->getHelper('question')->ask($input, $output, $this->createCountryNameQuestion());
                $adm0Code = $this->getHelper('question')->ask($input, $output, $this->createCountryADM0Question());

                $f = fopen($targetFilepath, 'w');
                fwrite($f, "<?xml version=\"1.0\"?>\n<adm0 name=\"$countryName\" code=\"$adm0Code\"></adm0>");
                fclose($f);
            } else {
                if (!file_exists($targetFilepath)) {
                    throw new InvalidArgumentException('target file must exists: ' . $targetFilepath);
                }
            }
        }
    }

    private function getCodeColumnIndex(InputInterface $input, OutputInterface $output, string $sourceFilePath)
    {
        $header = $this->getCSVHeader($sourceFilePath);
        $question = $this->columnChoice($sourceFilePath, 'codes', $header);
        $answer = $this->getHelper('question')->ask($input, $output, $question);

        return array_search($answer, $header);
    }

    private function getNameColumnIndex(InputInterface $input, OutputInterface $output, string $sourceFilePath)
    {
        $header = $this->getCSVHeader($sourceFilePath);
        $question = $this->columnChoice($sourceFilePath, 'names', $header);
        $answer = $this->getHelper('question')->ask($input, $output, $question);

        return array_search($answer, $header);
    }

    private function getParentCodeColumnIndex(InputInterface $input, OutputInterface $output, string $sourceFilePath)
    {
        $header = $this->getCSVHeader($sourceFilePath);
        $question = $this->columnChoice($sourceFilePath, 'parent codes', $header);
        $answer = $this->getHelper('question')->ask($input, $output, $question);

        return array_search($answer, $header);
    }

    private function columnChoice(string $csvFilePath, $need, $header): ChoiceQuestion
    {
        $choices = [];
        foreach ($header as $label) {
            $choices[] = $label;
        }

        return new ChoiceQuestion(
            'In which column are ' . $need,
            $choices
        );
    }

    /**
     *
     * @throws Exception
     */
    protected function validate(
        ProgressBar $progressBar,
        string $targetFilepath,
        string $sourceFilePath,
        string $codeColumnIndex,
        string $nameColumnIndex,
        string $parentCodeColumnIndex
    ): void {
        $admCodes = [];
        $admCodeDuplicities = [];
        $admParentCodeMissing = [];
        $emptyLines = 0;
        $xml = new SimpleXMLElement(file_get_contents($targetFilepath));
        foreach ($this->getCSVLines($sourceFilePath) as $line) {
            $code = trim((string) $line[$codeColumnIndex]);
            $name = trim((string) $line[$nameColumnIndex]);
            $parent = trim((string) $line[$parentCodeColumnIndex]);

            if (empty($code)) {
                ++$emptyLines;
                continue;
            }

            // duplicity in file
            // if (isset($admCodes[$code])) {
            //     if (isset($admCodeDuplicities[$code])) {
            //         $admCodeDuplicities[$code]['count']++;
            //         $admCodeDuplicities[$code]['names'][] = $name;
            //         array_unique($admCodeDuplicities[$code]['names']);
            //     } else {
            //         $admCodeDuplicities[$code] = [
            //             'names' => [$name],
            //             'count' => 1,
            //         ];
            //     }
            // } else {
            //     $admCodes[$code] = true;
            // }

            // parent existence
            $xpath = $xml->xpath("//*[@code='$parent']");
            if (($xpath === null ? 0 : count($xpath)) < 1) {
                if (isset($admParentCodeMissing[$parent])) {
                    $admParentCodeMissing[$parent]['count']++;
                } else {
                    $admParentCodeMissing[$parent] = [
                        'count' => 1,
                    ];
                }
            }

            $progressBar->advance();
        }

        // if (count($admCodeDuplicities) !== 0) {
        //     echo "\nCode duplicities:\n";
        //     foreach ($admCodeDuplicities as $codeDuplicity => ['name' => $name, 'count' => $count]) {
        //         echo "$codeDuplicity;$name;$count times\n";
        //     }
        //     throw new \Exception("There are duplicities");
        // }
        if (count($admParentCodeMissing) !== 0) {
            echo "\nParent codes missing:\n";
            foreach ($admParentCodeMissing as $codeMissing => ['count' => $count]) {
                echo "$codeMissing;$count times\n";
            }
            throw new Exception("There are missing parents");
        }
        if ($emptyLines > 0) {
            echo "There is $emptyLines lines without code\n";
        }
        unset($admCodes);
        unset($admCodeDuplicities);
    }

    /**
     *
     * @return int[]
     */
    private function saveNewLocations(
        string $targetFilepath,
        string $sourceFilePath,
        string $parentCodeColumn,
        string $codeColumn,
        string $nameColumn,
        ProgressBar $progressBar,
        string $admLevel
    ): array {
        $xml = new SimpleXMLElement(file_get_contents($targetFilepath));

        $added = 0;
        $omitted = 0;
        foreach ($this->getCSVLines($sourceFilePath) as $line) {
            $parent = trim((string) $line[$parentCodeColumn]);
            $code = trim((string) $line[$codeColumn]);
            $name = trim((string) $line[$nameColumn]);

            if (($xml->xpath("//*[@code='$code']") === null ? 0 : count($xml->xpath("//*[@code='$code']"))) > 0) {
                // already imported
                $progressBar->advance();
                $omitted++;
                continue;
            }

            $xpath = "//*[@code='$parent']";

            /** @var SimpleXMLElement $parentElement */
            $parentElement = $xml->xpath($xpath)[0];
            $adm = $parentElement->addChild($admLevel);
            $adm->addAttribute('code', $code);
            $adm->addAttribute('name', $name);
            $added++;

            $progressBar->advance();
        }
        $xml->saveXML($targetFilepath);

        return [$added, $omitted];
    }
}
