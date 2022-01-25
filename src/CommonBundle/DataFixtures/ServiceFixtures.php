<?php

namespace CommonBundle\DataFixtures;

use CommonBundle\Entity\Organization;
use CommonBundle\Entity\OrganizationServices;
use CommonBundle\Entity\Service;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use FOS\UserBundle\Doctrine\UserManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class ServiceFixtures extends Fixture implements DependentFixtureInterface
{

    /** @var UserManager $manager */
    private $manager;

    /** @var EncoderFactoryInterface $encoderFactory */
    private $encoderFactory;

    public function __construct(UserManager $manager, EncoderFactoryInterface $encoderFactory)
    {
        $this->manager = $manager;
        $this->encoderFactory = $encoderFactory;
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixtures::class,
        ];
    }

    private $data = [
        [
            "name" => "Two-Factor Authentication",
            "parameters" => [
                '$id' => "2fa",
                "type" => "object",
                "title" => "Two-Factor Authentication",
                '$schema' => "http =>//json-schema.org/draft-07/schema#",
                "properties" => [
                    "token" => [
                        "type" => "string",
                        "description" => "The token for the SMS service"
                    ]
                ]
            ],
            "country" => null
        ],
        [
            "name" => "IDPoor API",
            "parameters" => [
                '$id' => "idpoor",
                "type" => "object",
                "title" => "IDPoor",
                '$schema' => "http://json-schema.org/draft-07/schema#",
                "properties" => [
                    "email" => [
                        "type" => "string",
                        "format" => "email",
                        "description" => "The email used for the IDPoor"
                    ],
                    "token" => [
                        "type" => "string",
                        "description" => "The token for the IDPoor"
                    ]
                ]
            ],
            "country" => "KHM"
        ],
        [
            "name" => "WING Cash Transfer",
            "parameters" => [
                '$id' => "wing",
                "type" => "object",
                "title" => "WING Cash Transfer",
                '$schema' => "http://json-schema.org/draft-07/schema#",
                "properties" => [
                    "password" => [
                        "type" => "string",
                        "format" => "password",
                        "description" => "The password for the WING API"
                    ],
                    "username" => [
                        "type" => "string",
                        "description" => "The username for the WING API"
                    ],
                    "production" => [
                        "type" => "boolean",
                        "description" => "Whether the service should use the production API or not"
                    ]
                ]
            ],
            "country" => "KHM"
        ]
    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum) {
            $service = $manager->getRepository(Service::class)->findOneBy(["name" => $datum["name"]], ['id' => 'asc']);
            if (!$service instanceof Service) {
                $service = new Service();
                $service->setName($datum["name"])
                         ->setParameters($datum["parameters"])
                         ->setCountry($datum["country"]);

                $manager->persist($service);
                $manager->flush();
            }

            $organizationService = $manager->getRepository(OrganizationServices::class)->findOneByService($datum["name"]);
            $organization = $manager->getRepository(Organization::class)->find(1);
            if ((!$organizationService instanceof OrganizationServices) && ($organization instanceof Organization)) {
                $organizationService = new OrganizationServices();

                $parameters = [];
                foreach ($service->getParameters()["properties"] as $parameter => $data) {
                    $parameters[$parameter] = null;
                }

                $organizationService->setOrganization($organization)
                                    ->setService($service)
                                    ->setEnabled(false)
                                    ->setParametersValue($parameters);
                
                $manager->persist($organizationService);
                $manager->flush();
            }
        }
    }
}
