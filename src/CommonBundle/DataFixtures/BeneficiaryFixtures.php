<?php


namespace CommonBundle\DataFixtures;

use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\Kernel;

class BeneficiaryFixtures extends Fixture
{
    private $householdArray = [
        [
          "livelihood" => "1",
          "income_level" => 3,
          "notes" => null,
          "latitude" => null,
          "longitude" => null,
          "coping_strategies_index" => "2",
          "food_consumption_score" => "3",
          "household_locations" => array(
              [
                "location_group" => "current",
                "type" => "residence",
                "address" =>  [
                    "street" => "azerrt",
                    "number" => "1",
                    "postcode" => "12345",
                    "location" => [
                        "adm1" => 1,
                        "adm2" => 1,
                        "adm3" => 1,
                        "adm4" => null,
                        "country_iso3" => "KHM",
                    ],
                ]
              ]
            ),
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
                  "en_given_name" => "Test",
                  "en_family_name" => "Tester",
                  "local_given_name" => "Test",
                  "local_family_name" => "Tester",
                  "gender" => "0",
                  "status" => "1",
                  "residency_status" => "resident",
                  "date_of_birth" => "10-10-1996",
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
              ],
              [
                  "en_given_name" => "Test2",
                  "en_family_name" => "Tester",
                  "local_given_name" => "Test2",
                  "local_family_name" => "Tester",
                  "gender" => "1",
                  "status" => "0",
                  "residency_status" => "IDP",
                  "date_of_birth" => "10-11-1996",
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
              ],
              [
                  "en_given_name" => "Test4",
                  "en_family_name" => "Tester",
                  "local_given_name" => "Test4",
                  "local_family_name" => "Tester",
                  "gender" => "1",
                  "status" => "0",
                  "residency_status" => "refugee",
                  "date_of_birth" => "10-12-1995",
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
              ],
              [
                  "en_given_name" => "Test5",
                  "en_family_name" => "Tester",
                  "local_given_name" => "Test5",
                  "local_family_name" => "Tester",
                  "gender" => "1",
                  "status" => "0",
                  "residency_status" => "resident",
                  "date_of_birth" => "14-10-2000",
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
              ],

          ],
          "__country" => "KHM"
        ],
        [
            "livelihood" => "1",
            "income_level" => 4,
            "notes" => null,
            "latitude" => null,
            "longitude" => null,
            "coping_strategies_index" => "4",
            "food_consumption_score" => "5",
            "household_locations" => array(
                [
                  "location_group" => "current",
                  "type" => "residence",
                  "address" =>  [
                      "street" => "azerrt",
                      "number" => "2",
                      "postcode" => "12346",
                      "location" => [
                          "adm1" => 1,
                          "adm2" => 1,
                          "adm3" => 1,
                          "adm4" => null,
                          "country_iso3" => "KHM",
                      ],
                  ]
                ]
              ),
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
                    "en_given_name" => "Test6",
                    "en_family_name" => "Bis",
                    "local_given_name" => "Test6",
                    "local_family_name" => "Bis",
                    "gender" => "1",
                    "status" => "1",
                    "residency_status" => "resident",
                    "date_of_birth" => "14-10-1995",
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
                ],
                [
                    "en_given_name" => "Test7",
                    "en_family_name" => "Bis",
                    "local_given_name" => "Test7",
                    "local_family_name" => "Bis",
                    "gender" => "1",
                    "status" => "0",
                    "residency_status" => "resident",
                    "date_of_birth" => "15-10-1989",
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
                ],
                [
                    "en_given_name" => "Test8",
                    "en_family_name" => "Bis",
                    "local_given_name" => "Test8",
                    "local_family_name" => "Bis",
                    "gender" => "1",
                    "status" => "0",
                    "residency_status" => "resident",
                    "date_of_birth" => "15-10-1990",
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
                ],
                [
                    "en_given_name" => "Test9",
                    "en_family_name" => "Bis",
                    "local_given_name" => "Test9",
                    "local_family_name" => "Bis",
                    "gender" => "1",
                    "status" => "0",
                    "residency_status" => "resident",
                    "date_of_birth" => "15-08-1989",
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
                ],

            ],
            "__country" => "KHM"
        ],
    ];

    private $householdService;
    
    private $kernel;

    public function __construct(Kernel $kernel, HouseholdService $householdService)
    {
        $this->householdService = $householdService;
        $this->kernel = $kernel;
    }


    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        if ($this->kernel->getEnvironment() !== "prod") {
            $projects = $manager->getRepository(Project::class)->findAll();
            foreach ($this->householdArray as $household) {
                $this->householdService->create($household, $projects);
            }
        }
    }
}
