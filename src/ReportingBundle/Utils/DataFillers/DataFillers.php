<?php

namespace ReportingBundle\Utils\DataFillers;

use ReportingBundle\Utils\DataFillers\DataFillerInterface;
use ReportingBundle\Utils\DataFillers\DataFillersCountry;
use ReportingBundle\Utils\DataFillers\DataFillersProject;
use ReportingBundle\Utils\DataFillers\DataFillersDistribution;
use ReportingBundle\Utils\Model\IndicatorInterface;

use Doctrine\ORM\EntityManager;

class DataFillers implements DataFillerInterface
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;   
    }

    public function fill(IndicatorInterface $indicator)
    {

        if(preg_match("#^EU?_C#", $indicator->getCode())) 
        {
            if(is_callable(array(new DataFillersCountry($this->em), $indicator->getCode())))
            {
                return call_user_func_array([new DataFillersCountry($this->em), $indicator->getCode()], []);
        
            }
        }

        if(preg_match("#^EU?_P#", $indicator->getCode())) 
        {
            if(is_callable(array(new DataFillersProject($this->em), $indicator->getCode())))
            {
                return call_user_func_array([new DataFillersProject($this->em), $indicator->getCode()], []);
            }
        }

        if(preg_match("#^EU?_D#", $indicator->getCode())) 
        {
            if(is_callable(array(new DataFillersDistribution($this->em), $indicator->getCode())))
            {
                return call_user_func_array([new DataFillersDistribution($this->em), $indicator->getCode()], []);
            }
        }

    }

}
