<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\VendorApp;

use DistributionBundle\Entity\Assistance;
use Exception;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\ReliefPackageState;
use Tests\BMSServiceTestCase;
use UserBundle\Entity\User;
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
        $reliefPackage = $this->em->getRepository(ReliefPackage::class)->findOneBy([], ['id' => 'asc']);
        $reliefPackage->setAmountDistributed("0.00");
        $reliefPackage->setState(ReliefPackageState::TO_DISTRIBUTE);

        /** @var Assistance $assistance */
        $assistance = $reliefPackage->getAssistanceBeneficiary()->getAssistance();
        $assistance->setRemoteDistributionAllowed(true);
        $assistance->setDateExpiration(null);

        $user = new User();
        $username = __METHOD__.random_int(100, 10000);
        $user->setUsername($username)
            ->setUsernameCanonical($username)
            ->setEmail($username)
            ->setEmailCanonical($username)
            ->setEnabled(true)
            ->setSalt('')
            ->setPassword('');

        $vendor = new Vendor();
        $vendor
            ->setName($username)
            ->setShop('shop')
            ->setAddressNumber('13')
            ->setAddressStreet('Main street')
            ->setAddressPostcode('12345')
            ->setArchived(false)
            ->setUser($user)
            ->setVendorNo('SYR'.sprintf('%07d', random_int(100, 10000)))
            ->setContractNo('SYRSP'.sprintf('%06d', random_int(100, 10000)))
        ;
        $vendor->setLocation($reliefPackage->getAssistanceBeneficiary()->getAssistance()->getLocation());
        $vendor->setCanSellCashback(true);
        $vendor->setCanSellNonFood(true);
        $vendor->setCanSellCashback(true);
        $vendor->setCanDoRemoteDistributions(true);

        $this->em->persist($assistance);
        $this->em->persist($user);
        $this->em->persist($vendor);
        $this->em->flush();
        $this->em->refresh($vendor);

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

        $this->em->flush();
    }
}
