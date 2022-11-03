<?php

namespace Listener;

use Exception;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AcceptHeaderEventSubscriber implements \Symfony\Component\EventDispatcher\EventSubscriberInterface
{
    /**
     * @param string[] $locales
     */
    public function __construct(private readonly array $locales, private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @throws Exception
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (HttpKernelInterface::SUB_REQUEST === $event->getRequestType()) {
            return;
        }

        $languages = $event->getRequest()->getLanguages();
        foreach ($languages as $locale) {
            if (in_array($locale, $this->locales)) {
                $event->getRequest()->setLocale($locale);
                $this->translator->setLocale($locale);
                break;
            }
        }
    }
    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [\Symfony\Component\HttpKernel\KernelEvents::REQUEST => ''];
    }
}
