<?php


namespace BeneficiaryBundle\Controller;


use BeneficiaryBundle\Utils\HouseholdService;
use JMS\Serializer\SerializationContext;
use RA\RequestValidatorBundle\RequestValidator\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BeneficiaryBundle\Entity\Household;

//Annotations
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;

class HouseholdController extends Controller
{

    /**
     * @Rest\Put("/households", name="add_household")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Parameter(
     *     name="household",
     *     in="body",
     *     required=true,
     *     @Model(type=Household::class, groups={"FullHousehold"})
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Household created",
     *     @Model(type=Household::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     *
     * @param Request $request
     * @return Response
     */
    public function addAction(Request $request)
    {
        $householdArray = $request->request->all();
        /** @var HouseholdService $householeService */
        $householeService = $this->get('beneficiary.household_service');
        try
        {
            $household = $householeService->create($householdArray);
        }
        catch (ValidationException $exception)
        {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('jms_serializer')
            ->serialize($household, 'json', SerializationContext::create()->setSerializeNull(true));

        return new Response($json);
    }

    /**
     * @Rest\Post("/households/{id}")
     *
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Parameter(
     *     name="household",
     *     in="body",
     *     type="string",
     *     required=true,
     *     description="fields of the household which must be updated",
     *     @Model(type=Household::class)
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="SUCCESS",
     *     @Model(type=Household::class)
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="BAD_REQUEST"
     * )
     *
     * @param Request $request
     * @param Household $household
     * @return Response
     */
    public function editAction(Request $request, Household $household)
    {
        $arrayHousehold = $request->request->all();
        /** @var HouseholdService $householdService */
        $householdService = $this->get('beneficiary.household_service');

        try
        {
            $newHousehold = $householdService->update($household, $arrayHousehold);
        }
        catch (ValidationException $exception)
        {
            return new Response(json_encode(current($exception->getErrors())), Response::HTTP_BAD_REQUEST);
        }
        catch (\Exception $e)
        {
            return new Response($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $json = $this->get('jms_serializer')
            ->serialize(
                $newHousehold,
                'json',
                SerializationContext::create()->setGroups("FullHousehold")->setSerializeNull(true)
            );

        return new Response($json);
    }

    /**
     * @Rest\Post("/households/all", name="all_households")
     *
     * @SWG\Tag(name="Households")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All households",
     *     @SWG\Schema(
     *          type="array",
     *          @SWG\Items(ref=@Model(type=Household::class))
     *     )
     * )
     *
     * @return Response
     */
    public function allAction(Request $request)
    {
        $filters = $request->request->all();
        /** @var HouseholdService $householeService */
        $householeService = $this->get('beneficiary.household_service');
        $households = $householeService->getAll($filters['__country'], $filters);

        $json = $this->get('jms_serializer')
            ->serialize(
                $households,
                'json',
                SerializationContext::create()->setGroups("FullHousehold")->setSerializeNull(true)
            );
        return new Response($json);
    }
}