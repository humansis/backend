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
        "number"
    ];

    public function __construct(EntityManagerInterface $entityManager, $criteria)
    {
        $this->em = $entityManager;
        $this->criteria = $criteria;
    }


    public function load()
    {
        $criteriaFormatted = ["default" => [], "table_id" => [], "table_value" => []];
        foreach ($this->criteria as $criterion => $type)
        {
            if (in_array($type, $this->MAPPING_TYPE_DEFAULT))
                $criteriaFormatted["default"][] = ["field" => $criterion, "type" => $type];

//            elseif ("id" === strval(strtolower($type)))
//            {
//
            }
            dump($criterion);
            dump($type);
        }

        return $criteriaFormatted;
    }
}