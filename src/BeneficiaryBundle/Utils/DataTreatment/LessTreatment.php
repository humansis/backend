<?php


namespace BeneficiaryBundle\Utils\DataTreatment;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Utils\BeneficiaryService;
use BeneficiaryBundle\Utils\HouseholdService;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;
use Symfony\Component\DependencyInjection\Container;

class LessTreatment extends AbstractTreatment
{
    /**
     * @param Project $project
     * @param array $householdsArray
     * @param string $email
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function treat(Project $project, array $householdsArray, string $email)
    {
        foreach ($householdsArray as $householdArray)
        {
            foreach ($householdArray['data'] as $oldBeneficiaryArray)
            {
                $oldBeneficiary = $this->em->getRepository(Beneficiary::class)->find($oldBeneficiaryArray['id']);
                if (!$oldBeneficiary instanceof Beneficiary)
                    continue;
                $this->beneficiaryService->remove($oldBeneficiary);
            }
        }
        return $this->addHouseholds($project, $email);
    }

    /**
     * @param Project $project
     * @param string $email
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Exception
     */
    public function addHouseholds(Project $project, string $email)
    {
        $householdsToAdd = $this->getHouseholdsNoTypo($email);
        $errors = [];
        $numberAdded = 0;
        $this->clearCache('households.new');
        foreach ($householdsToAdd as $householdToAdd)
        {
            try
            {
                $this->householdService->createOrEdit($householdToAdd['new'], [$project]);
                $this->saveHouseholds($email . '-households.new', $householdToAdd['new']);
                $numberAdded++;
                $this->em->clear();
            }
            catch (\Exception $exception)
            {
                if (!$this->em->isOpen())
                {
                    $this->container->get('doctrine')->resetManager();
                    $this->em = $this->container->get('doctrine')->getManager();
                }
                $this->em->clear();
                $errors[] = [
                    "household" => $householdToAdd,
                    "error" => $exception->getMessage()
                ];
            }
        }
        return ["number_added" => $numberAdded, "error" => $errors];
    }

    /**
     * @param string $email
     * @return mixed|null
     * @throws \Exception
     */
    private function getHouseholdsNoTypo(string $email)
    {
        if (null === $this->token)
            return null;

        $dir_root = $this->container->get('kernel')->getRootDir();
        $dir_var = $dir_root . '/../var/data/' . $this->token;
        if (!is_dir($dir_var))
            mkdir($dir_var);
        $dir_no_typo = $dir_var . '/' . $email . '-no_typo';
        if (!is_file($dir_no_typo))
            return [];
        return json_decode(file_get_contents($dir_no_typo), true);
    }
}