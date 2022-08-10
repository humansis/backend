<?php

namespace NewApiBundle\Command;

use PhpOffice\PhpSpreadsheet\IOFactory;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Generate translations .xlf files from source .csv (with ";" as divider).
 * Command expects translations.csv file in app/Resources/translations directory
 * with following structure (almost the same as generated in CommonController::translationsDownload):
 * - 2nd row: headers: id;resname;source;(language code);
 * - filename row
 * - rows with id, resname, source and translation for each language defined in 2nd row
 *
 * after generating .xlf files, run reformat code in IDE to prevent irrelevant changes in git
 */
class TranslationsUpdateGenerateCommand extends Command
{
    /** @var array */
    private $files = [];

    /** @var array */
    private $languages = [];

    /** @var OutputInterface */
    private $output;

    /** @var string */
    private $translationsDir;

    public function __construct(string $translationsDir)
    {
        parent::__construct();

        $this->translationsDir = $translationsDir;
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setName('translation:update:generate')
            ->setDescription('Regenerate .xlf translations from csv');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        
        $file = new File($this->translationsDir.'/translations.xlsx');
        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $worksheet = $reader->load($file->getRealPath())->getActiveSheet();
        $lines = $worksheet->toArray();
        
        array_shift($lines); // remove first line

        //get languages and remove second line
        $headers = array_shift($lines);
        
        $this->languages = array_slice($headers, 4);
        
        foreach ($lines as $index => $cells) {

            if ($cells[0] === null || $cells[0] === '') { // skip empty row
                continue;
            }

            if (count($cells) !== 4 + count($this->languages)) {
                throw new \Exception(
                    'Invalid number of cells (check source csv for multiline translations near line #'
                    .($index + 3).',  "'.$cells.'"'
                );
            }
            
            if ($cells[1] === $cells[3] && ($cells[3] === null || $cells[3] === '')) {
                $this->storeFiles();
                $this->initFiles($cells[0]);
            } else {
                $this->addRow($cells);
            }
        }

        $this->storeFiles();

        return 0;
    }

    private function addRow($cells): void
    {
        foreach ($this->languages as $index => $language) {

            // skip empty translation
            if (
                $cells[4 + $index] === null
                || $cells[4 + $index] === ''
            ) { 
                continue;
            }
            
            /** @var SimpleXMLElement $tu */
            $tu = $this->files[$language]['xml']->file[0]->body[0]->addChild('trans-unit');
            $tu->addAttribute('id', $cells[0]);
            $tu->addAttribute('resname', $cells[1]);

            $tu->source = $cells[3];
            $tu->target = $cells[4 + $index];
        }
    }

    private function initFiles(string $filename): void
    {
        $this->output->writeln('<info>Generating translations for '.$filename.'</info>');
        foreach ($this->languages as $language) {
            $this->files[$language] = [
                'filename' => $filename.'.'.$language.'.xlf',
                'xml' => $this->initXml($language),
            ];
        }
    }

    private function initXml(string $language): SimpleXMLElement
    {
        $xml = new SimpleXMLElement(
            <<<XML
<?xml version="1.0" encoding="UTF-8"?><xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2" />
XML
        );

        $file = $xml->addChild('file');
        $file->addAttribute('source-language', 'en');
        $file->addAttribute('target-language', $language);
        $file->addAttribute('datatype', 'plaintext');
        $file->addAttribute('original', 'file.ext');

        $header = $file->addChild('header');
        $tool = $header->addChild('tool');
        $tool->addAttribute('tool-id', 'symfony');
        $tool->addAttribute('tool-name', 'Symfony');

        $file->addChild('body');

        return $xml;
    }

    private function storeFiles(): void
    {
        foreach ($this->files as $file) {
            if (!isset($file['filename']) || $file['filename'] === '') {
                continue;
            }

            $file['xml']->asXML($this->translationsDir.'/'.$file['filename']);
        }
    }
}