<?php

namespace Tests\DistributionBundle\Controller;

use Tests\BMSServiceTestCase;
use ProjectBundle\Entity\Project;
use BeneficiaryBundle\Entity\Beneficiary;

class DistributionServiceTest extends BMSServiceTestCase
{
    /** @var LocationService $locationService */
    private $locationService;

    public function setUp()
    {
        $this->setDefaultSerializerName('jms_serializer');
        parent::setUpFunctionnal();
    }

    /**
     * Test used to check if the data returned by the function "getAllBeneficiariesInProject()" is a type of Beneficiary entity.
     */
    public function testSaveCSV()
    {
        $distributionService = $this->container->get('distribution.distribution_csv_service');
    }

    public function testGetAllBeneficiariesInProject()
    {
        $distributionService = $this->container->get('distribution.distribution_service');
        /**
         * Dev Project comes from Fixtures.
         */
        $project = $this->em->getRepository(Project::class)->findOneByName('Dev Project');

        $allBeneficiariesInProject = $distributionService->getAllBeneficiariesInProject($project);

        $beneficiary = new Beneficiary();

        for ($i = 0; $i < count($allBeneficiariesInProject); ++$i) {
            $this->assertTrue($allBeneficiariesInProject[$i] instanceof $beneficiary);
        }
    }
}
