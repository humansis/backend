<?php


namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Doctrine\UserManager;
use ProjectBundle\Entity\Project;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\HttpKernel\Kernel;
use UserBundle\Entity\User;
use UserBundle\Entity\UserCountry;
use UserBundle\Entity\UserProject;


class UserFixtures extends Fixture
{

    /** @var Kernel $kernel */
    private $kernel;
    
    /** @var UserManager $manager */
    private $manager;

    /** @var EncoderFactoryInterface $encoderFactory */
    private $encoderFactory;

    public function __construct(UserManager $manager, EncoderFactoryInterface $encoderFactory, Kernel $kernel)
    {
        $this->manager = $manager;
        $this->encoderFactory = $encoderFactory;
        $this->kernel = $kernel;
    }

    private $data = [
        ['tester', 'tester'],
        ['vendor', 'vendor']
    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $index => $datum)
        {
            if ($this->kernel->getEnvironment() === "test" || $index !== 1) {
                $instance = $manager->getRepository(User::class)->findOneByUsername($datum[0]);
                if (!$instance instanceof User)
                {
                    $salt = rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=');
                    
                    $instance = $this->manager->createUser();
                    $instance->setEnabled(1)
                    ->setEmail($datum[0])
                    ->setEmailCanonical($datum[0])
                    ->setUsername($datum[0])
                    ->setUsernameCanonical($datum[0])
                    ->setSalt($salt)
                    ->setRoles(["ROLE_ADMIN"]);
                    $instance->setPassword($this->encoderFactory->getEncoder($instance)->encodePassword($datum[1], $salt));
                    $manager->persist($instance);
                    
                    $manager->flush();
                }
            }
        }
    }
}