<?php


namespace DistributionBundle\Utils;


use Doctrine\ORM\EntityManagerInterface;

class ConfigurationLoader
{
    /** @var EntityManagerInterface $em */
    private $em;

    private $criteria;

    private $MAPPING_TYPE_DEFAULT = [
        "boolean",
        "string",
        "number",
        "date"
    ];

    public function __construct(EntityManagerInterface $entityManager, $criteria)
    {
        $this->em = $entityManager;
        $this->criteria = $criteria;
    }


    /**
     * Get all data from the config file.
     *
     * @param array $filters : [$field => $value]
     * @return array
     */
    public function load(array $filters)
    {
        $criteriaFormatted = [];
        foreach ($this->criteria as $criterion => $type)
        {
            if($criterion !== 'countrySpecific') {
                if($criterion !== 'vulnerabilityCriteria') {
                    $criteriaFormatted[] = $this->formatCriteria($filters, 'Beneficiary', $criterion, $type);
                }
                else {
                    $criteriaFormatted = array_merge($criteriaFormatted, $this->formatCriteria($filters, 'Beneficiary', $criterion, $type));
                }
            }
            else {
                $criteriaFormatted = array_merge($criteriaFormatted, $this->formatCriteria($filters, 'Household', $criterion, $type));

            }
            
        }
        return $criteriaFormatted;
    }

    private function formatCriteria(array $filters, string $distributionType, $criterion, $type) {
        if (in_array($type, $this->MAPPING_TYPE_DEFAULT))
        {
           return ["field_string" => $criterion, "type" => $type, "distributionType" => $distributionType];
        }
        else
        {
            $instances = $this->em->getRepository($type)->findForCriteria($filters);
            foreach ($instances as &$instance)
            {
                $instance->setTableString($criterion);
                $instance->setDistributionType($distributionType);
            }
        
            return $instances;
        }
    }
}