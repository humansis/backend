<?php

namespace NewApiBundle\Listener;

use NewApiBundle\Entity\Household;
use NewApiBundle\Entity\HouseholdActivity;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;
use NewApiBundle\Entity\User;

class HouseholdActivitySubscriber implements EventSubscriber
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var EntityManagerInterface */
    private $em;

    /** @var SerializerInterface */
    private $serializer;

    /**
     * Constuctor.
     *
     * @param TokenStorageInterface  $tokenStorage
     * @param EntityManagerInterface $em
     * @param SerializerInterface    $serializer
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $em,
        SerializerInterface $serializer
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
        $this->serializer = $serializer;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $this->handle($args);
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->handle($args);
    }

    protected function handle(LifecycleEventArgs $args)
    {
        if (!$args->getObject() instanceof Household) {
            return;
        }

        /** @var User|null $user */
        $user = null;
        if ($this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();
        }

        /** @var Household $household */
        $household = $args->getObject();

        $json = $this->serializer->serialize($household, 'json', ['groups' => ["Activity"], 'datetime_format' => 'd-m-Y']);

        $activity = new HouseholdActivity($household, $user, $json);
        $this->em->persist($activity);
        $this->em->flush();

    }
}
