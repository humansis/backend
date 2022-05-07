<?php
declare(strict_types=1);

namespace Tests\NewApiBundle\Component\Import\Helper;

use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use Symfony\Component\Console\Tester\CommandTester;

trait CliTrait
{
    private function userStartedIntegrityCheck(Import $import, bool $shouldEndCorrect, int $commandCallCount = 1): void
    {
        $this->importService->updateStatus($import, ImportState::INTEGRITY_CHECKING);
        $this->assertEquals(ImportState::INTEGRITY_CHECKING, $import->getState());
        $this->cli('app:import:integrity', $import);
        if ($shouldEndCorrect) {
            $this->assertEquals(ImportState::INTEGRITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(ImportState::INTEGRITY_CHECK_FAILED, $import->getState());
        }
    }

    private function userStartedIdentityCheck(Import $import, bool $shouldEndCorrect, int $commandCallCount = 1): void
    {
        $this->importService->updateStatus($import, ImportState::IDENTITY_CHECKING);
        $this->assertEquals(ImportState::IDENTITY_CHECKING, $import->getState());
        $this->cli('app:import:identity', $import);
        if ($shouldEndCorrect) {
            $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(ImportState::IDENTITY_CHECK_FAILED, $import->getState());
        }
    }

    private function userStartedSimilarityCheck(Import $import, bool $shouldEndCorrect, int $commandCallCount = 1): void
    {
        $this->importService->updateStatus($import, ImportState::SIMILARITY_CHECKING);
        $this->assertEquals(ImportState::SIMILARITY_CHECKING, $import->getState());
        $this->cli('app:import:similarity', $import);
        if ($shouldEndCorrect) {
            $this->assertEquals(ImportState::SIMILARITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(ImportState::SIMILARITY_CHECK_FAILED, $import->getState());
        }
    }

    private function userStartedFinishing(Import $import): void
    {
        $this->assertEquals(ImportState::SIMILARITY_CHECK_CORRECT, $import->getState());
        $this->importService->updateStatus($import, ImportState::IMPORTING);
        $this->assertEquals(ImportState::IMPORTING, $import->getState());
        $this->cli('app:import:finish', $import);
        $this->assertEquals(ImportState::FINISHED, $import->getState());
    }

    private function cli(string $commandName, Import $import): void
    {
        $command = $this->application->find($commandName);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['import' => $import->getId()]);
        $this->assertEquals(0, $commandTester->getStatusCode(), "Command $commandName failed");
    }

}
