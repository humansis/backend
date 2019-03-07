<?php

namespace ReportingBundle\Utils\Formatters;


/**
 * Class DefaultFormatter
 * @package ReportingBundle\Utils\Formatters
 */
class DefaultFormatter
{
    /**
     * Update indicator's format to have series
     * First key need always to be 'name' for the general name
     *
     * @param array $dataComputed
     * @return array
     */
    public function formatWithSeries($dataComputed) {
        $data = [];
        $names = [];
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

        return $data;
    }

    /**
     * Encode data which doesn't need series in json
     *
     * @param array $dataComputed
     * @return array
     */
    public function formatWithoutSeries($dataComputed) {
        return $dataComputed;
    }


    /**
     * Update indicator's format to have series and date
     * First key need always to be 'name' for the general name
     *
     * @param array $dataComputed
     * @return array
     */
    public function formatWithDateSeries($dataComputed) {
        $data = [];
        $names = [];
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

        return $data;
    }

}