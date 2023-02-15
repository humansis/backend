<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Enum\SmartcardStates;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Smartcard\Exception\SmartcardActivationDeactivatedException;
use Component\Smartcard\Exception\SmartcardDoubledRegistrationException;
use Component\Smartcard\Exception\SmartcardNotAllowedStateTransition;
use InputType\Smartcard\ChangeSmartcardInputType;
use InputType\Smartcard\SmartcardRegisterInputType;
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

    #[Rest\Post('/offline-app/v1/smartcards')]
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
     * @deprecated This endpoint is only used for card deactivation, but it´s done automatically during assign.
     *
     */
    #[Rest\Patch('/offline-app/v1/smartcards/{serialNumber}')]
    public function deactivate(
        string $serialNumber,
        ChangeSmartcardInputType $changeSmartcardInputType,
        SmartcardRepository $smartcardRepository,
        SmartcardService $smartcardService
    ): Response {
        $smartcards = $smartcardRepository->findBy(
            ['serialNumber' => $serialNumber, 'state' => SmartcardStates::ACTIVE]
        );
        $doubledRequest = count($smartcards) === 0;

        foreach ($smartcards as $smartcard) {
            try {
                $smartcardService->change($smartcard, $changeSmartcardInputType);
            } catch (SmartcardActivationDeactivatedException | SmartcardNotAllowedStateTransition) {
                $doubledRequest = true;
            }
        }

        if ($doubledRequest) {
            return new Response('', Response::HTTP_ACCEPTED);
        } else {
            return new Response();
        }
    }

    /**
     * Info about smartcard.
     */
    #[Rest\Get('/offline-app/v1/smartcards/{serialNumber}')]
    #[ParamConverter('smartcard')]
    public function info(Smartcard $smartcard): Response
    {
        $json = $this->serializer->serialize($smartcard, 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }
}
