<?php

namespace ReportingBundle\Utils\Formatters;

use ReportingBundle\Utils\Formatters\FormatterInterface;
use JMS\Serializer\SerializationContext;

class Formatter implements FormatterInterface {

    public function __construct()
    { }

    /**
     * Use to know which format is mandatory for the graph then return data in the good format
     * 
     * @param array $dataComputed
     * @param string $typeGraph
     * @return json
     */
    public function format($dataComputed, $typeGraph) {
        switch($typeGraph) {
            case "histogramme":
                $result = Formatter::formatWithSeries($dataComputed);
                return $result;
            case "camembert":
                $result = Formatter::formatWithoutSeries($dataComputed);
                return $result;
            case "bar":
                $result = Formatter::formatWithoutSeries($dataComputed);
                return $result;
            case "cible":
                $result = Formatter::formatWithoutSeries($dataComputed);
                return $result;
            case "nombre":
                $result = Formatter::formatWithoutSeries($dataComputed);
                return $result;
            case "courbe":
                $result = Formatter::formatWithDateSeries($dataComputed);
                return $result;
        }
    }

    /**
     * Update indicator's format to have series
     * First key need always to be 'name' for the general name
     * 
     * @param array $dataComputed
     * @return json
     */
    public function formatWithSeries($dataComputed) {
        $data = [];
        $names = [];
        $formats = [];
        foreach($dataComputed as $indicator) {
            array_push($names, $indicator['name']); 
        }

        foreach(array_unique($names) as $name) {
             $format = [
                'name' => $name,
                'series' => [
                ]
            ];
            foreach($dataComputed as $indicator) {
                $value = [
                    'name' => $indicator['unity'],
                    'value' => intval($indicator['value']),
                    'unity' => $indicator['unity']
                ];
                if($format['name'] === $indicator['name']) {
                    array_push($format['series'], (object) $value);                  
                }    
            }
            array_push($data, (object) $format);
        }
        return json_encode($data);
    }

    /**
     * Encode data which doesn't need series in json
     * 
     * @param array $dataComputed
     * @return json
     */
    public function formatWithoutSeries($dataComputed) {
        return json_encode($dataComputed);    
    }


    /**
     * Update indicator's format to have series and date
     * First key need always to be 'name' for the general name
     * 
     * @param array $dataComputed
     * @return json
     */
    public function formatWithDateSeries($dataComputed) {
        $data = [];
        $names = [];
        $formats = [];
        foreach($dataComputed as $indicator) {
            array_push($names, $indicator['name']); 
        }

        foreach(array_unique($names) as $name) {
             $format = [
                'name' => $name,
                'series' => [
                ]
            ];
            foreach($dataComputed as $indicator) {
                $value = [
                    'name' => $indicator['date'],
                    'value' => intval($indicator['value']),
                    'unity' => $indicator['unity']
                ];
                if($format['name'] === $indicator['name']) {
                    array_push($format['series'], (object) $value);                  
                }    
            }
            array_push($data, (object) $format);
        }
        return json_encode($data);
    }
}