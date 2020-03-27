<?php

namespace CommonBundle\Export;

use Symfony\Component\HttpFoundation\Response;

interface Exportable
{
    /**
     * @param string $type valid file extension
     * @param string $countryIso3
     * @param array $ids list of identifiers of entity to export
     * @param array $filters filters to finds entities to export
     * @return Response
     */
    public function export(string $type, string $countryIso3, $ids, $filters): Response;
}
