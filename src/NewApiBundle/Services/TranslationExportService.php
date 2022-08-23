<?php

namespace NewApiBundle\Services;

use CommonBundle\Utils\Exception\ExportNoDataException;
use CommonBundle\Utils\ExportService;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use SimpleXMLElement;
use Symfony\Component\Finder\Finder;
use UnexpectedValueException;

class TranslationExportService
{
    /** @var ExportService */
    private $exporter;

    /** @var string */
    private $translationsDir;
    
    /** @var array */
    private $locales;
    
    public function __construct(ExportService $exporter, string $translationsDir, array $locales)
    {
        $this->exporter = $exporter;
        $this->translationsDir = $translationsDir;
        $this->locales = $locales;
    }

    /**
     * @throws ExportNoDataException
     * @throws SpreadsheetException
     * @throws Exception
     * @throws \Exception
     */
    public function prepareExport(): string
    {
        $finder = new Finder();
        $finder->files()->in($this->translationsDir)->name('*.xlf');

        if (!$finder->hasResults()) {
            throw new UnexpectedValueException('No translations found');
        }

        //prepare source array of all languages
        $source = [];
        foreach ($finder as $file) {
            $xml = new SimpleXMLElement(file_get_contents($file->getRealPath()));

            [$filename, $lang, $ext] = explode('.', $file->getFilename());

            if (!isset($source[$filename])) {
                $source[$filename] = [];
            }

            $order = 1;

            foreach ($xml->file->body->{'trans-unit'} as $item) {
                $attr = $item->attributes();

                $source[$filename][(string)$attr['id']]['translate'][$lang] = (string)$item->target;

                if ($lang === 'en') {
                    $source[$filename][(string)$attr['id']]['order'] = $order++;
                    $source[$filename][(string)$attr['id']]['resname'] = (string)$attr['resname'];
                }
            }
        }

        //prepare target array to export
        $lines = [];
        $lines[0] = ['id','resname','row#','source'];
        foreach ($this->locales as $locale) {
            $lines[0][] = $locale;
        }
        $lines[] = [];

        $rowCounter = 1;

        foreach ($source as $filename => $entries) {

            $lines[] = [$filename, '', $rowCounter++];

            foreach ($entries as $id => $entry) {

                if (!isset($entry['resname'])) {
                    throw new UnexpectedValueException('Missing resname for id ' . $id);
                }

                $line = [
                    $id,
                    $entry['resname'],
                    $rowCounter++,
                    $entry['resname'],
                ];

                foreach ($this->locales as $locale) {
                    $line[] = $entry['translate'][$locale] ?? '';
                }

                $lines[] = $line;
            }
        }
        
        return $this->exporter->export($lines, 'translations', 'xlsx');
    }
}