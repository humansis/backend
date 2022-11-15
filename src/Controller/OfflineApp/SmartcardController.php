<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Enum\SmartcardStates;
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
     * @param SmartcardRegisterInputType $registerInputType
     * @param SmartcardService $smartcardService
     *
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function register(
        SmartcardRegisterInputType $registerInputType,
        SmartcardService $smartcardService
    ): Response {
        try {
            $smartcardService->register($registerInputType);

            return Response::create();
        } catch (SmartcardDoubledRegistrationException $e) {
            return Response::create('', Response::HTTP_ACCEPTED);
        }
    }

    /**
     * Deactivate Smartcard
     *
     * @Rest\Patch("/offline-app/v1/smartcards/{serialNumber}")
     * @param string $serialNumber
     * @param ChangeSmartcardInputType $changeSmartcardInputType
     * @param SmartcardRepository $smartcardRepository
     * @param SmartcardService $smartcardService
     *
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @deprecated This endpoint is only used for card deactivation, but it´s done automatically during assign.
     *
     */
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
     *
     * @Rest\Get("/offline-app/v1/smartcards/{serialNumber}")
     * @ParamConverter("smartcard")
     *
     * @param Smartcard $smartcard
     * @param Request $request
     *
     * @return Response
     */
    public function info(Smartcard $smartcard, Request $request): Response
    {
        $json = $this->serializer->serialize($smartcard, 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }
}
