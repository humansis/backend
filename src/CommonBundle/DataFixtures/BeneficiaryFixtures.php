<?php

namespace CommonBundle\DataFixtures;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity\DistributionData;
use Doctrine\Bundle\FixturesBundle\Fixture;
use FOS\UserBundle\Doctrine\UserManager;
use Doctrine\Common\Persistence\ObjectManager;
use ProjectBundle\Entity\Project;
use Symfony\Component\HttpKernel\Kernel;
use CommonBundle\Entity\Location;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;

class BeneficiaryFixtures extends Fixture

{



    /** @var UserManager $manager */
    private $manager;

    /** @var Kernel $kernel */
    private $kernel;



    public function __construct(UserManager $manager , Kernel $kernel)
    {
        $this->manager = $manager;
        $this->kernel = $kernel;

    }

    /**
     * @param ObjectManager $manager
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */

    public function load(ObjectManager $manager)
    {
        $nbFilesLoaded = $this->parseDirectory($manager);
        print_r("\n\n $nbFilesLoaded file(s) loaded.\n\n");
    }

    /**
     * @param ObjectManager $manager
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function parseDirectory(ObjectManager $manager)
    {
        $dir_root = $this->kernel->getRootDir();
        $file = $dir_root . '/../src/CommonBundle/DataFixtures/BeneficiariesFiles/beneficiaryhouseholder.xlsx';
        if (!file_exists($file)) {
            return false;
        }

        $reader = new Xlsx();
        $spreadSheet = $reader->load($file);
        $sheet = $spreadSheet->getActiveSheet();
        $rowIterator = $sheet->getRowIterator(2);


        while ($rowIterator->current()->getCellIterator()->current()->getValue() != null) {

            $rowIndex = $rowIterator->current()->getRowIndex();

            // remplir la table location


            $location = new Location();
            $Adm1 = new Adm1();
            $Adm2 = new Adm2();
            $Adm3 = new Adm3();
            $Adm4 = new Adm4();

            $Adm1 ->setName('Battambang')->setCountryISO3('KHM');
            $Adm2 ->setName('bla');
            $Adm3 ->setName('test');
            $Adm4 ->setName('tooot');


            $manager->persist($Adm1);
            $manager->persist($Adm2);
            $manager->persist($Adm3);
            $manager->persist($Adm4);


            $location ->setAdm1($Adm1)
                      ->setAdm2($Adm2)
                      ->setAdm3($Adm3)
                      ->setAdm4($Adm4);

            $manager->persist($location);






            // remplir la table household

            $household = new Household() ;

            $household->setAddressStreet($sheet->getCell('A'.$rowIndex)->getValue())
                ->setLivelihood($sheet->getCell('D'.$rowIndex)->getValue())
                ->setLatitude($sheet->getCell('F'.$rowIndex)->getValue())
                ->setLongitude($sheet->getCell('G'.$rowIndex)->getValue())
                ->setNotes($sheet->getCell('E'.$rowIndex)->getValue())
                ->setAddressPostcode($sheet->getCell('C'.$rowIndex)->getValue())
                ->setAddressNumber($sheet->getCell('B'.$rowIndex)->getValue())
                ->setLocation($location);

            $manager->persist($household);

            // remplir la table beneficiary

            $beneficiary = new Beneficiary();

            $beneficiary->setGivenName($sheet->getCell('L'.$rowIndex)->getValue())
                ->setFamilyName($sheet->getCell('M'.$rowIndex)->getValue())
                ->setGender($sheet->getCell('N'.$rowIndex)->getValue())
                ->setStatus($sheet->getCell('O'.$rowIndex)->getValue())
                ->setDateOfBirth((new \DateTime())->setDate (2018, 8, 01) )
                ->setHousehold($household);

            $manager->persist($beneficiary);


            //remplir la table project

            $project = new Project();

            $project ->setName($sheet->getCell('T'.$rowIndex)->getValue())
                        ->setStartDate(new \DateTime())
                        ->setEndDate((new \DateTime())->setDate(2018,8,01))
                        ->setIso3('KHM')
                        ->setArchived('true');


            $manager->persist($project);

            // remplir la table distribution

            $distribution = new DistributionData() ;

            $distribution ->setName($sheet->getCell('W'.$rowIndex)->getValue())
                ->setLocation($location)
                ->setArchived($sheet->getCell('V'.$rowIndex)->getValue())
                ->setDateDistribution((new \DateTime())->setDate(2018,8,01))
                ->setType($sheet->getCell('W'.$rowIndex)->getValue())
                ->setUpdatedOn((new \DateTime())->setDate(2018,8,01)->setTime(8,13,58))
                ->setProject($project);

            $manager->persist($distribution);





            $rowIterator->next();

        }

        $manager->flush();

            return true ;

    }


}