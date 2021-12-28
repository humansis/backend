<?php declare(strict_types=1);

namespace Tests\NewApiBundle\Controller\OfflineApp;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Tests\NewApiBundle\Helper\AbstractFunctionalApiTest;
use UserBundle\Entity\User;

class AuthControllerTest extends AbstractFunctionalApiTest
{
    private const PASSWORD = 'pin1234';
    private const USER = 'test-no-vendor@test.org';

    /**
     * @return User
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function getUser(): User
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $user = $this->getTestUser(self::USER);
        if(is_null($user)){
            $user = new User();
            $user->setUsername('test-no-vendor@test.org');
            $user->setUsernameCanonical('test-no-vendor@test.org');
            $user->setEmail('test-no-vendor@test.org');
            $user->setEmailCanonical('test-no-vendor@test.org');
            $user->setSalt('fhn91jwIbBnFAgZjQZA3mE4XUrjYzWfOoZDcjt/9');
            $user->setPassword('WvbKrt5YeWcDtzWg4C8uUW9a3pmHi6SkXvnvvCisIbNQqUVtaTm8Myv/Hst1IEUDv3NtrqyUDC4BygbjQ/zePw==');
            $user->setEnabled(true);
            $em->persist($user);
            $em->flush();
        }

        return $user;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function tearDown()
    {
        /** @var EntityManagerInterface $em */
        $em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $em->remove($this->getTestUser(self::USER));
        $em->flush();
        parent::tearDown();
    }

    public function testOfflineAppLogin(): void
    {
        $this->markTestSkipped('Support for JWT in test environment needs to be done first');

        $body = [
            'username' => self::USER,
            'password' => self::PASSWORD,
        ];

        $this->client->request('POST', '/api/jwt/offline-app/v2/login', [], [], [], json_encode($body));

        $responseBody = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($this->client->getResponse()->isSuccessful(), "Request failed: ".$this->client->getResponse()->getContent());
        $this->assertTrue(gettype($responseBody) == 'array');
        $this->assertArrayHasKey('id', $responseBody);
        $this->assertArrayHasKey('username', $responseBody);
        $this->assertArrayHasKey('token', $responseBody);
        $this->assertArrayHasKey('email', $responseBody);
        $this->assertArrayHasKey('changePassword', $responseBody);
        $this->assertArrayHasKey('availableCountries', $responseBody);
    }
}
