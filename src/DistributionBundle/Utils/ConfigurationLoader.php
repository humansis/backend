<?php


namespace DistributionBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class ConfigurationLoader
 * @package DistributionBundle\Utils
 * @deprecated
 */
class ConfigurationLoader
{
    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * @var
     */
    public $criteria;

    /**
     * ConfigurationLoader constructor.
     * @param EntityManagerInterface $entityManager
     * @param $criteria
     */
    public function __construct(EntityManagerInterface $entityManager, $criteria)
    {
        $this->em = $entityManager;
        $this->criteria = $criteria;
    }


    /**
     * Get all data from the config file.
     *
     * @param string $countryISO3
     * @return array
     */
    public function load(string $countryISO3)
    {
        $criteriaFormatted = [];
        foreach ($this->criteria as $criterion => $info) {
            // The type can be the countrySpecific or the vulnerabilityCriteria classes, or anything else
            if ($criterion === 'countrySpecific') {
                $criteriaFormatted = array_merge($criteriaFormatted, $this->formatClassCriteria($countryISO3, $info['target'], $criterion, $info['type']));
            } else if ($criterion === 'vulnerabilityCriteria') {
                $criteriaFormatted = array_merge($criteriaFormatted, $this->formatClassCriteria($countryISO3, $info['target'], $criterion, $info['type']));
            } else {
                $criteriaFormatted[] = $this->formatOtherCriteria($info['target'], $criterion, $info['type']);
            }
        }
        return $criteriaFormatted;
    }

    /**
     * @param string $countryISO3
     * @param string $target
     * @param $criterion
     * @param $class
     * @return array
     */
    private function formatClassCriteria(string $countryISO3, string $target, $criterion, $class)
    {
        $instances = $this->em->getRepository($class)->findForCriteria($countryISO3);
        foreach ($instances as &$instance) {
            $instance->setTableString($criterion)
                ->setTarget($target);
        }
        return $instances;
    }

    /**
     * @param string $target
     * @param $criterion
     * @param $type
     * @return array
     */
    private function formatOtherCriteria(string $target, $criterion, $type)
    {
        return ["field_string" => $criterion, "type" => $type, "target" => $target, "table_string" => 'Personnal'];
    }
}
