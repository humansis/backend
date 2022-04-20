<?php declare(strict_types=1);

namespace NewApiBundle\Controller\OfflineApp;

use FOS\RestBundle\Controller\Annotations as Rest;
use NewApiBundle\InputType\SmartcardDepositFilterInputType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Enum\SmartcardStates;
use VoucherBundle\Mapper\SmartcardMapper;
use VoucherBundle\Repository\SmartcardDepositRepository;
use VoucherBundle\Repository\SmartcardRepository;
use VoucherBundle\Utils\SmartcardService;

class SmartcardDepositController extends AbstractOfflineAppController
{
    /** @var SmartcardService */
    private $smartcardService;

    /**
     * @param SmartcardService $smartcardService
     */
    public function __construct(SmartcardService $smartcardService)
    {
        $this->smartcardService = $smartcardService;
    }

    /**
     * @Rest\Get("/offline-app/v1/smartcard-deposits")
     *
     * @param Request                         $request
     * @param SmartcardDepositFilterInputType $filter
     *
     * @return JsonResponse
     */
    public function list(Request $request, SmartcardDepositFilterInputType $filter): JsonResponse
    {
        /** @var SmartcardDepositRepository $repository */
        $repository = $this->getDoctrine()->getRepository(SmartcardDeposit::class);
        $data = $repository->findByParams($filter);

        $response = $this->json($data);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * @Rest\Get("/offline-app/v1/last-smartcard-deposit/{id}")
     *
     * @param SmartcardDeposit $smartcardDeposit
     * @param Request          $request
     *
     * @return JsonResponse
     */
    public function lastSmartcardDeposit(SmartcardDeposit $smartcardDeposit, Request $request): JsonResponse
    {
        $response = $this->json($smartcardDeposit);
        $response->setEtag(md5($response->getContent()));
        $response->setPublic();
        $response->isNotModified($request);

        return $response;
    }

    /**
     * Put money to smartcard. If smartcard does not exists, it will be created.
     *
     * @Rest\Post("/offline-app/v4/smartcards/{serialNumber}/deposit")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE') or is_granted('ROLE_FIELD_OFFICER') or is_granted('ROLE_ENUMERATOR')")
     *
     * @param Request $request
     *
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function deposit(Request $request): Response
    {
        try {
            $deposit = $this->get('smartcard_service')->depositLegacy(
                $request->get('serialNumber'),
                $request->request->getInt('beneficiaryId'),
                $request->request->getInt('assistanceId'),
                $request->request->get('value'),
                $request->request->get('balanceBefore'),
                \DateTime::createFromFormat('Y-m-d\TH:i:sO', $request->get('createdAt')),
                $this->getUser()
            );
        } catch (NotFoundHttpException $exception) {
            $this->writeData(
                'depositV4',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            // due to PIN-2943 was removed exception propagation
            return new Response();
        } catch (\Exception $exception) {
            $this->writeData(
                'depositV4',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            throw $exception;
        }

        return $this->json($deposit->getSmartcard());
    }

    /**
     * Register smartcard to system and assign to beneficiary.
     *
     * @Rest\Post("/offline-app/v1/smartcards")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE') or is_granted('ROLE_FIELD_OFFICER') or is_granted('ROLE_ENUMERATOR')")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $smartcard = $this->smartcardService->register(
            strtoupper($request->get('serialNumber')),
            $request->get('beneficiaryId'),
            \DateTime::createFromFormat('Y-m-d\TH:i:sO', $request->get('createdAt')));

        $mapper = $this->get(SmartcardMapper::class);

        return $this->json($mapper->toFullArray($smartcard));
    }

    /**
     * Update smartcard, typically its' state.
     *
     * @Rest\Patch("/offline-app/v1/smartcards/{serialNumber}")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE') or is_granted('ROLE_FIELD_OFFICER') or is_granted('ROLE_ENUMERATOR')")
     *
     * @param string              $serialNumber
     * @param Request             $request
     * @param SmartcardRepository $smartcardRepository
     *
     * @return Response
     */
    public function change(string $serialNumber, Request $request, SmartcardRepository $smartcardRepository): Response
    {
        $smartcard = $smartcardRepository->findActiveBySerialNumber($serialNumber);

        if (!$smartcard instanceof Smartcard) {
            throw $this->createNotFoundException("Smartcard with code '$serialNumber' was not found.");
        }

        $newState = $smartcard->getState();
        if ($request->request->has('state')) {
            $newState = $request->request->get('state');
        }

        if ($smartcard->getState() !== $newState) {
            if (!SmartcardStates::isTransitionAllowed($smartcard->getState(), $newState)) {
                throw new BadRequestHttpException('Is not possible change state from '.$smartcard->getState().' to '.$newState);
            }

            $smartcard->setState($newState);
        }

        $this->getDoctrine()->getManager()->persist($smartcard);
        $this->getDoctrine()->getManager()->flush();

        return $this->json($smartcard);
    }

    /**
     * Info about smartcard.
     *
     * @Rest\Get("/offline-app/v1/smartcards/{serialNumber}")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE') or is_granted('ROLE_FIELD_OFFICER') or is_granted('ROLE_ENUMERATOR')")
     * @ParamConverter("smartcard")
     *
     * @param Smartcard $smartcard
     *
     * @return Response
     */
    public function info(Smartcard $smartcard): Response
    {
        return $this->json($smartcard);
    }

    /**
     * Beneficiary by its smartcard.
     *
     * @Rest\Get("/offline-app/v1/smartcards/{serialNumber}/beneficiary")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @ParamConverter("smartcard")
     *
     * @param Smartcard $smartcard
     *
     * @return Response
     */
    public function beneficiary(Smartcard $smartcard): Response
    {
        return $this->json($smartcard->getBeneficiary());
    }


    private function writeData(string $type, string $user, string $smartcard, $data): void
    {
        $filename = $this->get('kernel')->getLogDir().'/';
        $filename .= implode('_', ['SC-invalidData', $type, 'vendor-'.$user, 'sc-'.$smartcard.'.json']);
        $logFile = fopen($filename, "a+");
        fwrite($logFile, $data);
        fclose($logFile);
    }
}
