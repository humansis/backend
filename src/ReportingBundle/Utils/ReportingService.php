<?php

namespace ReportingBundle\Utils;

use CommonBundle\Utils\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use ReportingBundle\Entity\ReportingIndicator;
use ReportingBundle\Utils\Finders\Finder;
use Symfony\Component\HttpFoundation\ParameterBag;

class ReportingService
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    /** @var ExportService */
    private $exportCSVService;

    /** @var Finder */
    private $reportingFinder;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container, ExportService $exportCSVService,
                                Finders\Finder $reportingFinder
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->exportCSVService = $exportCSVService;
        $this->reportingFinder = $reportingFinder;
    }


    public function getFilteredData(Array $filters) {

        // Format incoming object
        $filters = $this->formatFiltersIfEmpty($filters);
        $filters = $this->formatReportType($filters);

        $allIndicators = $this->reportingFinder->getIndicatorsByType($filters['report']);
        $filteredGraphs = [];

        foreach ($allIndicators as $indicator) {
            $values = $this->container->get('reporting.computer')->compute($indicator, $filters);
            array_push($filteredGraphs, [
                "graphType" => $indicator->getGraph(),
                "values" => $values,
                "name" => $indicator->getReference()
            ]);
        }
        return $filteredGraphs;
    }

    public function exportToCsv(ParameterBag $request, string $type)
    {
        $filters = [
            'report' => $request->get('report'),
            'distributions' => $request->get('distributions'),
            'projects' => $request->get('projects'),
            'frequency' => $request->get('frequency'),
            'country' => $request->get('country'),
            'period' => $request->get('period'),
        ];

        // Format incoming object
        $filters = $this->formatFiltersIfEmpty($filters);
        $filters = $this->formatReportType($filters);


        /** @var ReportingIndicator[] $indicators */
        $indicators = $this->reportingFinder->getIndicatorsByType($filters['report']);

        $indicatorsTable = [];

        /** Loop over the indicators id */


        //Output file layout:

        // |INDICATOR NAME|       |       |       |
        // |              |PERIOD1|PERIOD2|PERIOD3|
        // |LABEL1        |VALUE  |VALUE  |VALUE  |
        // |LABEL2        |VALUE  |VALUE  |VALUE  |
        // |LABEL3        |VALUE  |VALUE  |VALUE  |
        // ###############BLANK LINE###############
        // |INDICATOR NAME|       |       |       |
        // |              |PERIOD1|PERIOD2|PERIOD3|
        // |LABEL1        |VALUE  |VALUE  |VALUE  |
        // |LABEL2        |VALUE  |VALUE  |VALUE  |
        // |LABEL3        |VALUE  |VALUE  |VALUE  |

        foreach ($indicators as $indicator) {

            //Period row should start with a blank
            $periodRow = [''];
            $periodColumns = [];
            $maxValuesCount = 0;
            $labelsColumn = [];

            $indicatorValue = $this->container->get('reporting.computer')->compute($indicator, $filters);

            foreach ($indicatorValue as $period => $values) {
                $periodRow[] = $period;
                foreach ($values as $value) {

                    $label = (array_key_exists('name', $value)? $value['name']: $value['unity']);
                    $periodColumns[$period][$label] = $value['value'];

                    if(!in_array($label, $labelsColumn)) {
                        $labelsColumn[] = $label;
                    }
                }
            }
            $values_rows = [];
            foreach ($labelsColumn as $indicatorLabel) {
                $value_row = [$indicatorLabel];
                foreach ($periodColumns as $period => $values) {
                    // If the label don't have a value in this period, cell will be blank
                    $value_row[] = (array_key_exists($indicatorLabel, $values)? $values[$indicatorLabel]: '');
                }
                $values_rows[] = $value_row;
            }
            // Concatenate the indicator title, periods, values and a blank line
            $indicatorTable = array_merge([[$indicator->getReference()]], [$periodRow], $values_rows, [['']]);
            $indicatorsTable = array_merge($indicatorsTable, $indicatorTable);
        }
        return $this->exportCSVService->exportRaw($indicatorsTable, 'reporting', $type);
    }

    private function formatFiltersIfEmpty($emptyStringOrArrayFilters) {

        $arrayFilters['period'] = $emptyStringOrArrayFilters['period'] === '' ? [] : explode(',', $emptyStringOrArrayFilters['period']);
        $arrayFilters['projects'] = $emptyStringOrArrayFilters['projects'] === '' ? [] : explode(',', $emptyStringOrArrayFilters['projects']);
        $arrayFilters['distributions'] = $emptyStringOrArrayFilters['distributions'] === '' ? [] : explode(',', $emptyStringOrArrayFilters['distributions']);
        return array_merge($emptyStringOrArrayFilters, $arrayFilters);
    }

    private function formatReportType(array $filters) {
        switch ($filters['report']) {
            case 'countries':
                $filters['report'] = 'Country';
                break;
            case 'projects':
                $filters['report'] = 'Project';
                break;
            case 'distributions':
                $filters['report'] = 'Distribution';
                break;
            default:
                $filters['report'] = null;
        }
        return $filters;
    }
}
