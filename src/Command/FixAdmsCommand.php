<?php

namespace Command;

use Doctrine\DBAL\Connection;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XMLReader;

class FixAdmsCommand extends ContainerAwareCommand
{
    /**@var Connection  */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:adm:fix')
            ->setDescription('Fix relations between countries')
            ->addArgument('country', InputArgument::REQUIRED);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filepath = $this->getFilepath($input);
        $count = $this->getCount($filepath);

        $conn = $this->connection;

        $progressBar = new ProgressBar($output, $count);
        $progressBar->start();

        $xml = new XMLReader();
        $xml->open($filepath);

        $name1 = $code1 = $name2 = $code2 = $name3 = $code3 = null;
        while ($xml->read()) {
            if (XMLReader::ELEMENT === $xml->nodeType && 'adm1' === $xml->name) {
                $name1 = $xml->getAttribute('name');
                $code1 = $xml->getAttribute('code');

                $this->processAdm1($conn, $input->getArgument('country'), $code1, $name1);
            }
            if (XMLReader::ELEMENT === $xml->nodeType && 'adm2' === $xml->name) {
                $name2 = $xml->getAttribute('name');
                $code2 = $xml->getAttribute('code');

                $this->process($conn, 'adm2', 'adm1', $code2, $name2, $code1);
            }
            if (XMLReader::ELEMENT === $xml->nodeType && 'adm3' === $xml->name) {
                $name3 = $xml->getAttribute('name');
                $code3 = $xml->getAttribute('code');

                $this->process($conn, 'adm3', 'adm2', $code3, $name3, $code2);
            }
            if (XMLReader::ELEMENT === $xml->nodeType && 'adm4' === $xml->name) {
                $name4 = $xml->getAttribute('name');
                $code4 = $xml->getAttribute('code');

                $this->process($conn, 'adm4', 'adm3', $code4, $name4, $code3);

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $xml->close();

        return 0;
    }

    private function getFilepath(InputInterface $input)
    {
        $filepath = __DIR__ . '/../Resources/locations/' . strtolower($input->getArgument('country')) . '.xml';
        if (!file_exists($filepath)) {
            throw new InvalidArgumentException('Unable to find file with Adms for specified country.');
        }

        return $filepath;
    }

    private function getCount(string $filepath)
    {
        $xml = new XMLReader();
        $xml->open($filepath);

        $count = 0;
        while ($xml->read()) {
            if (XMLReader::ELEMENT === $xml->nodeType && 'adm4' === $xml->name) {
                ++$count;
            }
        }

        return $count;
    }

    private function getNewLocationId(Connection $conn)
    {
        $conn->beginTransaction();
        $conn->executeQuery('INSERT INTO location VALUES (null)');
        $id = $conn->fetchColumn('SELECT LAST_INSERT_ID()');
        $conn->commit();

        return $id;
    }

    private function process(Connection $conn, $adm, $admPrev, $code, $name, $codePrev)
    {
        $data = $conn->fetchAssoc(
            "SELECT {$admPrev}.code AS codePrev, {$adm}.name FROM {$adm} JOIN {$admPrev} ON $adm.{$admPrev}_id={$admPrev}.id WHERE {$adm}.code=?",
            [$code]
        );
        if (false === $data) {
            $conn->executeQuery(
                "INSERT INTO {$adm} SET code=?, name=?, location_id=?, {$admPrev}_id=(SELECT id FROM {$admPrev} WHERE code=?)",
                [$code, $name, $this->getNewLocationId($conn), $codePrev]
            );
        } else {
            if ($data['name'] !== $name) {
                $conn->executeQuery("UPDATE {$adm} SET name=? WHERE code=?", [$name, $code]);
            }
            if ($data['codePrev'] !== $codePrev) {
                $conn->executeQuery(
                    "UPDATE {$adm} SET {$admPrev}_id=(SELECT id FROM {$admPrev} WHERE code=?) WHERE code=?",
                    [$codePrev, $code]
                );
            }
        }
    }

    private function processAdm1(Connection $conn, $countryIso3, $code, $name)
    {
        $data = $conn->fetchAssoc('SELECT code, name FROM adm1 WHERE code=?', [$code]);
        if (false === $data) {
            $conn->executeQuery(
                'INSERT INTO adm1 SET code=?, name=?, countryIso3=?, location_id=?',
                [$code, $name, strtoupper($countryIso3), $this->getNewLocationId($conn)]
            );
        } elseif ($data['name'] !== $name) {
            $conn->executeQuery('UPDATE adm1 SET name=? WHERE code=?', [$name, $code]);
        }
    }
}
