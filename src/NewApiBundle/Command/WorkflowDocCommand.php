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

        $this->twig = $twig;
        $this->menuStructure = [];
    }

    public function configure()
    {
        $this->setName('app:wfd:generate');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createHTMLFiles($this->getAllEntities($output), $output);

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @return array
     */
    private function getAllEntities(OutputInterface $output): array
    {
        $entities = [];
        $workflowInfo = Yaml::parseFile('src/NewApiBundle/Resources/config/workflow.yml',Yaml::PARSE_CONSTANT);
        $this->directoryFinder = new Finder();
        $this->directoryFinder->directories()->in('src')->depth('== 0')->sortByName();

        if ($this->directoryFinder->hasResults()) {
            foreach ($this->directoryFinder as $directory) {

                $directoryName = $directory->getFilename();
                $this->menuStructure[$directoryName] = [];
                $this->fileFinder = new Finder();
                $this->fileFinder->files()->in('src/'.$directoryName.'/Entity')->name('*.php')->sortByName();

                if ($this->fileFinder->hasResults()) {
                    foreach ($this->fileFinder as $file) {

                        $entityName = $file->getFilenameWithoutExtension();
                        $entityNameWithPath = $file->getRelativePathname();
                        $entityNameWithPath = substr($entityNameWithPath, 0, strpos($entityNameWithPath, '.'));
                        $entityNameWithPath = str_replace('/','\\',$entityNameWithPath);
                        $entityFullName = $directoryName.'\Entity\\'.$entityNameWithPath;
                        try {
                            $entityReflection = new \ReflectionClass($entityFullName);
                            $entityDescription = $entityReflection->getDocComment();
                            if ($entityDescription === false) {
                                $entityDescription = 'Description comment missing';
                            }
                        } catch (\Exception $exception) {
                            $output->writeln($exception->getMessage());
                        }

                        $entities[$entityFullName] = new Entity($entityName, $entityFullName, $directoryName, $entityDescription);
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

    /**
     * @param array $entities
     * @param OutputInterface $output
     */
    private function createHTMLFiles(array $entities, OutputInterface $output)
    {
        if (!empty($entities)) {

            try {
                $renderedHTML = $this->twig->render('WorkflowDoc/entity.html.twig',[
                    'menu' => $this->menuStructure
                ]);
            } catch (\Exception $exception) {
                $output->writeln($exception->getMessage());
            }

            if (!file_exists('web/workflows')) {
                mkdir('web/workflows', 0777, true);
            }

            file_put_contents('web/workflows/index.html', $renderedHTML);

            foreach ($entities as $entity) {

                try {
                    $renderedHTML = $this->twig->render('WorkflowDoc/entity.html.twig',[
                        'menu' => $this->menuStructure,
                        'currentEntity' => $entity

                    ]);
                } catch (\Exception $exception) {
                    $output->writeln($exception->getMessage());
                }


                if (!file_exists('web/workflows/'.$entity->getBundleName())) {
                    mkdir('web/workflows/'.$entity->getBundleName(), 0777, true);
                }

                file_put_contents('web/workflows/'.$entity->getBundleName().'/'.$entity->getEntityName().'-workflow.html', $renderedHTML);

            }
        }
    }
}