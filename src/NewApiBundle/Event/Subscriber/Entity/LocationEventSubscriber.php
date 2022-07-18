<?php

namespace NewApiBundle\Event\Subscriber\Entity;

use CommonBundle\Entity\Location;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use NewApiBundle\InputType\LocationFilterInputType;
use Doctrine\Common\EventSubscriber;

class LocationEventSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postRemove,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->updateDuplicities($args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->updateDuplicities($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->updateDuplicities($args);
    }

    private function updateDuplicities(LifecycleEventArgs $args): void
    {
        /** @var Location $entity */
        $location = $args->getObject();
        
        if (!($location instanceof Location)) {
            return;
        }
        $repository = $args->getObjectManager()->getRepository(Location::class);
        
        $filter = new LocationFilterInputType();
        $filter->setFilter([
            'level' => $location->getLvl(),
            'enumNormalizedName' => $location->getEnumNormalizedName(),
        ]);

        $locations = $repository->findByParams($filter, $location->getCountryISO3());
        $duplicityCount = $locations->count() - 1;

        $r = $repository->updateDuplicityCount(
            $location->getLvl(),
            $location->getCountryISO3(),
            $location->getEnumNormalizedName(),
            $duplicityCount
        );
    }
}