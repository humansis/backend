<?php

namespace BeneficiaryBundle\Utils\Mapper;

use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\Household;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HouseholdToCSVMapper extends AbstractMapper
{
    /** @var Serializer $serializer */
    private $serializer;

    public function __construct(EntityManagerInterface $entityManager, Serializer $serializer)
    {
        parent::__construct($entityManager);
        $this->serializer = $serializer;
    }

    public function fromHouseholdToCSV(Worksheet $worksheet, array $receivers, $countryISO3)
    {
        $arraySheet = $worksheet->toArray(null, true, true, true);
        $mapping = $this->loadMappingCSVOfCountry($countryISO3);
        $rowHeader = $arraySheet[2];
        dump($arraySheet);
        dump($mapping);
        /** @var Household $receiver */
        foreach ($receivers as $receiver)
        {
            $householdArrayCSV = [];
            $householdArray = json_decode(
                $this->serializer
                    ->serialize(
                        $receiver,
                        'json',
                        SerializationContext::create()->setSerializeNull(true)->setGroups(["FullHousehold"])
                    ),
                true);

            dump($householdArray);
            foreach ($mapping as $fieldName => $columnCsv)
            {
                if (is_array($columnCsv))
                {
                    foreach ($columnCsv as $fieldName2 => $columnCsv2)
                    {
                        if (!array_key_exists($fieldName2, $householdArray[$fieldName]))
                            $householdArrayCSV[$columnCsv2] = null;
                        else
                            $householdArrayCSV[$columnCsv2] = $householdArray[$fieldName][$fieldName2];
                    }
                }
                else
                {
                    // TODO COMPLETE FOR COUNTRY SPECIFIC => FOUND ANSWER OF BENEFICIARY
                    if (substr($fieldName, 0, 20) === "tmp_country_specific")
                    {
                        $field = $rowHeader[$mapping[$fieldName]];
                        $countrySpecific = $this->em->getRepository(CountrySpecific::class)
                            ->findOneByFieldString($field);
//                        $countrySpecificAnswer = $this->
                    }
                    elseif (!array_key_exists($fieldName, $householdArray[$fieldName]))
                        $householdArrayCSV[$columnCsv] = null;
                    else
                    {
                        $householdArrayCSV[$columnCsv] = $householdArray[$fieldName];
                    }
                }
            }

            dump($householdArrayCSV);
            foreach ($receiver->getBeneficiaries()->getValues() as $beneficiary)
            {
                dump($beneficiary);
                foreach ($mapping as $fieldName => $csvColumn)
                {
                }
            }
        }
    }

}