<?php

namespace VoucherBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Entity\Organization;
use CommonBundle\Repository\OrganizationRepository;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Export\SmartcardInvoiceExport;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use NewApiBundle\Component\Country\Countries;
use NewApiBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UserBundle\Entity\User;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\Entity\SmartcardPurchase;
use VoucherBundle\Entity\Invoice;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Enum\SmartcardStates;
use VoucherBundle\InputType\SmartcardPurchaseDeprecated as SmartcardPurchaseDeprecatedInput;
use VoucherBundle\InputType\SmartcardPurchase as SmartcardPurchaseInput;
use VoucherBundle\InputType\SmartcardInvoice as RedemptionBatchInput;
use VoucherBundle\Mapper\SmartcardMapper;
use VoucherBundle\Repository\SmartcardPurchaseRepository;

/**
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true,
 *     description="This parameter is obsolete"
 * )
 */
class SmartcardController extends Controller
{

    /** @var SmartcardInvoiceExport */
    private $exporter;
    /** @var OrganizationRepository */
    private  $organizationRepository;
    /** @var Countries  */
    private $countries;

    /**
     * @param SmartcardInvoiceExport $exporter
     * @param OrganizationRepository $organizationRepository
     * @param Countries              $countries
     */
    public function __construct(SmartcardInvoiceExport $exporter, OrganizationRepository $organizationRepository, Countries $countries)
    {
        $this->exporter = $exporter;
        $this->organizationRepository = $organizationRepository;
        $this->countries = $countries;
    }

    /**
     * Register smartcard to system and assign to beneficiary.
     *
     * @Rest\Post("/offline-app/v1/smartcards")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE') or is_granted('ROLE_FIELD_OFFICER') or is_granted('ROLE_ENUMERATOR')")
     *
     * @SWG\Tag(name="Smartcards")
     * @SWG\Tag(name="Offline App")
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *             property="serialNumber",
     *             type="string",
     *             description="Serial number (GUID) of smartcard"
     *         ),
     *         @SWG\Property(
     *             property="beneficiaryId",
     *             type="integer",
     *             description="ID of beneficiary"
     *         ),
     *         @SWG\Property(
     *             property="createdAt",
     *             type="string",
     *             description="ISO 8601 time of register smartcard in UTC",
     *             example="2020-02-02T12:00:00+0200"
     *         )
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Smartcard succesfully registered to system",
     *     @Model(type=Smartcard::class, groups={"SmartcardOverview"})
     * )
     *
     * @SWG\Response(response=404, description="Beneficiary does not exists")
     * @SWG\Response(response=400, description="Smartcard is already registered")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function register(Request $request): Response
    {
        $smartcard = $this->get('smartcard_service')->register(
            strtoupper($request->get('serialNumber')),
            $request->get('beneficiaryId'),
            \DateTime::createFromFormat('Y-m-d\TH:i:sO', $request->get('createdAt')));

        $mapper = $this->get(SmartcardMapper::class);

        return $this->json($mapper->toFullArray($smartcard));
    }

    /**
     * Info about smartcard.
     *
     * @Rest\Get("/offline-app/v1/smartcards/{serialNumber}")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE') or is_granted('ROLE_FIELD_OFFICER') or is_granted('ROLE_ENUMERATOR')")
     * @ParamConverter("smartcard")
     *
     * @SWG\Tag(name="Smartcards")
     * @SWG\Tag(name="Offline App")
     *
     * @SWG\Parameter(
     *     name="serialNumber",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="Serial number (GUID) of smartcard"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Smartcard overview",
     *     @Model(type=Smartcard::class, groups={"SmartcardOverview"})
     * )
     *
     * @SWG\Response(response=404, description="Smartcard does not exists.")
     *
     * @param Smartcard $smartcard
     * @param Request   $request
     *
     * @return Response
     */
    public function info(Smartcard $smartcard, Request $request): Response
    {
        $json = $this->get('serializer')->serialize($smartcard, 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }

    /**
     * List of blocked smardcards.
     * Blocked smartcards are not allowed to pay with.
     *
     * @Rest\Get("/vendor-app/v1/smartcards/blocked")
     * @Security("is_granted('ROLE_VENDOR')")
     *
     * @SWG\Tag(name="Smartcards")
     * @SWG\Tag(name="Vendor App")
     *
     * @SWG\Parameter(
     *     name="country",
     *     in="header",
     *     type="string",
     *     required=true
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="List of blocked smartcards",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(
     *             type="string",
     *             description="serial number of blocked smartcard"
     *         )
     *     )
     * )
     *
     * @param Request $request
     *
     * @return Response
     */
    public function listOfBlocked(Request $request): Response
    {
        $country = $request->headers->get('country');
        $smartcards = $this->getDoctrine()->getRepository(Smartcard::class)->findBlocked($country);

        return new JsonResponse($smartcards);
    }

    /**
     * Update smartcard, typically its' state.
     *
     * @Rest\Patch("/offline-app/v1/smartcards/{serialNumber}")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE') or is_granted('ROLE_FIELD_OFFICER') or is_granted('ROLE_ENUMERATOR')")
     *
     * @SWG\Tag(name="Smartcards")
     * @SWG\Tag(name="Offline App")
     *
     * @SWG\Parameter(
     *     name="serialNumber",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="Serial number (GUID) of smartcard"
     * )
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *             property="state",
     *             type="string",
     *             description="smartcard state",
     *             enum={"active", "inactive", "frozen", "cancelled"}
     *         ),
     *         @SWG\Property(
     *             property="createdAt",
     *             type="string",
     *             description="ISO 8601 time of state change in UTC",
     *             example="2020-02-02T12:00:00+0200"
     *         )
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Smartcard succesfully updated",
     *     @Model(type=Smartcard::class, groups={"SmartcardOverview"})
     * )
     *
     * @SWG\Response(response=404, description="Smartcard does not exists.")
     * @SWG\Response(response=400, description="Smartcard state can't be changed this way. State flow restriction.")
     *
     * @param string  $serialNumber
     * @param Request $request
     *
     * @return Response
     */
    public function change(string $serialNumber, Request $request): Response
    {
        $smartcard = $this->getDoctrine()->getRepository(Smartcard::class)->findActiveBySerialNumber($serialNumber);

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

        $json = $this->get('serializer')->serialize($smartcard, 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }

    /**
     * Put money to smartcard. If smartcard does not exists, it will be created.
     *
     * @Rest\Patch("/offline-app/v2/smartcards/{serialNumber}/deposit")
     * @Rest\Patch("/offline-app/v3/smartcards/{serialNumber}/deposit")
     * @ParamConverter("smartcard")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE') or is_granted('ROLE_FIELD_OFFICER') or is_granted('ROLE_ENUMERATOR')")
     *
     * @SWG\Tag(name="Smartcards")
     *
     * @SWG\Parameter(
     *     name="serialNumber",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="Serial number (GUID) of smartcard"
     * )
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *             property="value",
     *             type="number",
     *             description="Value of money deposit to smartcard"
     *         ),
     *         @SWG\Property(
     *             property="balance",
     *             type="number",
     *             description="Actual balance on smartcard"
     *         ),
     *         @SWG\Property(
     *             property="distributionId",
     *             type="int",
     *             description="ID of distribution from which are money deposited"
     *         ),
     *         @SWG\Property(
     *             property="createdAt",
     *             type="string",
     *             description="ISO 8601 time of deposit in UTC",
     *             example="2020-02-02T12:00:00+0200"
     *         )
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Money succesfully succesfully deposited to smartcard",
     *     @Model(type=Smartcard::class, groups={"SmartcardOverview"})
     * )
     *
     * @param Request $request
     *
     * @return Response
     */
    public function legacyDeposit(Request $request): Response
    {
        try {
            $deposit = $this->get('smartcard_service')->depositLegacy(
                $request->get('serialNumber'),
                $request->request->get('beneficiaryId'),
                $request->request->getInt('distributionId'),
                $request->request->get('value'),
                null,
                \DateTime::createFromFormat('Y-m-d\TH:i:sO', $request->get('createdAt')),
                $this->getUser()
            );
        } catch (\Exception $exception) {
            $this->writeData(
                'depositV23',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            throw $exception;
        }

        $json = $this->get('serializer')->serialize($deposit->getSmartcard(), 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }

    /**
     * Purchase goods from smartcard. If smartcard does not exists, it will be created.
     *
     * @Rest\Patch("/vendor-app/v1/smartcards/{serialNumber}/purchase")
     * @Security("is_granted('ROLE_VENDOR')")
     *
     * @SWG\Tag(name="Smartcards")
     * @SWG\Tag(name="Vendor App")
     *
     * @SWG\Parameter(
     *     name="serialNumber",
     *     in="path",
     *     type="string",
     *     required=true,
     *     description="Serial number (GUID) of smartcard"
     * )
     *
     * @SWG\Parameter(name="purchase from smartcard",
     *     in="body",
     *     required=true,
     *     type="object",
     *     @Model(type=SmartcardPurchaseDeprecatedInput::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Smartcard succesfully registered to system",
     *     @Model(type=Smartcard::class, groups={"SmartcardOverview"})
     * )
     *
     * @SWG\Response(response=400, description="Product does not exists.")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     *
     * @deprecated
     */
    public function purchaseDeprecated(Request $request): Response
    {
        /** @var SmartcardPurchaseDeprecatedInput $data */
        $data = $this->get('serializer')->deserialize($request->getContent(), SmartcardPurchaseDeprecatedInput::class, 'json');

        $errors = $this->get('validator')->validate($data);
        if (count($errors) > 0) {
            $this->container->get('logger')->error('validation errors: '.((string) $errors));
            $this->writeData(
                'purchaseV1',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            // Changed by PIN-1637: it is needed for one specific period of syncing and need to be reverted after vendor app change
            // throw new \RuntimeException((string) $errors);
            return new Response();
        }

        try {
            $purchase = $this->get('smartcard_service')->purchaseWithoutReusingSC($request->get('serialNumber'), $data);
        } catch (\Exception $exception) {
            $this->writeData(
                'purchaseV1',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            throw $exception;
        }

        $json = $this->get('serializer')->serialize($purchase->getSmartcard(), 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }

    /**
     * Purchase goods from smartcard. If smartcard does not exists, it will be created.
     *
     * @Rest\Patch("/vendor-app/v2/smartcards/{serialNumber}/purchase")
     * @Security("is_granted('ROLE_VENDOR')")
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     *
     * @deprecated
     */
    public function purchaseDeprecated2(Request $request): Response
    {
        /** @var SmartcardPurchaseInput $data */
        $data = $this->get('serializer')->deserialize($request->getContent(), SmartcardPurchaseInput::class, 'json');

        $errors = $this->get('validator')->validate($data);
        if (count($errors) > 0) {
            $this->container->get('logger')->error('validation errors: '.((string) $errors));
            // Changed by PIN-1637: it is needed for one specific period of syncing and need to be reverted after vendor app change
            // throw new \RuntimeException((string) $errors);
            $this->writeData(
                'purchaseV2',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            return new Response();
        }

        try {
            $purchase = $this->get('smartcard_service')->purchaseWithoutReusingSC($request->get('serialNumber'), $data);
        } catch (\Exception $exception) {
            $this->writeData(
                'purchaseV2',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            throw $exception;
        }

        $json = $this->get('serializer')->serialize($purchase->getSmartcard(), 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }

    /**
     * Purchase goods from smartcard. If smartcard does not exists, it will be created.
     *
     * @Rest\Patch("/vendor-app/v3/smartcards/{serialNumber}/purchase")
     * @Security("is_granted('ROLE_VENDOR')")
     *
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     */
    public function purchase(Request $request): Response
    {
        /** @var SmartcardPurchaseInput $data */
        $data = $this->get('serializer')->deserialize($request->getContent(), SmartcardPurchaseInput::class, 'json');

        $errors = $this->get('validator')->validate($data);
        if (count($errors) > 0) {
            $this->container->get('logger')->error('validation errors: '.((string) $errors).' data: '.json_encode($request->request->all()));
            // Changed by PIN-1637: it is needed for one specific period of syncing and need to be reverted after vendor app change
            // throw new \RuntimeException((string) $errors);
            $this->writeData(
                'purchaseV3',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            return new Response();
        }

        try {
            $purchase = $this->get('smartcard_service')->purchase($request->get('serialNumber'), $data);
        } catch (\Exception $exception) {
            $this->writeData(
                'purchaseV3',
                $this->getUser() ? $this->getUser()->getUsername() : 'nouser',
                $request->get('serialNumber', 'missing'),
                json_encode($request->request->all())
            );
            throw $exception;
        }

        $json = $this->get('serializer')->serialize($purchase->getSmartcard(), 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }

    /**
     * Get vendor purchase counts.
     *
     * @Rest\Get("/smartcards/purchases/{id}", name="smarcards_purchases")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Smartcards")
     * @SWG\Tag(name="Single Vendor")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All vendor purchases"
     * )
     *
     * @param Vendor $vendor
     *
     * @return Response
     *
     * @throws NonUniqueResultException
     */
    public function getPurchasesSummary(Vendor $vendor): Response
    {
        /** @var SmartcardPurchaseRepository $repository */
        $repository = $this->getDoctrine()->getManager()->getRepository(SmartcardPurchase::class);
        $summary = $repository->countPurchases($vendor);

        return $this->json($summary);
    }

    /**
     * Get vendor purchase details.
     *
     * @Rest\Get("/smartcards/purchases/{id}/details", name="smarcards_purchases_details")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Smartcards")
     * @SWG\Tag(name="Single Vendor")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All vendor unredeemed purchases"
     * )
     *
     * @param Vendor $vendor
     *
     * @return Response
     */
    public function getPurchasesDetails(Vendor $vendor): Response
    {
        /** @var SmartcardPurchaseRepository $repository */
        $repository = $this->getDoctrine()->getManager()->getRepository(SmartcardPurchase::class);
        $details = $repository->getUsedUnredeemedDetails($vendor);

        return $this->json($details);
    }

    /**
     * Get vendor purchases to redeem.
     *
     * @Rest\Get("/smartcards/purchases/to-redemption/{id}", name="smarcards_purchases_to_redemtion")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Smartcards")
     * @SWG\Tag(name="Single Vendor")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Vendor purchases to redeem"
     * )
     *
     * @param Vendor $vendor
     *
     * @return Response
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getPurchasesToRedeemSummary(Vendor $vendor): Response
    {
        /** @var SmartcardPurchaseRepository $repository */
        $repository = $this->getDoctrine()->getManager()->getRepository(SmartcardPurchase::class);
        $summaries = $repository->countPreliminaryInvoices($vendor);

        return $this->json($summaries);
    }

    /**
     * Get vendor purchase counts.
     *
     * @Rest\Get("/smartcards/batch", name="smarcards_redeemed_batches")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Smartcards")
     * @SWG\Tag(name="Single Vendor")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All vendor purchases",
     * )
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getRedeemBatches(Request $request): Response
    {
        $vendorId = $request->query->getInt('vendor');
        if (!$vendorId) {
            throw $this->createNotFoundException();
        }

        $vendor = $this->getDoctrine()->getRepository(Vendor::class)->find($vendorId);
        if (!$vendor) {
            throw $this->createNotFoundException('Vendor does not exists');
        }

        /** @var SmartcardPurchaseRepository $repository */
        $repository = $this->getDoctrine()->getManager()->getRepository(Invoice::class);
        $summaryBatches = $repository->findBy([
            'vendor' => $vendor,
        ]);

        return $this->json($summaryBatches);
    }

    /**
     * Get vendor purchase batch detail.
     *
     * @Rest\Get("/smartcards/batch/{id}")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Smartcards")
     *
     * @SWG\Response(response=200)
     *
     * @param Invoice $batch
     *
     * @return Response
     */
    public function getBatchesDetails(Invoice $batch): Response
    {
        return $this->json($batch);
    }

    /**
     * Get vendor purchase batch details.
     *
     * @Rest\Get("/smartcards/batch/{id}/purchases", name="smarcards_redeemed_batches_details")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_READ')")
     *
     * @SWG\Tag(name="Smartcards")
     * @SWG\Tag(name="Single Vendor")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All vendor purchases",
     * )
     *
     * @param Invoice $batch
     *
     * @return Response
     */
    public function getRedeemBatchesDetails(Invoice $batch): Response
    {
        /** @var SmartcardPurchaseRepository $repository */
        $repository = $this->getDoctrine()->getManager()->getRepository(SmartcardPurchase::class);
        $details = $repository->getDetailsByBatch($batch);

        return $this->json($details);
    }

    /**
     * Set vendor purchase as redeemed.
     *
     * @Rest\Post("/smartcards/purchases/redeem-batch/{id}", name="smarcards_redeem_batch")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Smartcards")
     * @SWG\Tag(name="Single Vendor")
     *
     * @SWG\Parameter(
     *     name="vendor",
     *     in="body",
     *     type="array",
     *     required=true,
     *     description="fields of the vendor purchase ids",
     *     schema="int"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="All vendor purchases"
     * )
     *
     * @param Vendor               $vendor
     * @param RedemptionBatchInput $newBatch
     *
     * @return Response
     */
    public function redeemBatch(Vendor $vendor, RedemptionBatchInput $newBatch): Response
    {
        try {
            $redemptionBath = $this->get('smartcard_service')->redeem($vendor, $newBatch, $this->getUser());
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        return $this->json([
            'id' => $redemptionBath->getId(),
        ]);
    }

    /**
     * @Rest\Get("/web-app/v1/smartcards/batch/{id}/legacy-export")
     *
     * @SWG\Tag(name="Export")
     *
     * @SWG\Response(
     *     response=200,
     *     description="streamed file"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="invalid redeemed batch"
     * )
     *
     * @param Invoice $batch
     *
     * @return Response
     *
     * @throws
     */
    public function exportLegacy(Invoice $batch): Response
    {
        // todo find organisation by relation to smartcard
        $organization = $this->getDoctrine()->getRepository(Organization::class)->findOneBy([]);

        $filename = $this->get('distribution.export_legacy.smartcard_invoice')->export($batch, $organization, $this->getUser());

        $response = new BinaryFileResponse(getcwd().'/'.$filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guess(getcwd().'/'.$filename));
        }

        return $response;
    }

    /**
     * @Rest\Get("/smartcards/batch/{id}/export")
     *
     * @SWG\Tag(name="Export")
     *
     * @SWG\Response(
     *     response=200,
     *     description="streamed file"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="invalid redeemed batch"
     * )
     *
     * @param Invoice $batch
     *
     * @return Response
     *
     */
    public function export(Invoice $batch): Response
    {
        $country = $this->countries->getCountry($batch->getProject()->getIso3());

        // todo find organisation by relation to smartcard
        $organization = $this->organizationRepository->findOneBy([]);
        $filename = $this->exporter->export(
            $batch,
            $organization,
            $this->getUser(),
            $country->getLanguage()
        );

        $response = new BinaryFileResponse(getcwd().'/'.$filename);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();
        if ($mimeTypeGuesser->isSupported()) {
            $response->headers->set('Content-Type', $mimeTypeGuesser->guess(getcwd().'/'.$filename));
        }

        return $response;
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
