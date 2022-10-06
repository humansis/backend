<?php

declare(strict_types=1);

namespace Tests\Component\Import\Helper;

use Entity\Import;
use Enum\ImportState;
use Symfony\Component\Console\Tester\CommandTester;

trait CliTrait
{
    private function userStartedUploading(Import $import, bool $shouldEndCorrect): void
    {
        $this->importService->updateStatus($import, ImportState::UPLOADING);
        if ($shouldEndCorrect) {
            $this->assertEquals(ImportState::UPLOADING, $import->getState());
        } else {
            $this->assertEquals(ImportState::UPLOAD_FAILED, $import->getState());
        }
    }

    private function userStartedIntegrityCheck(Import $import, bool $shouldEndCorrect, int $commandCallCount = 1): void
    {
        $this->importService->updateStatus($import, ImportState::INTEGRITY_CHECKING);
        if ($shouldEndCorrect) {
            $this->assertEquals(ImportState::INTEGRITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(ImportState::INTEGRITY_CHECK_FAILED, $import->getState());
        }
    }

    private function userStartedIdentityCheck(Import $import, bool $shouldEndCorrect, int $commandCallCount = 1): void
    {
        $this->importService->updateStatus($import, ImportState::IDENTITY_CHECKING);
        if ($shouldEndCorrect) {
            $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(ImportState::IDENTITY_CHECK_FAILED, $import->getState());
        }
    }

    private function userStartedSimilarityCheck(Import $import, bool $shouldEndCorrect, int $commandCallCount = 1): void
    {
        $this->importService->updateStatus($import, ImportState::SIMILARITY_CHECKING);
        if ($shouldEndCorrect) {
            $this->assertEquals(ImportState::SIMILARITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(ImportState::SIMILARITY_CHECK_FAILED, $import->getState());
        }
    }

    private function userStartedFinishing(Import $import, bool $skipSimilarityCheck = false): void
    {
        if ($skipSimilarityCheck) {
            $this->assertEquals(ImportState::IDENTITY_CHECK_CORRECT, $import->getState());
        } else {
            $this->assertEquals(ImportState::SIMILARITY_CHECK_CORRECT, $import->getState());
        }

        $this->importService->updateStatus($import, ImportState::IMPORTING);
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
