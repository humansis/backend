<?php

namespace Tests\Component\Assistance\Validator;

use Component\Assistance\Validator\AssistanceMove;
use Component\Assistance\Validator\AssistanceMoveValidator;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Entity\Assistance;
use Entity\Project;
use Entity\ProjectSector;
use Enum\AssistanceState;
use InputType\Assistance\MoveAssistanceInputType;
use PHPUnit\Framework\MockObject\MockObject;
use Repository\ProjectRepository;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AssistanceMoveValidatorTest extends ConstraintValidatorTestCase
{
    private readonly MockObject $projectRepositoryMock;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct(
            $name,
            $data,
            $dataName
        );

        $this->projectRepositoryMock = $this->getMockBuilder(ProjectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createValidator(): ConstraintValidator
    {
        return new AssistanceMoveValidator($this->projectRepositoryMock);
    }

    public function testValidMove()
    {
        $moveAssistanceInputType = new MoveAssistanceInputType();
        $moveAssistanceInputType->setOriginalProjectId(1);
        $moveAssistanceInputType->setTargetProjectId(2);

        $assistanceMock = $this->getMockBuilder(Assistance::class)
            ->disableOriginalConstructor()
            ->getMock();

        $originalProjectMock = $this->getMockBuilder(Project::class)
            ->disableOriginalConstructor()
            ->getMock();

        $targetProjectMock = $this->getMockBuilder(Project::class)
            ->disableOriginalConstructor()
            ->getMock();

        $assistanceMock->method('getProject')
            ->willReturn($originalProjectMock);

        $originalProjectMock->method('getId')
            ->willReturn(1);

        $this->projectRepositoryMock->method('find')
            ->with(2)
            ->willReturn($targetProjectMock);

        $assistanceMock->expects($this->once())
            ->method('getDateDistribution')
            ->willReturn(DateTime::createFromFormat('Y-m-d', '2020-10-10'));

        $targetProjectMock->expects($this->once())
            ->method('getStartDate')
            ->willReturn(DateTime::createFromFormat('Y-m-d', '2020-01-01'));

        $targetProjectMock->expects($this->once())
            ->method('getEndDate')
            ->willReturn(DateTime::createFromFormat('Y-m-d', '2020-12-31'));

        $assistanceMock->expects($this->once())
            ->method('getSector')
            ->willReturn('Food Security');

        $targetProjectMock->expects($this->once())
            ->method('getSectors')
            ->willReturn(new ArrayCollection([new ProjectSector('Food Security', $targetProjectMock)]));

        $originalProjectMock->expects($this->once())
            ->method('getAllowedProductCategoryTypes')
            ->willReturn([
                'A',
                'B',
                'C',
            ]);

        $targetProjectMock->expects($this->once())
            ->method('getAllowedProductCategoryTypes')
            ->willReturn([
                'F',
                'A',
                'B',
                'C',
                'D',
            ]);

        $originalProjectMock->expects($this->once())
            ->method('getCountryIso3')
            ->willReturn('iso3');

        $targetProjectMock->expects($this->once())
            ->method('getCountryIso3')
            ->willReturn('iso3');

        $assistanceMock->expects($this->once())
            ->method('getState')
            ->willReturn(AssistanceState::NEW);

        $assistanceMove = new AssistanceMove();
        $assistanceMove->moveAssistanceInputType = $moveAssistanceInputType;

        $this->validator->validate($assistanceMock, $assistanceMove);
        $this->assertNoViolation();
    }

    public function testInvalidMoveAllViolations()
    {
        $moveAssistanceInputType = new MoveAssistanceInputType();
        $moveAssistanceInputType->setOriginalProjectId(1);
        $moveAssistanceInputType->setTargetProjectId(2);

        $assistanceMock = $this->getMockBuilder(Assistance::class)
            ->disableOriginalConstructor()
            ->getMock();

        $originalProjectMock = $this->getMockBuilder(Project::class)
            ->disableOriginalConstructor()
            ->getMock();

        $targetProjectMock = $this->getMockBuilder(Project::class)
            ->disableOriginalConstructor()
            ->getMock();

        $assistanceMock->method('getProject')
            ->willReturn($originalProjectMock);

        $originalProjectMock->method('getId')
            ->willReturn(50);

        $this->projectRepositoryMock->method('find')
            ->with(2)
            ->willReturn($targetProjectMock);

        $assistanceMock->expects($this->once())
            ->method('getDateDistribution')
            ->willReturn(DateTime::createFromFormat('Y-m-d', '2010-10-10'));

        $targetProjectMock->expects($this->once())
            ->method('getStartDate')
            ->willReturn(DateTime::createFromFormat('Y-m-d', '2020-01-01'));

        $targetProjectMock->expects($this->once())
            ->method('getEndDate')
            ->willReturn(DateTime::createFromFormat('Y-m-d', '2020-12-31'));

        $assistanceMock->expects($this->once())
            ->method('getSector')
            ->willReturn('Different');

        $targetProjectMock->expects($this->once())
            ->method('getSectors')
            ->willReturn(new ArrayCollection([new ProjectSector('Food Security', $targetProjectMock)]));

        $originalProjectMock->expects($this->once())
            ->method('getAllowedProductCategoryTypes')
            ->willReturn([
                'A',
                'B',
                'C',
            ]);

        $targetProjectMock->expects($this->once())
            ->method('getAllowedProductCategoryTypes')
            ->willReturn([
                'F',
                'A',
                'C',
                'D',
            ]);

        $originalProjectMock->expects($this->once())
            ->method('getCountryIso3')
            ->willReturn('iso3');

        $targetProjectMock->expects($this->once())
            ->method('getCountryIso3')
            ->willReturn('another');

        $assistanceMock->expects($this->exactly(2))
            ->method('getState')
            ->willReturn(AssistanceState::VALIDATED);

        $assistanceMove = new AssistanceMove();
        $assistanceMove->moveAssistanceInputType = $moveAssistanceInputType;

        $this->validator->validate($assistanceMock, $assistanceMove);
        $this->assertEquals(6, count($this->context->getViolations()));
    }
}
