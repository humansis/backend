<?php

namespace ReportingBundle\Utils;

use Doctrine\ORM\EntityManager;
use ReportingBundle\Entity\ReportingIndicator;
use Symfony\Component\DependencyInjection\Container;

class ReportingService
{
    /** @var EntityManager $em */
    private $em;

    /** @var Container $container */
    private $container;

    public function __construct(EntityManager $em, Container $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function exportToCsv($indicatorsId, $frequency, $projects, $distributions, $country, $type) {

        $filters = [
            'frequency' => $frequency,
            'project' => $projects,
            'NoDistribution' => $distributions,
            'country' => $country
        ];

        /** @var ReportingIndicator[] $indicators */
        $indicators = $this->em->getRepository(ReportingIndicator::class)->getIndicatorsById($indicatorsId);

        $res = [];
        $dataFormatted = [];

        /** Loop over the indicators id */
        foreach ($indicators as $indicator) {
            /** Get the data of the charts */
            $dataComputed = $this->container->get('reporting.computer')->compute($indicator, $filters);
            /** Push the data by grouping them by date */
            foreach ($dataComputed as $data) {
                $key = $indicator->getReference() . ' - ' . $data['name'];
                $dataFormatted[$data['date']][$key] = $data['value'];
            }
        }

        /** Get the data by date and format them for export */
        foreach ($dataFormatted as $date => $values) {
            $row = [
                'date' => $date
            ];

            foreach ($values as $code => $value) {
                $row[$code] = $value;
            }

            $res[] = $row;
        }

        return $this->container->get('export_csv_service')->export($res,'reporting', $type);
    }
}