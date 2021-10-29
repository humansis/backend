<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\VendorApp;

use Exception;
use NewApiBundle\Entity\ReliefPackage;
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
        $vendor = $this->em->getRepository(Vendor::class)->findOneBy([]);

        $originalLocation = $vendor->getLocation();

        $reliefPackage = $this->em->getRepository(ReliefPackage::class)->findOneBy([]);
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
