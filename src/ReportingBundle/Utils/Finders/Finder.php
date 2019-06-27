<?php

namespace ReportingBundle\Utils\Finders;

use ReportingBundle\Utils\Finders\FinderInterface;
use ReportingBundle\Entity\ReportingIndicator;

use Doctrine\ORM\EntityManager;

/**
 * Class Finder
 * @package ReportingBundle\Utils\Finders
 */
class Finder implements FinderInterface
{

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
     * @param string|null $type
     * @return ReportingIndicator[]
     */
    public function getIndicatorsByType(string $type = null) {
        $this->repository = $this->em->getRepository(ReportingIndicator::class);

        return $this->repository->findByType($type);
    }

    /**
     * Search an indicator with its code and return indicator with its id, its name and the type of its graph
     *
     * @return array
     */
    public function generateIndicatorsData()
    {
        $data = [];
        $indicators = $this->getIndicatorsByType();
        foreach ($indicators as $indicator) {
            $type = explode('_', $indicator->getCode());
            $infoIndicator = [
                        'type_graph' => $indicator->getGraph(),
                        'id' => $indicator->getId(),
                        'full_name' => $indicator->getReference(),
                        'filter' => $indicator->getFilters(),
                        'type' => $type[1],
                        'code' => $indicator->getCode(),
                    ];
            array_push($data, (object) $infoIndicator);
        }
        return $data;
    }
}
