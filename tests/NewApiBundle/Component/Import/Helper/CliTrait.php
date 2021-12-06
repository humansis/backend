<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import\Helper;

use NewApiBundle\Component\Import\Entity\Import;
use NewApiBundle\Component\Import\Enum\State;
use Symfony\Component\Console\Tester\CommandTester;

trait CliTrait
{
    private function userStartedIntegrityCheck(Import $import, bool $shouldEndCorrect): void
    {
        $this->importService->updateStatus($import, State::INTEGRITY_CHECKING);
        $this->assertEquals(State::INTEGRITY_CHECKING, $import->getState());
        $this->cli('app:import:integrity', $import);
        $this->cli('app:import:integrity', $import);
        if ($shouldEndCorrect) {
            $this->assertEquals(State::INTEGRITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(State::INTEGRITY_CHECK_FAILED, $import->getState());
        }
    }

    private function userStartedIdentityCheck(Import $import, bool $shouldEndCorrect): void
    {
        $this->importService->updateStatus($import, State::IDENTITY_CHECKING);
        $this->assertEquals(State::IDENTITY_CHECKING, $import->getState());
        $this->cli('app:import:identity', $import);
        $this->cli('app:import:identity', $import);
        if ($shouldEndCorrect) {
            $this->assertEquals(State::IDENTITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(State::IDENTITY_CHECK_FAILED, $import->getState());
        }
    }

    private function userStartedSimilarityCheck(Import $import, bool $shouldEndCorrect): void
    {
        $this->importService->updateStatus($import, State::SIMILARITY_CHECKING);
        $this->assertEquals(State::SIMILARITY_CHECKING, $import->getState());
        $this->cli('app:import:similarity', $import);
        $this->cli('app:import:similarity', $import);
        if ($shouldEndCorrect) {
            $this->assertEquals(State::SIMILARITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(State::SIMILARITY_CHECK_FAILED, $import->getState());
        }
    }

    private function userStartedFinishing(Import $import): void
    {
        $this->assertEquals(State::SIMILARITY_CHECK_CORRECT, $import->getState());
        $this->importService->updateStatus($import, State::IMPORTING);
        $this->assertEquals(State::IMPORTING, $import->getState());
        $this->cli('app:import:finish', $import);
        $this->assertEquals(State::FINISHED, $import->getState());
    }

    private function cli(string $commandName, Import $import): void
    {
        $command = $this->application->find($commandName);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['import' => $import->getId()]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command $commandName failed");
    }

}
