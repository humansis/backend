<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
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
     * Update smartcard, typically its' state.
     *
     * @Rest\Patch("/offline-app/v1/smartcards/{serialNumber}")
     *
     * @param string $serialNumber
     * @param ChangeSmartcardInputType $changeSmartcardInputType
     * @param SmartcardRepository $smartcardRepository
     * @param SmartcardService $smartcardService
     *
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function change(
        string $serialNumber,
        ChangeSmartcardInputType $changeSmartcardInputType,
        SmartcardRepository $smartcardRepository,
        SmartcardService $smartcardService
    ): Response {
        $smartcards = $smartcardRepository->findBy(['serialNumber' => $serialNumber, 'state' => SmartcardStates::ACTIVE]);
        $doubledRequest = count($smartcards) === 0;

        foreach ($smartcards as $smartcard) {
            try {
                $smartcardService->change($smartcard, $changeSmartcardInputType);
            } catch (SmartcardActivationDeactivatedException|SmartcardNotAllowedStateTransition $e) {
                $doubledRequest = true;
            }
        }

        if ($doubledRequest) {
            return Response::create('', Response::HTTP_ACCEPTED);
        } else {
            return Response::create();
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
