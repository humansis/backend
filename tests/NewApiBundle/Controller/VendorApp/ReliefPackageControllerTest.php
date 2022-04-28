<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\VendorApp;

use DistributionBundle\Entity\Assistance;
use Exception;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use Tests\BMSServiceTestCase;
use VoucherBundle\Entity\Vendor;

class ReliefPackageControllerTest extends BMSServiceTestCase
{
    /**
     * @throws Exception
     */
    public function setUp()
    {
        // Configuration of BMSServiceTest
        $this->setDefaultSerializerName('serializer');
        parent::setUpFunctionnal();

        // Get a Client instance for simulate a browser
        $this->client = self::$container->get('test.client');
    }

    public function testListReliefPackagesSimple()
    {
        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([], ['id' => 'asc']);

        $originalLocation = $vendor->getLocation();

        $reliefPackage = $this->em->getRepository(ReliefPackage::class)->findOneBy([], ['id' => 'asc']);
        $reliefPackage->setAmountDistributed("0.00");

        /** @var Assistance $assitance */
        $assistance = $reliefPackage->getAssistanceBeneficiary()->getAssistance();
        $assistance->setRemoteDistributionAllowed(true);

        $vendor->setLocation($reliefPackage->getAssistanceBeneficiary()->getAssistance()->getLocation());

        $this->em->flush();

        $this->request('GET', "/api/basic/vendor-app/v1/vendors/{$vendor->getId()}/relief-packages");

        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            'Request failed: '.$this->client->getResponse()->getContent()
        );

        $this->assertJsonFragment('{
            "totalCount": "*",
            "data": [
                {
                    "id": "*",
                    "assistanceId": "*",
                    "beneficiaryId": "*",
                    "amountToDistribute": "*",
                    "unit": "*",
                    "smartCardSerialNumber": "*",
                    "foodLimit": "*",
                    "nonfoodLimit": "*",
                    "cashbackLimit": "*",
                    "expirationDate": "*"
                }
            ]
        }', $this->client->getResponse()->getContent());

        $vendor->setLocation($originalLocation);
        $this->em->flush();
    }
}
