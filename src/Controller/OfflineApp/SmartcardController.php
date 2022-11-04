<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Smartcard\Exception\SmartcardActivationDeactivatedException;
use Component\Smartcard\Exception\SmartcardDoubledRegistrationException;
use Component\Smartcard\Exception\SmartcardNotAllowedStateTransition;
use InputType\Smartcard\ChangeSmartcardInputType;
use InputType\Smartcard\SmartcardRegisterInputType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Entity\Smartcard;
use Repository\SmartcardRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Utils\SmartcardService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class SmartcardController extends AbstractOfflineAppController
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    /**
     * @Rest\Post("/offline-app/v1/smartcards")
     *
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function register(
        SmartcardRegisterInputType $registerInputType,
        SmartcardService $smartcardService
    ): Response {
        try {
            $smartcardService->register($registerInputType);

            return new Response();
        } catch (SmartcardDoubledRegistrationException) {
            return new Response('', Response::HTTP_ACCEPTED);
        }
    }

    /**
     * Update smartcard, typically its' state.
     *
     * @Rest\Patch("/offline-app/v1/smartcards/{serialNumber}")
     *
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function change(
        string $serialNumber,
        ChangeSmartcardInputType $changeSmartcardInputType,
        SmartcardRepository $smartcardRepository,
        SmartcardService $smartcardService
    ): Response {
        $smartcard = $smartcardRepository->findOneBy(['serialNumber' => $serialNumber]);
        try {
            $smartcardService->change($smartcard, $changeSmartcardInputType);

            return new Response();
        } catch (SmartcardActivationDeactivatedException | SmartcardNotAllowedStateTransition) {
            return new Response('', Response::HTTP_ACCEPTED);
        }
    }

    /**
     * Info about smartcard.
     *
     * @Rest\Get("/offline-app/v1/smartcards/{serialNumber}")
     * @ParamConverter("smartcard")
     *
     *
     */
    public function info(Smartcard $smartcard, Request $request): Response
    {
        $json = $this->serializer->serialize($smartcard, 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }
}
