<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\VendorApp;

use DistributionBundle\Entity\Assistance;
use Doctrine\ORM\EntityManagerInterface;
use NewApiBundle\Entity\ReliefPackage;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;
use VoucherBundle\Entity\Vendor;

class ReliefPackageControllerTest extends AbstractFunctionalApiTest
{
    public function testListReliefPackagesSimple()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $vendor = $em->getRepository(Vendor::class)->findOneBy([], ['id' => 'asc']);

        $originalLocation = $vendor->getLocation();

        $reliefPackage = $em->getRepository(ReliefPackage::class)->findOneBy([], ['id' => 'asc']);

        /** @var Assistance $assitance */
        $assistance = $reliefPackage->getAssistanceBeneficiary()->getAssistance();
        $assistance->setRemoteDistributionAllowed(true);

        $vendor->setLocation($reliefPackage->getAssistanceBeneficiary()->getAssistance()->getLocation());

        $em->flush();

        $this->client->request('GET', "/api/basic/vendor-app/v1/vendors/{$vendor->getId()}/relief-packages", [], [], $this->addAuth());

        $this->assertResponseIsSuccessful('Request was\'t successful: '.$this->client->getResponse()->getContent());

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
        $em->flush();
    }
}
