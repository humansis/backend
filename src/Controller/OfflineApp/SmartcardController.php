<?php

declare(strict_types=1);

namespace Controller\OfflineApp;

use Entity\SmartcardBeneficiary;
use Enum\SmartcardStates;
use FOS\RestBundle\Controller\Annotations as Rest;
use Component\Smartcard\Exception\SmartcardActivationDeactivatedException;
use Component\Smartcard\Exception\SmartcardDoubledRegistrationException;
use Component\Smartcard\Exception\SmartcardNotAllowedStateTransition;
use InputType\Smartcard\ChangeSmartcardInputType;
use InputType\Smartcard\SmartcardRegisterInputType;
use Repository\SmartcardBeneficiaryRepository;
use Symfony\Component\HttpFoundation\Response;
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
        SmartcardBeneficiaryRepository $smartcardBeneficiaryRepository,
        SmartcardService $smartcardService
    ): Response {
        $smartcardBeneficiaries = $smartcardBeneficiaryRepository->findBy(
            ['serialNumber' => $serialNumber, 'state' => SmartcardStates::ACTIVE]
        );
        $doubledRequest = count($smartcardBeneficiaries) === 0;

        foreach ($smartcardBeneficiaries as $smartcardBeneficiary) {
            try {
                $smartcardService->change($smartcardBeneficiary, $changeSmartcardInputType);
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
    #[ParamConverter('smartcardBeneficiary')]
    public function info(SmartcardBeneficiary $smartcardBeneficiary): Response
    {
        $json = $this->serializer->serialize($smartcardBeneficiary, 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }
}
