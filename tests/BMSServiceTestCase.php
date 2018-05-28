<?php


namespace Tests;


use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Serializer\SerializerInterface;
use UserBundle\Entity\User;
use UserBundle\Security\Authentication\Token\WsseUserToken;


class BMSServiceTestCase extends KernelTestCase
{

    const USER_PHPUNIT = 'phpunit';
    const USER_TESTER = 'tester';

    // SERVICES

    /** @var EntityManager $em */
    protected $em;

    /** @var Container $container */
    protected $container;

    /** @var $serializer */
    protected $serializer;


    protected $tokenStorage;

    /**
     * @var $defaultSeralizerName
     * If you plan to use another serializer, use the setter before calling this setUp Method in the child class setUp method.
     * Ex :
     * function setUp(){
     *      $this->setDefaultSerializerName("jms_serializer");
     *      parent::setUp();
     * }
     */
    private $defaultSerializerName = "serializer";


    public function setDefaultSerializerName($serializerName)
    {
        $this->defaultSerializerName = $serializerName;
        return $this;
    }


    public function setUpFunctionnal()
    {

        self::bootKernel();

        $this->container = static::$kernel->getContainer();

        //Preparing the EntityManager
        $this->em = $this->container
            ->get('doctrine')
            ->getManager();

        //Mocking Serializer, Container
        $this->serializer = $this->container
            ->get($this->defaultSerializerName);

        //setting the token_storage
        $this->tokenStorage = $this->container->get('security.token_storage');

    }


    public function setUpUnitTest()
    {
        //EntityManager mocking
        $this->mockEntityManager(['getRepository']);
        //Serializer mocking
        $this->mockSerializer();
        //Container mocking
        $this->mockContainer();

    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        //parent::tearDown();
        if (!empty($this->em))
        {
            //$this->em->close();
            unset($this->em);
            $this->em = null; // avoid memory leaks
        }
    }

    /**
     * Mock the EntityManager with the given functions
     * @param  array $requiredFunctions [names of functions to setup on the mock]
     * @return EntityManager {[MockClass]       [a mock instance of EntityManager]
     */
    protected function mockEntityManager(array $requiredFunctions)
    {
        $this->em = $this->getMockBuilder(EntityManager::class)
            ->setMethods($requiredFunctions)
            ->disableOriginalConstructor()
            ->getMock();
        return $this->em;
    }

    protected function mockSerializer()
    {
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->setMethods(['serialize', 'deserialize'])
            ->disableOriginalConstructor()
            ->getMock();
        return $this->serializer;
    }

    protected function mockRepository($repositoryClass, array $requiredFunctions)
    {
        return $this->getMockBuilder($repositoryClass)
            ->disableOriginalConstructor()
            ->setMethods($requiredFunctions)
            ->getMock();
    }

    protected function mockContainer()
    {
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->method('get')
            ->with($this->defaultSerializerName)
            ->will($this->returnValue($this->serializer));
        return $this->container;
    }

    protected function getUserToken(User $user)
    {
        $token = new WsseUserToken($user->getRoles());
        $token->setUser($user);

        return $token;
    }

    /**
     * Require Functional tests and real Entity Manager
     * @param string $username
     * @return null|object|User {[type] [description]
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function getTestUser(string $username)
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);

        if ($user instanceOf User)
        {
            return $user;
        }

        $user = new User();
        $user->setUsername($username)
            ->setEmail($username)
            ->setPassword("");
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

}