<?php

namespace VoucherBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\DistributionData;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Entity\SmartcardDeposit;
use VoucherBundle\InputType\SmartcardPurchase as SmartcardPurchaseInput;

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
    /**
     * Register smartcard to system and assign to beneficiary.
     *
     * @Rest\Post("/offline-app/v1/smartcards")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
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
        $serialNumber = strtoupper($request->get('serialNumber'));

        /** @var Smartcard $smartcard */
        $smartcard = $this->getDoctrine()->getRepository(Smartcard::class)->findBySerialNumber($serialNumber);
        if ($smartcard) {
            $smartcard->setSuspicious(false);
        } else {
            $smartcard = new Smartcard($serialNumber, \DateTime::createFromFormat('Y-m-d\TH:i:sO', $request->get('createdAt')));
            $smartcard->setState(Smartcard::STATE_ACTIVE);
        }

        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->getDoctrine()->getRepository(Beneficiary::class)->find($request->get('beneficiaryId'));
        if ($beneficiary) {
            $smartcard->setBeneficiary($beneficiary);
        } else {
            $smartcard->setSuspicious(true, '');
        }

        $this->getDoctrine()->getManager()->persist($smartcard);
        $this->getDoctrine()->getManager()->flush();

        $json = $this->get('serializer')->serialize($smartcard, 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }

    /**
     * Info about smartcard.
     *
     * @Rest\Get("/offline-app/v1/smartcards/{serialNumber}")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE') or is_granted('ROLE_VENDOR')")
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
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
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
     * @param Smartcard $smartcard
     * @param Request   $request
     *
     * @return Response
     */
    public function change(Smartcard $smartcard, Request $request): Response
    {
        $newState = $smartcard->getState();
        if ($request->request->has('state')) {
            $newState = $request->request->get('state');
        }

        $possibleFlow = [
            Smartcard::STATE_UNASSIGNED => Smartcard::STATE_ACTIVE,
            Smartcard::STATE_ACTIVE => [Smartcard::STATE_INACTIVE, Smartcard::STATE_CANCELLED],
            Smartcard::STATE_INACTIVE => Smartcard::STATE_CANCELLED,
        ];

        if ($smartcard->getState() !== $newState && isset($possibleFlow[$smartcard->getState()])) {
            if (!in_array($newState, $possibleFlow[$smartcard->getState()])) {
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
     * @Rest\Patch("/offline-app/v1/smartcards/{serialNumber}/deposit")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
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
    public function deposit(Request $request): Response
    {
        $serialNumber = $request->get('serialNumber');

        $smartcard = $this->getDoctrine()->getRepository(Smartcard::class)->findBySerialNumber($serialNumber);
        if (!$smartcard) {
            $smartcard = new Smartcard($serialNumber, \DateTime::createFromFormat('Y-m-d\TH:i:sO', $request->get('createdAt')));
            $smartcard->setState(Smartcard::STATE_ACTIVE);
            $smartcard->setSuspicious(true, 'Smartcard does not exists in database');
        }

        if (!$smartcard->isActive()) {
            $smartcard->setSuspicious(true, 'Smartcard is in '.$smartcard->getState().' state');
        }

        $distribution = $this->getDoctrine()->getRepository(DistributionData::class)->find($request->request->getInt('distributionId'));
        if (!$distribution) {
            throw new BadRequestHttpException('Distribution does not exists.');
        }

        $distributionBeneficiary = $this->getDoctrine()->getRepository(DistributionBeneficiary::class)->findByDistributionAndBeneficiary(
            $distribution,
            $smartcard->getBeneficiary()
        );

        $deposit = SmartcardDeposit::create(
            $smartcard,
            $this->getUser(),
            $distributionBeneficiary,
            (float) $request->request->get('value'),
            \DateTime::createFromFormat('Y-m-d\TH:i:sO', $request->get('createdAt'))
        );

        $smartcard->addDeposit($deposit);

        $this->getDoctrine()->getManager()->persist($smartcard);
        $this->getDoctrine()->getManager()->flush();

        $json = $this->get('serializer')->serialize($smartcard, 'json', ['groups' => ['SmartcardOverview']]);

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
     *     @Model(type=SmartcardPurchaseInput::class)
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
     */
    public function purchase(Request $request): Response
    {
        /** @var SmartcardPurchaseInput $data */
        $data = $this->get('serializer')->deserialize($request->getContent(), SmartcardPurchaseInput::class, 'json');

        $errors = $this->get('validator')->validate($data);
        if (count($errors) > 0) {
            throw new \RuntimeException((string) $errors);
        }

        $serialNumber = $request->get('serialNumber');

        $smartcard = $this->getDoctrine()->getRepository(Smartcard::class)->findBySerialNumber($serialNumber);
        if (!$smartcard) {
            $smartcard = new Smartcard($serialNumber, \DateTime::createFromFormat('Y-m-d\TH:i:sO', $request->get('createdAt')));
            $smartcard->setState(Smartcard::STATE_ACTIVE);
            $smartcard->setSuspicious(true, 'Smartcard does not exists in database');

            $this->getDoctrine()->getManager()->persist($smartcard);
            $this->getDoctrine()->getManager()->flush();
        }

        $this->get('voucher.purchase_service')->purchaseSmartcard($smartcard, $data);

        $json = $this->get('serializer')->serialize($smartcard, 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }
}
