<?php

namespace Listener;

use Entity\Household;
use Entity\HouseholdActivity;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Entity\User;

class HouseholdActivitySubscriber implements EventSubscriber
{
    /**
     * Constuctor.
     */
    public function __construct(private readonly TokenStorageInterface $tokenStorage, private readonly EntityManagerInterface $em, private readonly SerializerInterface $serializer)
    {
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

        $json = $this->serializer->serialize(
            $household,
            'json',
            ['groups' => ["Activity"], 'datetime_format' => 'd-m-Y']
        );

        $activity = new HouseholdActivity($household, $user, $json);
        $this->em->persist($activity);
        $this->em->flush();
    }
}
