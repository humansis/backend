<?php

namespace CommonBundle\Command;

use Doctrine\DBAL\Connection;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XMLReader;

class AdmCSV2XMLCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:adm:convert')
            ->setDescription('Check CSV file consistency and transform it into importable xml file')
            ->addArgument('country', InputArgument::REQUIRED, 'Country code used for xml file generation')
            ->addArgument('level', InputArgument::REQUIRED, 'ADM_ level, {adm1,adm2,adm3,adm4,adm5}')
            ->addArgument('sourceFile', InputArgument::REQUIRED, 'Source file in CSV format')
            ->addOption('validate', null, InputArgument::OPTIONAL, 'Run only source file validations')
            ->addArgument('name', InputArgument::REQUIRED, 'Column header with ADM name')
            ->addArgument('parent', InputArgument::REQUIRED, 'Column header with ADM code of parent')
            ->addArgument('code', InputArgument::REQUIRED, 'Column header with ADM code')
            ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $targetFilepath = $this->createTargetFilepath($input);
        $sourceFilePath = $this->getSourceFilepath($input);

        // VALIDATION
        echo "Validate $sourceFilePath";

        $count = $this->getCount($sourceFilePath);
        echo " (file size is $count lines)\n";

        $header = $this->getCSVHeader($sourceFilePath);
        $codeColumn = array_search($input->getArgument('code'), $header);
        $nameColumn = array_search($input->getArgument('name'), $header);
        $parentCodeColumn = array_search($input->getArgument('parent'), $header);

        echo "Columns: code=$codeColumn; name=$nameColumn; parent code=$parentCodeColumn\n";

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        $admCodes = [];
        $admCodeDuplicities = [];
        $admParentCodeMissing = [];
        $xml = new \SimpleXMLElement(file_get_contents($targetFilepath));
        foreach ($this->getCSVLines($sourceFilePath) as $line) {
            $code = $line[$codeColumn];
            $name = $line[$nameColumn];
            $parent = $line[$parentCodeColumn];

            // duplicity in file
            if (isset($admCodes[$code])) {
                if (isset($admCodeDuplicities[$code])) {
                    $admCodeDuplicities[$code]['count']++;
                } else {
                    $admCodeDuplicities[$code] = [
                        'name' => $name,
                        'count' => 1,
                    ];
                }
            } else {
                $admCodes[$code] = true;
            }

            // parent existence
            $xpath = $xml->xpath("//*[@code='$parent']");
            if (count($xpath) < 1) {
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
        $progressBar->finish();

        if (count($admCodeDuplicities) !== 0) {
            echo "\nCode duplicities:\n";
            foreach ($admCodeDuplicities as $codeDuplicity => ['name'=>$name, 'count'=>$count]) {
                echo "$codeDuplicity;$name;$count times\n";
            }
            return 1;
        }
        if (count($admParentCodeMissing) !== 0) {
            echo "\nParent codes missing:\n";
            foreach ($admParentCodeMissing as $codeMissing => ['count'=>$count]) {
                echo "$codeMissing;$count times\n";
            }
            return 1;
        }
        echo "\nOK \n";
        unset($admCodes);
        unset($admCodeDuplicities);

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        $xml = new \SimpleXMLElement(file_get_contents($targetFilepath));

        foreach ($this->getCSVLines($sourceFilePath) as $line) {
            $parent = $line[$parentCodeColumn];
            $code = $line[$codeColumn];
            $name = $line[$nameColumn];

            $xpath = "//*[@code='$parent']";

            /** @var \SimpleXMLElement $parentElement */
            $parentElement = $xml->xpath($xpath)[0];
            $adm = $parentElement->addChild($input->getArgument('level'));
            $adm->addAttribute('code', $code);
            $adm->addAttribute('name', $name);

            $progressBar->advance();
        }
        $xml->saveXML($targetFilepath);

        $progressBar->finish();
        echo "\nDONE\n";

        return 0;
    }

    /**
     * @param InputInterface $input
     *
     * @return string[]
     */
    private function getSourceFilepath(InputInterface $input): string
    {
        $filepath = $input->getArgument('sourceFile');
        if (!file_exists($filepath)) {
            throw new InvalidArgumentException('Unable to find source file with Adms for import: '.$filepath);
        }

        return $filepath;
    }

    private function createTargetFilepath(InputInterface $input): string
    {
        $filepath = __DIR__.'/../Resources/locations/'.strtolower($input->getArgument('country')).'.xml';

        if (!file_exists($filepath)) {
            $f = fopen($filepath, 'w');
            fwrite($f, "<?xml version=\"1.0\"?>\n<adm0 name=\"Mongolia\" code=\"MN\"></adm0>");
            fclose($f);
        }

        return $filepath;
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
        while (($line = fgetcsv($file)) !== FALSE) {
            if (!$first) {
                yield $line;
            } else {
                $first = false;
            }
        }
        fclose($file);
    }
}
