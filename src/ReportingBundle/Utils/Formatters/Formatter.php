<?php

namespace ReportingBundle\Utils\Formatters;

use ReportingBundle\Utils\Formatters\FormatterInterface;
use JMS\Serializer\SerializationContext;

class Formatter implements FormatterInterface {

    /** @var int  */
    const DefaultFormat = 0;

    /** @var int */
    const CsvFormat = 1;

    /**
     * Formatter constructor.
     */
    public function __construct()
    { }

    /**
     * Use to know which format is mandatory for the graph then return data in the good format
     *
     * @param $type
     * @param array $dataComputed
     * @param string $typeGraph
     * @return array
     * @throws \Exception
     */
    public function format($type, $dataComputed, $typeGraph)
    {
        $result = [];

        switch ($type) {
            case self::DefaultFormat:
                $result = $this->defaultFormat($dataComputed, $typeGraph);
                break;

            case self::CsvFormat:
                $result = $this->csvFormat($dataComputed, $typeGraph);
                break;
        }

        return $result;
    }

    /**
     * @param $dataComputed
     * @param $typeGraph
     * @return array
     */
    private function defaultFormat($dataComputed, $typeGraph)
    {
        $result = [];

        switch($typeGraph) {
            case "stackbar":
                $result = DefaultFormatter::formatWithSeries($dataComputed);
                break;
            case "pie":
            case "bar":
            case "grid":
            case "number":
                $result = DefaultFormatter::formatWithoutSeries($dataComputed);
                break;
            case "line":
                $result = DefaultFormatter::formatWithDateSeries($dataComputed);
                break;
        }

        return $result;
    }

    /**
     * @param $dataComputed
     * @param $typeGraph
     * @return array
     * @throws \Exception
     */
    private function csvFormat($dataComputed, $typeGraph)
    {
        $result = [];

        switch($typeGraph) {
            case "stackbar":
                $result = CsvFormatter::formatWithSeries();
                break;
            case "pie":
            case "bar":
            case "grid":
            case "number":
                $result = CsvFormatter::formatWithoutSeries($dataComputed);
                break;
            case "line":
                $result = CsvFormatter::formatWithDateSeries($dataComputed);
                break;
        }

        return $result;
    }

}