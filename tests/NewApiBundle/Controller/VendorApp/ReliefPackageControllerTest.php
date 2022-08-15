<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\VendorApp;

use NewApiBundle\Entity\Assistance;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Enum\ReliefPackageState;
use Tests\BMSServiceTestCase;
use NewApiBundle\Entity\User;
use NewApiBundle\Entity\Vendor;
use NewApiBundle\Enum\SmartcardStates;

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
        $location = 'ZMB';
        $reliefPackage = $this->em->getRepository(ReliefPackage::class)
            ->createQueryBuilder('rp')
            ->leftJoin('rp.assistanceBeneficiary', 'ab')
            ->leftJoin('ab.assistance', 'a')
            ->leftJoin('a.location', 'l')
            ->join('ab.beneficiary', 'abstB')
            ->join(Beneficiary::class, 'b', Join::WITH, 'b.id=abstB.id AND b.archived = 0')
            ->join('b.smartcards', 's', Join::WITH, 's.beneficiary=b AND s.state=:smartcardStateActive')
            ->andWhere('l.countryISO3 = :iso3')
            ->andWhere('rp.state != :state')
            ->setParameters([
                'iso3' => $location,
                'smartcardStateActive' => SmartcardStates::ACTIVE,
                'state' => ReliefPackageState::CANCELED,
            ])
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        $reliefPackage->setAmountDistributed("0.00");

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
            ->setVendorNo($location.sprintf('%07d', random_int(100, 10000)))
            ->setContractNo($location.'SP'.sprintf('%06d', random_int(100, 10000)))
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

        $this->request(
            'GET',
            "/api/basic/vendor-app/v1/vendors/{$vendor->getId()}/relief-packages",
            [],
            [],
            [
                'HTTP_COUNTRY' => $location
            ]
        );

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
