<?php
declare(strict_types=1);
namespace NewApiBundle\Command;

use NewApiBundle\Component\WorkflowDoc\Entity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
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

    /**
     * @var Entity[]
     */
    private $menuStructure;

    public function __construct(Environment $twig, string $name = null)
    {
        parent::__construct($name);

        $this->directoryFinder = new Finder();
        $this->fileFinder = new Finder();
        $this->twig = $twig;
        $this->menuStructure = [];
    }

    public function configure()
    {
        $this->setName('app:wfd:generate');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->renderHTMLFiles($this->getAllEntities());

        return 0;
    }

    private function getAllEntities(): array
    {
        $entities = [];
        $workflowInfo = Yaml::parseFile('src/NewApiBundle/Resources/config/workflow.yml',Yaml::PARSE_CONSTANT);

        $this->directoryFinder->directories()->in('src')->depth('== 0')->sortByName();

        if ($this->directoryFinder->hasResults()) {
            foreach ($this->directoryFinder as $directory) {

                $directoryName = $directory->getFilename();
                $this->menuStructure[$directoryName] = [];
                $this->fileFinder->files()->in('src/'.$directoryName.'/Entity')->name('*.php')->sortByName();

                if ($this->fileFinder->hasResults()) {
                    foreach ($this->fileFinder as $file) {

                        $entityName = $file->getFilenameWithoutExtension();
                        $entityFullName = $directoryName.'\Entity\\'.$entityName;
                        $entities[$entityFullName] = new Entity($entityName, $entityFullName, $directoryName);
                        array_push($this->menuStructure[$directoryName], $entities[$entityFullName]);

                    }
                }
            }
        }

        foreach ($workflowInfo['framework']['workflows'] as $workflowKey => $workflowContent) {
            foreach ($workflowContent['supports'] as $supportedEntity) {

                if (array_key_exists($supportedEntity,$entities) AND $entities[$supportedEntity]->isSupported($supportedEntity)) {
                    $entities[$supportedEntity]->addSupportedWorkflows($workflowKey);
                }

            }
        }

        return $entities;
    }

    private function renderHTMLFiles($entities)
    {
        if (!empty($entities)) {

            $renderedHTML = $this->twig->render('WorkflowDoc/entity.html.twig',[
                'menu' => $this->menuStructure
            ]);

            if (!file_exists('web/workflows')) {
                mkdir('web/workflows', 0777, true);
            }

            file_put_contents('web/workflows/index.html', $renderedHTML);

            foreach ($entities as $entity) {

                $renderedHTML = $this->twig->render('WorkflowDoc/entity.html.twig',[
                    'menu' => $this->menuStructure,
                    'currentEntity' => $entity

                ]);

                if (!file_exists('web/workflows/'.$entity->getBundleName())) {
                    mkdir('web/workflows/'.$entity->getBundleName(), 0777, true);
                }

                file_put_contents('web/workflows/'.$entity->getBundleName().'/'.$entity->getEntityName().'-workflow.html', $renderedHTML);

            }
        }
    }
}