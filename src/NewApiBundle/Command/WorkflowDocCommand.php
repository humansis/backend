<?php
declare(strict_types=1);
namespace NewApiBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Parser;
use Twig\Environment;

class WorkflowDocCommand extends Command
{

    /**
     * @var Finder
     */
    private $directoryFinder;

    /**
     * @var Finder
     */
    private $fileFinder;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig, string $name = null)
    {
        parent::__construct($name);

        $this->directoryFinder = new Finder();
        $this->fileFinder = new Finder();
        $this->twig = $twig;
    }

    public function configure()
    {
        $this->setName('app:wfd:generate');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->renderHTMLFiles($this->getAllEntities($output));

        return 0;
    }

    private function getAllEntities($output): array
    {
        $bundles = [];
        $workflowInfo = Yaml::parseFile('src/NewApiBundle/Resources/config/workflow.yml',Yaml::PARSE_CONSTANT);
        $this->directoryFinder->directories()->in('src')->depth('== 0');

        if ($this->directoryFinder->hasResults()) {
            foreach ($this->directoryFinder as $directory) {

                $directoryName = $directory->getFilename();
                $entities = [];
                $this->fileFinder->files()->in('src/'.$directoryName.'/Entity')->name('*.php');

                if ($this->fileFinder->hasResults()) {
                    foreach ($this->fileFinder as $file) {

                        $variable = $file->getFilenameWithoutExtension();
                        $entities[$variable] = [];
                    }
                    $bundles[$directoryName] = $entities;

                }
            }
        }

        foreach ($workflowInfo['framework']['workflows'] as $workflowKey => $workflowContent) {
            foreach ($workflowContent['supports'] as $supportedEntity) {

                foreach ($bundles as $bundleKey => $bundledEntities) {
                    foreach ($bundledEntities as $entityKey => $entityData) {

                        if (strcmp($supportedEntity, $bundleKey.'\Entity\\'.$entityKey) == 0) {
                            array_push($bundles[$bundleKey][$entityKey],$workflowKey);
                        }
                    }
                }

            }
        }

        return $bundles;
    }

    private function renderHTMLFiles($data)
    {
        if (!empty($data)) {

            $renderedHTML = $this->twig->render('WorkflowDoc/entity.html.twig',[
                'bundles' => $data
            ]);

            if (!file_exists('web/workflows')) {
                mkdir('web/workflows', 0777, true);
            }

            file_put_contents('web/workflows/index.html', $renderedHTML);

            foreach ($data as $budleKey => $bundledEntities) {
                foreach ($bundledEntities as $entityKey => $entityData) {

                    $renderedHTML = $this->twig->render('WorkflowDoc/entity.html.twig',[
                        'bundles' => $data,
                        'currentBundle' => $budleKey,
                        'currentEntity'  => $entityKey
                    ]);

                    if (!file_exists('web/workflows/'.$budleKey)) {
                        mkdir('web/workflows/'.$budleKey, 0777, true);
                    }

                    file_put_contents('web/workflows/'.$budleKey.'/'.$entityKey.'-workflow.html', $renderedHTML);
                }
            }
        }
    }
}