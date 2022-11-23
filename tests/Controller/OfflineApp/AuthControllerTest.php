<?php

declare(strict_types=1);

namespace Tests\Controller\OfflineApp;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Tests\BMSServiceTestCase;
use Entity\User;
use Repository\UserRepository;

class AuthControllerTest extends BMSServiceTestCase
{
    private const PASSWORD = 'pin1234';
    private const USER = 'test-no-vendor@test.org';

    private \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository|\Repository\UserRepository $userRepository;

    private \Entity\User $user;

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function setUp(): void
    {
        // Configuration of BMSServiceTest
        parent::setUpFunctionnal();

        $this->client = self::getContainer()->get('test.client');
        $this->userRepository = $this->em->getRepository(User::class);
        $this->user = $this->getUser();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function getUser(): User
    {
        $user = $this->userRepository->findOneBy(['username' => self::USER]);
        if (is_null($user)) {
            $user = new User();
            $user->setUsername('test-no-vendor@test.org');
            $user->setEmail('test-no-vendor@test.org');
            $user->setSalt('fhn91jwIbBnFAgZjQZA3mE4XUrjYzWfOoZDcjt/9');
            $user->setPassword(
                'WvbKrt5YeWcDtzWg4C8uUW9a3pmHi6SkXvnvvCisIbNQqUVtaTm8Myv/Hst1IEUDv3NtrqyUDC4BygbjQ/zePw=='
            );
            $user->setEnabled(true);
            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function tearDown(): void
    {
        $this->em->remove($this->user);
        $this->em->flush();
        parent::tearDown();
    }

    public function testOfflineAppLogin(): void
    {
        $this->markTestSkipped('Support for JWT in test environment needs to be done first');

        $body = [
            'username' => $this->user->getUsername(),
            'password' => self::PASSWORD,
        ];

        $this->client->request('POST', '/api/jwt/offline-app/v2/login', [], [], [], json_encode($body, JSON_THROW_ON_ERROR));

        $responseBody = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue(
            $this->client->getResponse()->isSuccessful(),
            "Request failed: " . $this->client->getResponse()->getContent()
        );
        $this->assertTrue(gettype($responseBody) == 'array');
        $this->assertArrayHasKey('id', $responseBody);
        $this->assertArrayHasKey('username', $responseBody);
        $this->assertArrayHasKey('token', $responseBody);
        $this->assertArrayHasKey('email', $responseBody);
        $this->assertArrayHasKey('changePassword', $responseBody);
        $this->assertArrayHasKey('availableCountries', $responseBody);
    }
}
