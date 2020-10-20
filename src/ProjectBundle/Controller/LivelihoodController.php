<?php

namespace ProjectBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use ProjectBundle\Enum\Livelihood;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SWG\Parameter(
 *     name="country",
 *     in="header",
 *     type="string",
 *     required=true
 * )
 */
class LivelihoodController extends Controller
{
    /**
     * Get livelihoods.
     *
     * @Rest\Get("/livelihoods")
     *
     * @SWG\Tag(name="Projects")
     *
     * @SWG\Response(
     *     response=200,
     *     description="All Projects",
     *     @SWG\Schema(
     *          type="array"
     *     )
     * )
     *
     * @param Request $request
     *
     * @return Response
     */
    public function list(Request $request)
    {
        $data = [];

        foreach (Livelihood::values() as $value) {
            $data[] = [
                'value' => $value,
                'name' => Livelihood::translate($value),
            ];
        }

        // filter result according to request
        if ($request->query->get('values')) {
            $filterValues = (array) $request->query->get('values', []);

            $data = array_filter($data, function ($item) use ($filterValues) {
                return in_array($item['value'], $filterValues);
            });
        }

        return $this->json($data);
    }
}
