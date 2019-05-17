<?php

namespace ReportingBundle\Utils;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use ReportingBundle\Entity\ReportingIndicator;
use ReportingBundle\Utils\Model\IndicatorInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ReportingService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }


    public function getFilteredData(Array $filters) {


        switch ($filters['report']) {
            case 'countries':
                $reportType = 'Country';
                break;
            case 'projects':
                $reportType = 'Project';
                break;
            case 'distributions':
                $reportType = 'Distribution';
                break;
            default:
                $reportType = null;
        }

        $allIndicators = $this->container->get('reporting.finder')->getIndicatorsByType($reportType);
        dump($allIndicators);
        $filteredGraphs = [];

        foreach ($allIndicators as $indicator) {
            dump($indicator);
            $values = $this->container->get('reporting.computer')->compute($indicator, $filters);
            array_push($filteredGraphs, [
                "graphType" => $indicator->getGraph(),
                "values" => $values,
                "name" => $indicator->getReference()
            ]);
        }
        return $filteredGraphs;
    }

    public function exportToCsv($indicatorsId, $frequency, $projects, $distributions, $country, $type)
    {
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

        return $this->container->get('export_csv_service')->export($res, 'reporting', $type);
    }
}
