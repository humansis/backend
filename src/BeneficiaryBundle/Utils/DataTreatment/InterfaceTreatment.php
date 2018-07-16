<?php


namespace BeneficiaryBundle\Utils\DataTreatment;


use ProjectBundle\Entity\Project;

interface InterfaceTreatment
{
    public function treat(Project $project, array $householdsArray);
}