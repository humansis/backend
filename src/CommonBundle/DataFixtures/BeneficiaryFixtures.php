<?php


namespace CommonBundle\DataFixtures;

use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use ProjectBundle\Entity\Project;

class BeneficiaryFixtures extends Fixture
{

    private $householdArray = [
        [
          "address_street" => "azerrt",
          "address_number" => "1",
          "address_postcode" => "12345",
          "livelihood" => "1",
          "notes" => null,
          "latitude" => null,
          "longitude" => null,
          "location" => [
            "adm1" => "Banteay Meanchey",
            "adm2" => "Mongkol Borei",
            "adm3" => "Banteay Neang",
            "adm4" => null,
            "country_iso3" => "KHM",
          ],
          "country_specific_answers" => [
              [
                "answer" => "2",
                "country_specific" => [
                    "id" => 1
                ],
              ],
              [
                  "answer" => null,
                  "country_specific" => [
                      "id" => 2
                  ],
              ]
          ],
          "beneficiaries" => [
              [
                  "given_name" => "Test",
                  "family_name" => "Tester",
                  "gender" => "0",
                  "status" => "1",
                  "date_of_birth" => "1996/10/10",
                  "vulnerability_criteria" => [
                      [
                          "id" => 3
                      ]
                  ],
                  "phones" => [],
                  "national_ids" => [],
                  "profile" => [
                      "photo" => ""
                  ],
                  "updated_on" => "2018-09-12 13:09:06",
              ],
              [
                  "given_name" => "Test2",
                  "family_name" => "Tester",
                  "gender" => "1",
                  "status" => "0",
                  "date_of_birth" => "1996/10/11",
                  "vulnerability_criteria" => [
                      [
                          "id" => 1
                      ]
                  ],
                  "phones" => [],
                  "national_ids" => [],
                  "profile" => [
                      "photo" => ""
                  ],
                  "updated_on" => "2018-09-12 13:09:06"
              ],
              [
                  "given_name" => "Test4",
                  "family_name" => "Tester",
                  "gender" => "1",
                  "status" => "0",
                  "date_of_birth" => "1996/10/12",
                  "vulnerability_criteria" => [
                      [
                          "id" => 1
                      ]
                  ],
                  "phones" => [],
                  "national_ids" => [],
                  "profile" => [
                      "photo" => ""
                  ],
                  "updated_on" => "2018-09-12 13:09:06",
              ],
              [
                  "given_name" => "Test5",
                  "family_name" => "Tester",
                  "gender" => "1",
                  "status" => "0",
                  "date_of_birth" => "1996/10/13",
                  "vulnerability_criteria" => [
                      [
                          "id" => 3
                      ]
                  ],
                  "phones" => [],
                  "national_ids" => [],
                  "profile" => [
                      "photo" => ""
                  ],
                  "updated_on" => "2018-09-12 13:09:06",
              ],

          ],
          "__country" => "KHM"
        ],
        [
            "address_street" => "azerrt",
            "address_number" => "2",
            "address_postcode" => "12346",
            "livelihood" => "1",
            "notes" => null,
            "latitude" => null,
            "longitude" => null,
            "location" => [
                "adm1" => "Banteay Meanchey",
                "adm2" => "Mongkol Borei",
                "adm3" => "Banteay Neang",
                "adm4" => null,
                "country_iso3" => "KHM",
            ],
            "country_specific_answers" => [
                [
                    "answer" => "3",
                    "country_specific" => [
                        "id" => 1
                    ],
                ],
                [
                    "answer" => null,
                    "country_specific" => [
                        "id" => 2
                    ],
                ]
            ],
            "beneficiaries" => [
                [
                    "given_name" => "Test6",
                    "family_name" => "Bis",
                    "gender" => "1",
                    "status" => "1",
                    "date_of_birth" => "1996/10/14",
                    "vulnerability_criteria" => [
                        [
                            "id" => 1
                        ]
                    ],
                    "phones" => [],
                    "national_ids" => [],
                    "profile" => [
                        "photo" => ""
                    ],
                    "updated_on" => "2018-09-12 13:09:06",
                ],
                [
                    "given_name" => "Test7",
                    "family_name" => "Bis",
                    "gender" => "1",
                    "status" => "0",
                    "date_of_birth" => "1996/10/15",
                    "vulnerability_criteria" => [
                        [
                            "id" => 3
                        ]
                    ],
                    "phones" => [],
                    "national_ids" => [],
                    "profile" => [
                        "photo" => ""
                    ],
                    "updated_on" => "2018-09-12 13:09:06"
                ],
                [
                    "given_name" => "Test8",
                    "family_name" => "Bis",
                    "gender" => "1",
                    "status" => "0",
                    "date_of_birth" => "1996/10/16",
                    "vulnerability_criteria" => [
                        [
                            "id" => 1
                        ]
                    ],
                    "phones" => [],
                    "national_ids" => [],
                    "profile" => [
                        "photo" => ""
                    ],
                    "updated_on" => "2018-09-12 13:09:06",
                ],
                [
                    "given_name" => "Test9",
                    "family_name" => "Bis",
                    "gender" => "1",
                    "status" => "0",
                    "date_of_birth" => "1996/10/17",
                    "vulnerability_criteria" => [
                        [
                            "id" => 1
                        ]
                    ],
                    "phones" => [],
                    "national_ids" => [],
                    "profile" => [
                        "photo" => ""
                    ],
                    "updated_on" => "2018-09-12 13:09:06",
                ],

            ],
            "__country" => "KHM"
        ],
    ];

    private $householdService;

    public function __construct(HouseholdService $householdService)
    {
        $this->householdService = $householdService;
    }


    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $project = $manager->getRepository(Project::class)->findAll();
        foreach($this->householdArray as $household){
            $this->householdService->create($household, $project[0]);
        }
    }
}