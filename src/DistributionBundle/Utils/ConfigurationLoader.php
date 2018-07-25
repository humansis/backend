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


    public function load()
    {
        $criteriaFormatted = ["default" => [], "table" => []];
        foreach ($this->criteria as $criterion => $type)
        {
            if (in_array($type, $this->MAPPING_TYPE_DEFAULT))
            {
                $criteriaFormatted["default"][] = ["field" => $criterion, "type" => $type];
            }
            else
            {
                $instances = $this->em->getRepository($type)->findAll();
                dump($instances);
                $criteriaFormatted["table"][] = ["field" => $criterion, "type" => $instances];

            }
//            elseif ("id" === strval(strtolower($type)))
//            {
//
//            }
        }

        return $criteriaFormatted;
    }
}