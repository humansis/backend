<?php

namespace ReportingBundle\Utils\Formatters;


/**
 * Class CsvFormatter
 * @package ReportingBundle\Utils\Formatters
 */
class CsvFormatter
{
    /**
     * Update indicator's format to have series
     * First key need always to be 'name' for the general name
     *
     * @return void
     * @throws \Exception
     */
    public function formatWithSeries() {

        throw new \Exception('Not implemented');
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
        if(count($dataComputed) === 0) {
            return $dataComputed;
        }

        $data = [];

        /**
         * build data body from  $dataComputed which has the following format
         *
         * "name" => "TH"
         * "value" => "15"
         * "unity" => "s"
         * "date" => "2018"
         */
        foreach($dataComputed as $row) {
            array_push($data, [
                'name' => $row['date'],
                'value' => intval($row['value']),
                'unity' => $row['unity']
            ]);
        }

        return $data;
    }

}