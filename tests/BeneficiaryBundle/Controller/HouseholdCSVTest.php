<?php

namespace Tests\BeneficiaryBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\CountrySpecific;
use BeneficiaryBundle\Entity\CountrySpecificAnswer;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\NationalId;
use BeneficiaryBundle\Entity\Phone;
use BeneficiaryBundle\Entity\Profile;
use BeneficiaryBundle\Model\ImportStatistic;
use BeneficiaryBundle\Utils\ExportCSVService;
use BeneficiaryBundle\Utils\HouseholdCSVService;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\BMSServiceTestCase;


class HouseholdCSVTest extends BMSServiceTestCase
{
    /** @var HouseholdCSVService $hhCSVService */
    private $hhCSVService;
    /** @var ExportCSVService $exportCSVService */
    private $exportCSVService;

    private $addressStreet = "ADDR TEST_IMPORT";
    private $addressStreet2 = "ADDR2 TEST_IMPORT_TEST_IMPORT";
    private $addressStreet3 = "ADDR3 UNIT TEST UNIT";
    private $addressStreet4 = "ADDR4 UNIT4";

    private $UPDATED_GIVEN_NAME = "FIRSTNAME TEST_IMPORT1";

    private $SHEET_ARRAY = [
        1 => [
            "A" => "Household",
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => "Country Specifics",
            "M" => null,
            "N" => "Beneficiary",
            "O" => null,
            "P" => null,
            "Q" => null,
            "R" => null,
            "S" => null,
            "T" => null,
            "U" => null,
        ],
        2 => [
            "A" => "Address street",
            "B" => "Address number",
            "C" => "Address Postcode",
            "D" => "Livelihood",
            "E" => "Notes",
            "F" => "Latitude",
            "G" => "Longitude",
            "H" => "adm1",
            "I" => "adm2",
            "J" => "adm3",
            "K" => "adm4",
            "L" => "IDPoor",
            "M" => "WASH",
            "N" => "Given name",
            "O" => "Family name",
            "P" => "Gender",
            "Q" => "Status",
            "R" => "Date of birth",
            "S" => "Vulnerability criterions",
            "T" => "Phones",
            "U" => "National Ids",
        ],
        3 => [
            "A" => "ADDR TEST_IMPORT",
            "B" => 1.0,
            "C" => 11.0,
            "D" => 10.0,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "Rhone-Alpes",
            "I" => "Savoie",
            "J" => "Chambery",
            "K" => "Sainte Hélène sur Isère",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FIRSTNAME TEST_IMPORT",
            "O" => "NAME TEST_IMPORT",
            "P" => 0,
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ],
        4 => [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNAME2 TEST_IMPORT",
            "O" => "NAME2 TEST_IMPORT",
            "P" => 1,
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ]
    ];

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUpFunctionnal();
        $this->hhCSVService = $this->container->get('beneficiary.household_csv_service');
        $this->exportCSVService = $this->container->get('beneficiary.household_export_csv_service');
    }

    /**
     * @throws \Exception
     */
    public function testExportCSV()
    {
        $countrySpecifics = $this->em->getRepository(CountrySpecific::class)->findByCountryIso3($this->iso3);

        $filename = $this->container->get('beneficiary.household_export_csv_service')->exportToCsv('xls', $this->iso3);
        $path = getcwd() . '/' . $filename;
        
        $this->assertEquals($filename, 'pattern_household_' . $this->iso3 . '.xls');
        $this->assertFileExists($path);
        $this->assertFileIsReadable($path);
        
        $csv_content = file_get_contents($path);
        $csvArray = str_replace('"', '', explode(",", explode("\n", $csv_content)[1]));
        $this->assertTrue(gettype($csvArray) == 'array');
        
        unlink($path);
    }

    /**
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function testImportCSV()
    {
        $body_begin = $this->SHEET_ARRAY;
        
        $this->removeHousehold($this->addressStreet);
        $this->removeHousehold($this->addressStreet2);
        $this->removeHousehold($this->addressStreet3);
        $this->removeHousehold($this->addressStreet4);
        
        $projects = $this->em->getRepository(Project::class)->findAll();
        if (empty($projects))
        {
            print_r("\nThere is no project in your database.\n\n");
            return;
        }

        // TRY TO ADD CSV WITH HOUSEHOLD WITHOUT ANY KIND OF ISSUE
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $this->SHEET_ARRAY, 1, null);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $token = $return["token"];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 2, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 3, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 4, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 5, $token);
        $this->assertSame([], $return['error']);
        $household = $this->em->getRepository(Household::class)->findOneByAddressStreet($this->addressStreet);
        $this->assertInstanceOf(Household::class, $household);


        // TRY TO ADD CSV WITH TYPO ERROR => UPDATE THE OLD 'GIVEN_NAME' OF THE HEAD IN DATABASE
        $this->SHEET_ARRAY[3]['N'] = $this->UPDATED_GIVEN_NAME;
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $this->SHEET_ARRAY, 1, null);
        $token = $return["token"];
        $this->assertArrayHasKey("token", $return);
        $this->assertArrayHasKey("data", $return);
        $this->assertArrayHasKey("new", current($return["data"]));
        $this->assertArrayHasKey("old", current($return["data"]));
        $oldHousehold = current($return["data"])["old"];
        $request = [
            [
                "new" => current($return["data"])["new"],
                "id_tmp_cache" => current($return["data"])["id_tmp_cache"],
                "id_old" => $oldHousehold->getId(),
                "state" => 0
            ]
        ];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $request, 2, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 3, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 4, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 5, $token);
        $this->assertSame([], $return['error']);
        /** @var Beneficiary $headOldHousehold */
        $headOldHousehold = $this->em->getRepository(Beneficiary::class)->getHeadOfHousehold($oldHousehold);
        $this->assertSame($this->UPDATED_GIVEN_NAME, $headOldHousehold->getGivenName());
        $phones = $this->em->getRepository(Phone::class)->findByBeneficiary($headOldHousehold);
        $this->assertSame(1, count($phones));


        // TRY TO ADD CSV WITH DUPLICATE ERROR => REMOVE THE DUPLICATE IN THE CSV
        $this->SHEET_ARRAY[5] = [
            "A" => $this->addressStreet2,
            "B" => 2,
            "C" => 2,
            "D" => 2,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "Rhone-Alpes",
            "I" => "Savoie",
            "J" => "Chambery",
            "K" => "Sainte Hélène sur Isère",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FIRSTNAME3 UNIT_TEST",
            "O" => "FNAME33 UNIT_TEST",
            "P" => 0,
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ];
        $this->SHEET_ARRAY[6] = [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNAME2 TEST_IMPORT",
            "O" => "NAME2 TEST_IMPORT",
            "P" => 1,
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $this->SHEET_ARRAY, 1, null);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $token = $return["token"];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 2, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertArrayHasKey("token", $return);
        $this->assertArrayHasKey("new_household", current($return["data"]));
        $this->assertArrayHasKey("data", current($return["data"]));
        $this->assertArrayHasKey("new", current(current($return["data"])["data"]));
        $this->assertArrayHasKey("old", current(current($return["data"])["data"]));
        $requestDuplicate = [
            [
                "id_tmp_cache" => current($return["data"])["id_tmp_cache"],
                "new_household" => current($return["data"])["new_household"],
                "data" => [
                    [
                        "to_delete" => [
                            "given_name" => current(current(current($return["data"])["data"])["new"]["beneficiaries"])["given_name"],
                            "family_name" => current(current(current($return["data"])["data"])["new"]["beneficiaries"])["family_name"]
                        ],
                        "id_old" => current(current($return["data"])["data"])["old"]->getId()
                    ]
                ]
            ]
        ];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $requestDuplicate, 3, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 4, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 5, $token);
        $this->assertSame([], $return['error']);
        $household2 = $this->em->getRepository(Household::class)->findOneByAddressStreet($this->addressStreet2);
        $beneficiariesOfHH2 = $this->em->getRepository(Beneficiary::class)->findByHousehold($household2);
        $this->assertSame(1, count($beneficiariesOfHH2));


        // TRY TO ADD CSV WITH DUPLICATE ERROR => KEEP THE DUPLICATE IN THE CSV
        $this->SHEET_ARRAY[6] = [
            "A" => $this->addressStreet3,
            "B" => 3,
            "C" => 3,
            "D" => 3,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "Rhone-Alpes",
            "I" => "Savoie",
            "J" => "Chambery",
            "K" => "Sainte Hélène sur Isère",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FIRSTNAME44444444 UNIT_TEST",
            "O" => "FNAME44444444444444 UNIT_TEST",
            "P" => 0,
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ];
        $this->SHEET_ARRAY[7] = [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNAME2 TEST_IMPORT",
            "O" => "NAME2 TEST_IMPORT",
            "P" => 1,
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $this->SHEET_ARRAY, 1, null);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $token = $return["token"];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 2, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertArrayHasKey("token", $return);
        $this->assertArrayHasKey("new_household", current($return["data"]));
        $this->assertArrayHasKey("data", current($return["data"]));
        $this->assertArrayHasKey("new", current(current($return["data"])["data"]));
        $this->assertArrayHasKey("old", current(current($return["data"])["data"]));
        $requestDuplicate = [
            [
                "id_tmp_cache" => current($return["data"])["id_tmp_cache"],
                "new_household" => current($return["data"])["new_household"],
                "data" => [
                    [
                        "state" => true,
                        "new" => current(current($return["data"])["data"])["new"],
                        "id_old" => current(current($return["data"])["data"])["old"]->getId()
                    ]
                ]
            ]
        ];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $requestDuplicate, 3, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 4, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 5, $token);
        $this->assertSame([], $return['error']);
        $household3 = $this->em->getRepository(Household::class)->findOneByAddressStreet($this->addressStreet3);
        $beneficiariesOfHH3 = $this->em->getRepository(Beneficiary::class)->findByHousehold($household3);
        $this->assertSame(2, count($beneficiariesOfHH3));


        // TRY TO ADD CSV WITH DUPLICATE ERROR (=> REMOVE THE DUPLICATE IN THE DB) AND MORE ERROR (=> ADD IT TO THE DATABASE)
        $this->SHEET_ARRAY[8] = [
            "A" => $this->addressStreet4,
            "B" => 4,
            "C" => 4,
            "D" => 4,
            "E" => "this is just some notes",
            "F" => 1.1544,
            "G" => 120.12,
            "H" => "Rhone-Alpes",
            "I" => "Savoie",
            "J" => "Chambery",
            "K" => "Sainte Hélène sur Isère",
            "L" => 4.0,
            "M" => "my wash",
            "N" => "FI5 UNIT_TEST",
            "O" => "FNA5 UNIT_TEST",
            "P" => 0,
            "Q" => 1,
            "R" => "1995-04-25",
            "S" => "lactating ; single family",
            "T" => "Type1 - 1",
            "U" => "card-152a",
        ];
        $this->SHEET_ARRAY[9] = [
            "A" => null,
            "B" => null,
            "C" => null,
            "D" => null,
            "E" => null,
            "F" => null,
            "G" => null,
            "H" => null,
            "I" => null,
            "J" => null,
            "K" => null,
            "L" => null,
            "M" => null,
            "N" => "FIRSTNAME2 TEST_IMPORT",
            "O" => "NAME2 TEST_IMPORT",
            "P" => 1,
            "Q" => 0,
            "R" => "1995-04-25",
            "S" => "lactating",
            "T" => "Type1 - 2",
            "U" => "id-45f",
        ];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $this->SHEET_ARRAY, 1, null);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $token = $return["token"];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 2, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertArrayHasKey("token", $return);
        $this->assertArrayHasKey("new_household", current($return["data"]));
        $this->assertArrayHasKey("data", current($return["data"]));
        $this->assertArrayHasKey("new", current(current($return["data"])["data"]));
        $this->assertArrayHasKey("old", current(current($return["data"])["data"]));
        $requestDuplicate = [
            [
                "id_tmp_cache" => current($return["data"])["id_tmp_cache"],
                "new_household" => current($return["data"])["new_household"],
                "data" => [
                    [
                        "state" => false,
                        "new" => current(current($return["data"])["data"])["new"],
                        "id_old" => current(current(current(current($return["data"])["data"])["old"]->getBeneficiaries()))->getId()
                    ]
                ]
            ]
        ];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $requestDuplicate, 3, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertArrayHasKey("token", $return);
        $this->assertArrayHasKey("new", current($return["data"]));
        $this->assertArrayHasKey("old", current($return["data"]));
        $requestMore = [
            [
                "id_old" => current($return["data"])['old']->getId(),
                "data" => [
                    current($return["data"])['new']["beneficiaries"][1]
                ]
            ]
        ];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $requestMore, 4, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 5, $token);
        $this->assertSame([], $return['error']);
        $household4 = $this->em->getRepository(Household::class)->findOneByAddressStreet($this->addressStreet4);
        $beneficiariesOfHH4 = $this->em->getRepository(Beneficiary::class)->findByHousehold($household4);
        $this->assertSame(2, count($beneficiariesOfHH4));
        $household1 = $this->em->getRepository(Household::class)->findOneByAddressStreet($this->addressStreet);
        $beneficiariesOfHH1 = $this->em->getRepository(Beneficiary::class)->findByHousehold($household1);
        $this->assertSame(2, count($beneficiariesOfHH1));


        // TRY TO ADD CSV WITH TYPO ERROR => NEW AND OLD ARE NOT THE SAME
        $body_begin[4]['N'] = "THIS IS JUST A TEST";
        $body_begin[4]['O'] = "THIS IS JUST A TEST2";
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $body_begin, 1, null);
        $token = $return["token"];
        $this->assertArrayHasKey("token", $return);
        $this->assertArrayHasKey("data", $return);
        $this->assertArrayHasKey("new", current($return["data"]));
        $this->assertArrayHasKey("old", current($return["data"]));
        $oldHousehold = current($return["data"])["old"];
        $request = [
            [
                "new" => current($return["data"])["new"],
                "id_tmp_cache" => current($return["data"])["id_tmp_cache"],
                "id_old" => $oldHousehold->getId(),
                "state" => 1
            ]
        ];
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), $request, 2, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 3, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 4, $token);
        $this->assertArrayHasKey("data", $return);
        $this->assertSame([], $return["data"]);
        $this->assertArrayHasKey("token", $return);
        $return = $this->hhCSVService->transformAndAnalyze($this->iso3, current($projects), [], 5, $token);
        $this->assertSame([], $return['error']);
        /** @var Beneficiary $headOldHousehold */
        $headOldHousehold = $this->em->getRepository(Beneficiary::class)->getHeadOfHousehold($oldHousehold);
        $this->assertSame($this->UPDATED_GIVEN_NAME, $headOldHousehold->getGivenName());

        $this->removeHousehold($this->addressStreet);
        $this->removeHousehold($this->addressStreet2);
        $this->removeHousehold($this->addressStreet3);
        $this->removeHousehold($this->addressStreet4);
    }

}