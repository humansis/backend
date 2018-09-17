<?php

namespace ReportingBundle\Utils\Finders;

use ReportingBundle\Utils\Finders\FinderInterface;
use ReportingBundle\Entity\ReportingIndicator;

use Doctrine\ORM\EntityManager;

class Finder implements FinderInterface {

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var
     */
    private $repository;


    /**
     * Finder constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em; 
    }

    /**
     * Search an indicator with its code and return indicator with its id, its name and the type of its graph
     * 
     * @return object
     */
    public function findIndicator() 
    {
        $data = [];
        $this->repository = $this->em->getRepository(ReportingIndicator::class);
         $indicators = $this->repository->findAll();  
         foreach($indicators as $indicator) {
            if(preg_match("#^BMS_#", $indicator->getCode())) 
            {
                $type = explode('_', $indicator->getCode());
                $infoIndicator = [
                            'type_graph' => $indicator->getGraph(),
                            'id' => $indicator->getId(),
                            'full_name' => $indicator->getReference(),
                            'filter' => $indicator->getFilters(),
                            'type' => $type[1]
                        ];
                array_push($data, (object) $infoIndicator);
            }
         }
        return $data; 
           
    }
}