<?php


namespace CommonBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Doctrine\UserManager;
use ProjectBundle\Entity\Project;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use UserBundle\Entity\User;
use UserBundle\Entity\UserCountry;
use UserBundle\Entity\UserProject;


class UserFixtures extends Fixture
{
    /** @var UserManager $manager */
    private $manager;

    /** @var EncoderFactoryInterface $encoderFactory */
    private $encoderFactory;

    public function __construct(UserManager $manager, EncoderFactoryInterface $encoderFactory)
    {
        $this->manager = $manager;
        $this->encoderFactory = $encoderFactory;
    }

    private $data = [
        ['tester', 'tester']
    ];

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $datum)
        {
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
                $userCountry = new UserCountry();
                $userCountry->setUser($instance)
                    ->setIso3("KHM")
                    ->setRights(1);
                $manager->persist($userCountry);
                $project = $manager->getRepository(Project::class)->findOneByName("Dev Project");
                if ($project instanceof Project)
                {
                    $userProject = new UserProject();
                    $userProject->setRights(1)
                        ->setUser($instance)
                        ->setProject($project);
                    $manager->persist($userProject);
                }
                $manager->flush();
            }
        }
    }
}