<?php

namespace Listener;

use Exception;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AcceptHeaderListener
{
    /** @var string[] */
    private $locales;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(array $languages, TranslatorInterface $translator)
    {
        $this->locales = $languages;
        $this->translator = $translator;
    }

    /**
     * @param RequestEvent $event
     *
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
}
