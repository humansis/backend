<?php

namespace Listener;

use Entity\Logs;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

use function is_object;

class ResponseListener
{
    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ContainerInterface $container */
    private $container;

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->em = $entityManager;
        $this->container = $container;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();
        $user = $this->getUser();

        //Uid
        $idUser = $user['id'];
        //Umail
        $mailUser = $user['email'];
        //Controller
        $controller = $request->attributes->get('_controller');
        //url
        $url = $request->getPathInfo();
        //Date
        $date = new DateTime('now');
        //Method
        $method = $request->getMethod();
        //HTTPStatus
        $httpStatus = $response->getStatusCode();
        //Request
        $requestAll = $request->request->all();

        //do not log any login requests
        //TODO this is nasty... whole mechanism of logging to "log" table should be rewritten or abandoned
        if (str_contains($url, 'login')) {
            $requestAll = '';
        }

        //Fake POST urls
        $isFakePost = preg_match('/.*\/households\/get\/.*/', $url) ||
            preg_match('/.*\/export/', $url) ||
            preg_match('/.*\/location\/.+/', $url) ||
            preg_match('/.*\/distributions\/criteria\/project\/\d+\/number/', $url) ||
            preg_match('/.*\/distributions\/beneficiaries\/project\/\d+/', $url) ||
            preg_match('/.*\/indicators/', $url) ||
            preg_match('/.*\/login.+/', $url) ||
            preg_match('/.*\/booklets-print/', $url) ||
            // Unused until the App is fixed to not send a request each time it syncs;
            preg_match('/.*\/vouchers\/scanned/', $url) ||
            preg_match('/.*\/deactivate-booklets/', $url);

        if ($idUser && $method != 'GET' && explode('\\', $controller)[0] != "ReportingBundle" && (!$isFakePost || $method !== 'POST')) {
            $log = new Logs();

            $log->setUrl($url)
                ->setIdUser($idUser)
                ->setMailUser($mailUser)
                ->setMethod($method)
                ->setDate($date)
                ->setHttpStatus($httpStatus)
                ->setController($controller)
                ->setRequest(json_encode($requestAll));

            if (!$this->em->isOpen()) {
                $this->em = $this->em->create(
                    $this->em->getConnection(),
                    $this->em->getConfiguration()
                );
            }

            $this->em->persist($log);
            $this->em->flush();
        }
    }

    /**
     * Get the user
     */
    protected function getUser()
    {
        if (!$this->container->has('security.token_storage')) {
            throw new LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return;
        }

        return ['id' => $user->getId(), 'email' => $user->getEmail()];
    }
}
