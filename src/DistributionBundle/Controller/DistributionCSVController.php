<?php


namespace DistributionBundle\Controller;


use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Utils\DistributionCSVService;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class DistributionCSVController extends Controller
{
    /**
     * @Rest\Post("/distributions/{id}/import", name="import_csv")
     * @Security("is_granted('ROLE_PROJECT_MANAGEMENT_WRITE')")
     *
     * @SWG\Tag(name="Distributions")
     *
     * @SWG\Parameter(
     *     name="file",
     *     in="formData",
     *     required=true,
     *     type="file"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Import distribution"
     * )
     *
     * @param Request $request
     * @param \DistributionBundle\Entity\DistributionData $distributionData
     * @return Response
     * @throws \Exception
     */
    public function importAction(Request $request, DistributionData $distributionData)
    {
        if (!$request->files->has('file'))
            return new Response("You must upload a file.", 500);
        $file = $request->files->get('file');
        /** @var DistributionCSVService $distributionCSVService */
        $distributionCSVService = $this->get('distribution.distribution_csv_service');
        try
        {
            $return = $distributionCSVService->import($distributionData, $file);
        }
        catch (Exception $e)
        {
            return new Response($e->getMessage(), 500);
        }
        catch (\PhpOffice\PhpSpreadsheet\Exception $e)
        {
            return new Response($e->getMessage(), 500);
        }

        return new Response(json_encode($return));
    }
}