<?php

namespace ReportingBundle\Utils\Computers;

use ReportingBundle\Utils\Computers\ComputerInterface;
use ReportingBundle\Utils\Model\IndicatorInterface;

use ReportingBundle\Utils\DataRetrievers\CountryDataRetrievers;
use ReportingBundle\Utils\DataRetrievers\ProjectDataRetrievers;
use ReportingBundle\Utils\DataRetrievers\DistributionDataRetrievers;

use Doctrine\ORM\EntityManager;

class Computer implements ComputerInterface {

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var ProjectDataRetrievers
     */
    private $project;


    /**
     * Computer constructor.
     * @param EntityManager $em
     * @param ProjectDataRetrievers $project
     */
    public function __construct(EntityManager $em, ProjectDataRetrievers $project)
    {
        $this->em = $em; 
        $this->project = $project;
    }

    /**
     * Search in all data retrievers if the code exists
     * Call the good function after find it
     * @param IndicatorInterface $indicator
     * @param array $filters
     * @return mixed
     */
    public function compute(IndicatorInterface $indicator , array $filters = []) 
    {
        $filters['__'] = [
                    'functionName' => $indicator->getCode()
        ];

        if(preg_match("#^BMS_C#", $indicator->getCode())) 
        {
            if(is_callable(array(new CountryDataRetrievers($this->em), $indicator->getCode())))
            {
                return call_user_func_array([new CountryDataRetrievers($this->em), $indicator->getCode()], [$filters]);
        
            }
        }

        if(preg_match("#^BMS_P#", $indicator->getCode())) 
        {
            if(is_callable(array(new ProjectDataRetrievers($this->em), $indicator->getCode())))
            {
                return call_user_func_array([new ProjectDataRetrievers($this->em), $indicator->getCode()], [$filters]);
            }
        }

        if(preg_match("#^BMS_D#", $indicator->getCode())) 
        {
            if(is_callable(array(new DistributionDataRetrievers($this->em, $this->project), $indicator->getCode())))
            {
                return call_user_func_array([new DistributionDataRetrievers($this->em, $this->project), $indicator->getCode()], [$filters]);
            }
        }
    }
}