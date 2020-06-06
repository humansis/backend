<?php

namespace VoucherBundle\Controller;

use BeneficiaryBundle\Entity\Beneficiary;
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
use VoucherBundle\Entity\Product;
use VoucherBundle\Entity\Smartcard;

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
        /** @var Beneficiary $beneficiary */
        $beneficiary = $this->getDoctrine()->getRepository(Beneficiary::class)->find($request->get('beneficiaryId'));
        if (!$beneficiary) {
            throw $this->createNotFoundException('Beneficiary does not exists.');
        }

        $serialNumber = strtoupper($request->get('serialNumber'));
        if (!preg_match('~^[A-F0-9]+$~', $serialNumber)) {
            throw new BadRequestHttpException('Smartcards\' serial number is invalid.');
        }

        if ($this->getDoctrine()->getRepository(Smartcard::class)->findBySerialNumber($serialNumber)) {
            throw new BadRequestHttpException('Smartcard with this serial number is already exist.');
        }

        $smartcard = new Smartcard($serialNumber, $beneficiary, \DateTime::createFromFormat('Y-m-d\TH:i:sO', $request->get('createdAt')));
        $smartcard->setState(Smartcard::STATE_ACTIVE);

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
     * Update smartcard.
     *
     * @Rest\Put("/offline-app/v1/smartcards/{serialNumber}")
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
            Smartcard::STATE_ACTIVE => [Smartcard::STATE_INACTIVE, Smartcard::STATE_FROZEN, Smartcard::STATE_CANCELLED],
            Smartcard::STATE_FROZEN => [Smartcard::STATE_ACTIVE, Smartcard::STATE_CANCELLED],
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
     * Put money to smartcard.
     *
     * @Rest\Post("/smartcards/{serialNumber}/deposit")
     * @Security("is_granted('ROLE_BENEFICIARY_MANAGEMENT_WRITE')")
     * @ParamConverter("smartcard")
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
     * @SWG\Response(response=404, description="Smartcard does not exists.")
     * @SWG\Response(response=400, description="Smartcard is blocked.")
     *
     * @param Smartcard $smartcard
     * @param Request   $request
     *
     * @return Response
     */
    public function deposit(Smartcard $smartcard, Request $request): Response
    {
        if (!$smartcard->isActive()) {
            throw new BadRequestHttpException('Smartcard is blocked.');
        }

        $value = $request->request->get('value');

        $smartcard->addDeposit($value, \DateTime::createFromFormat('Y-m-d\TH:i:sO', $request->get('createdAt')));

        $this->getDoctrine()->getManager()->persist($smartcard);
        $this->getDoctrine()->getManager()->flush();

        $json = $this->get('serializer')->serialize($smartcard, 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }

    /**
     * Purchase goods from smartcard.
     *
     * @Rest\Post("/vendor-app/v1/smartcards/{serialNumber}/purchase")
     * @Security("is_granted('ROLE_VENDOR')")
     * @ParamConverter("smartcard")
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
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *             property="productId",
     *             type="integer",
     *             description="ID of purchased product"
     *         ),
     *         @SWG\Property(
     *             property="quantity",
     *             type="number",
     *             description="Product quantity"
     *         ),
     *         @SWG\Property(
     *             property="value",
     *             type="number",
     *             description="Product price"
     *         ),
     *         @SWG\Property(
     *             property="createdAt",
     *             type="string",
     *             description="ISO 8601 time of purchase in UTC",
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
     * @SWG\Response(response=404, description="Smartcard does not exists.")
     * @SWG\Response(response=400, description="Product does not exists.")
     *
     * @param Smartcard $smartcard
     * @param Request   $request
     *
     * @return Response
     */
    public function purchase(Smartcard $smartcard, Request $request): Response
    {
        $value = $request->request->get('value');
        if (!is_numeric($value)) {
            throw new BadRequestHttpException('Value is not valid');
        }

        /** @var Product $product */
        $product = $this->getDoctrine()->getRepository(Product::class)->find($request->request->get('productId'));
        if (!$product) {
            throw $this->createNotFoundException('Product does not exists.');
        }

        $quantity = $request->request->get('quantity');
        if (!is_numeric($quantity)) {
            throw new BadRequestHttpException('Quantity is not valid');
        }

        $smartcard->addPurchase($value, $product, $quantity, \DateTime::createFromFormat('Y-m-d\TH:i:sO', $request->get('createdAt')));

        $this->getDoctrine()->getManager()->persist($smartcard);
        $this->getDoctrine()->getManager()->flush();

        $json = $this->get('serializer')->serialize($smartcard, 'json', ['groups' => ['SmartcardOverview']]);

        return new Response($json);
    }
}
