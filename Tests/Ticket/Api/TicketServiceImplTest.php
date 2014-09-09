<?php
/*
 * Copyright (c) 2014 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */

namespace Eltrino\DiamanteDeskBundle\Tests\Ticket\Api;

use Eltrino\DiamanteDeskBundle\Attachment\Api\Dto\AttachmentInput;
use Eltrino\DiamanteDeskBundle\Attachment\Model\File;
use Eltrino\DiamanteDeskBundle\Entity\Attachment;
use Eltrino\DiamanteDeskBundle\Entity\Ticket;
use Eltrino\DiamanteDeskBundle\Entity\Branch;
use Eltrino\DiamanteDeskBundle\Ticket\Infrastructure\Persistence\Doctrine\DBAL\Types\PriorityType;
use Eltrino\DiamanteDeskBundle\Ticket\Model\Source;
use Eltrino\DiamanteDeskBundle\Ticket\Model\Status;
use Eltrino\DiamanteDeskBundle\Ticket\Model\Priority;
use Eltrino\DiamanteDeskBundle\Ticket\Api\TicketServiceImpl;
use Eltrino\PHPUnit\MockAnnotations\MockAnnotations;
use Oro\Bundle\UserBundle\Entity\User;

class TicketServiceImplTest extends \PHPUnit_Framework_TestCase
{
    const DUMMY_TICKET_ID     = 1;
    const DUMMY_ATTACHMENT_ID = 1;
    const DUMMY_TICKET_SUBJECT      = 'Subject';
    const DUMMY_TICKET_DESCRIPTION  = 'Description';
    const DUMMY_FILENAME      = 'dummy_filename.ext';
    const DUMMY_FILE_CONTENT  = 'DUMMY_CONTENT';
    const DUMMY_STATUS        = 'dummy';

    /**
     * @var TicketServiceImpl
     */
    private $ticketService;

    /**
     * @var \Eltrino\DiamanteDeskBundle\Ticket\Model\TicketRepository
     * @Mock \Eltrino\DiamanteDeskBundle\Ticket\Model\TicketRepository
     */
    private $ticketRepository;

    /**
     * @var \Eltrino\DiamanteDeskBundle\Ticket\Api\Internal\AttachmentService
     * @Mock \Eltrino\DiamanteDeskBundle\Ticket\Api\Internal\AttachmentService
     */
    private $ticketAttachmentService;

    /**
     * @var \Eltrino\DiamanteDeskBundle\Entity\Ticket
     * @Mock \Eltrino\DiamanteDeskBundle\Entity\Ticket
     */
    private $ticket;

    /**
     * @var \Eltrino\DiamanteDeskBundle\Branch\Model\BranchRepository
     * @Mock \Eltrino\DiamanteDeskBundle\Branch\Model\BranchRepository
     */
    private $branchRepository;

    /**
     * @var\ Eltrino\DiamanteDeskBundle\Ticket\Api\Factory\TicketFactory
     * @Mock Eltrino\DiamanteDeskBundle\Ticket\Api\Factory\TicketFactory
     */
    private $ticketFactory;

    /**
     * @var \Eltrino\DiamanteDeskBundle\Ticket\Api\Internal\UserService
     * @Mock \Eltrino\DiamanteDeskBundle\Ticket\Api\Internal\UserService
     */
    private $userService;

    /**
     * @var \Oro\Bundle\SecurityBundle\SecurityFacade
     * @Mock \Oro\Bundle\SecurityBundle\SecurityFacade
     */
    private $securityFacade;

    protected function setUp()
    {
        MockAnnotations::init($this);

        $this->ticketService = new TicketServiceImpl(
            $this->ticketRepository,
            $this->branchRepository,
            $this->ticketFactory,
            $this->ticketAttachmentService,
            $this->userService,
            $this->securityFacade
        );
    }

    /**
     * @test
     */
    public function thatTicketCreatesWithDefaultStatusAndNoAttachments()
    {
        $branchId = 1;
        $branch = $this->createBranch();
        $this->branchRepository->expects($this->once())->method('get')->with($this->equalTo($branchId))
            ->will($this->returnValue($branch));

        $reporterId = 2;
        $assigneeId = 3;
        $reporter = $this->createReporter();
        $assignee = $this->createAssignee();

        $this->userService->expects($this->at(0))->method('getUserById')->with($this->equalTo($reporterId))
            ->will($this->returnValue($reporter));

        $this->userService->expects($this->at(1))->method('getUserById')->with($this->equalTo($assigneeId))
            ->will($this->returnValue($assignee));

        $status = Status::NEW_ONE;
        $priority = Priority::DEFAULT_PRIORITY;
        $source = Source::PHONE;

        $ticket = new Ticket(
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $branch,
            $reporter,
            $assignee,
            $priority,
            $source,
            $status
        );

        $this->ticketFactory->expects($this->once())->method('create')->with(
            $this->equalTo(self::DUMMY_TICKET_SUBJECT), $this->equalTo(self::DUMMY_TICKET_DESCRIPTION),
            $this->equalTo($branch), $this->equalTo($reporter), $this->equalTo($assignee),
            $this->equalTo($priority), $this->equalTo($source)
        )->will($this->returnValue($ticket));

        $this->ticketRepository->expects($this->once())->method('store')->with($this->equalTo($ticket));

        $this->ticketAttachmentService->expects($this->exactly(0))->method('createAttachmentsForItHolder');
            //->with($this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY), $this->equalTo($ticket));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('CREATE'), $this->equalTo('Entity:EltrinoDiamanteDeskBundle:Ticket'))
            ->will($this->returnValue(true));

        $this->ticketService->createTicket(
            $branchId,
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $reporterId,
            $assigneeId,
            $priority,
            $source
        );
    }

    /**
     * @test
     */
    public function thatTicketCreatesWithStatusAndNoAttachments()
    {
        $branchId = 1;
        $branch = $this->createBranch();
        $this->branchRepository->expects($this->once())->method('get')->with($this->equalTo($branchId))
            ->will($this->returnValue($branch));

        $reporterId = 2;
        $assigneeId = 3;
        $reporter = $this->createReporter();
        $assignee = $this->createAssignee();

        $this->userService->expects($this->at(0))->method('getUserById')->with($this->equalTo($reporterId))
            ->will($this->returnValue($reporter));

        $this->userService->expects($this->at(1))->method('getUserById')->with($this->equalTo($assigneeId))
            ->will($this->returnValue($assignee));

        $status = Status::IN_PROGRESS;
        $priority = Priority::DEFAULT_PRIORITY;
        $source = Source::PHONE;

        $ticket = new Ticket(
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $branch,
            $reporter,
            $assignee,
            $priority,
            $source,
            $status
        );

        $this->ticketFactory->expects($this->once())->method('create')->with(
            $this->equalTo(self::DUMMY_TICKET_SUBJECT), $this->equalTo(self::DUMMY_TICKET_DESCRIPTION),
            $this->equalTo($branch), $this->equalTo($reporter), $this->equalTo($assignee),
            $this->equalTo($priority), $this->equalTo($source)
        )->will($this->returnValue($ticket));

        $this->ticketRepository->expects($this->once())->method('store')->with($this->equalTo($ticket));

        $this->ticketAttachmentService->expects($this->exactly(0))->method('createAttachmentsForItHolder');
            //->with($this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY), $this->equalTo($ticket));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('CREATE'), $this->equalTo('Entity:EltrinoDiamanteDeskBundle:Ticket'))
            ->will($this->returnValue(true));

        $this->ticketService->createTicket(
            $branchId,
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $reporterId,
            $assigneeId,
            $priority,
            $source,
            $status
        );
    }

    /**
     * @test
     */
    public function thatTicketCreatesWithDefaultStatusAndAttachments()
    {
        $branchId = 1;
        $branch = $this->createBranch();
        $this->branchRepository->expects($this->once())->method('get')->with($this->equalTo($branchId))
            ->will($this->returnValue($branch));

        $reporterId = 2;
        $assigneeId = 3;
        $reporter = $this->createReporter();
        $assignee = $this->createAssignee();

        $this->userService->expects($this->at(0))->method('getUserById')->with($this->equalTo($reporterId))
            ->will($this->returnValue($reporter));

        $this->userService->expects($this->at(1))->method('getUserById')->with($this->equalTo($assigneeId))
            ->will($this->returnValue($assignee));

        $status = Status::NEW_ONE;
        $priority = Priority::DEFAULT_PRIORITY;
        $source = Source::PHONE;

        $ticket = new Ticket(
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $branch,
            $reporter,
            $assignee,
            $priority,
            $source,
            $status
        );

        $this->ticketFactory->expects($this->once())->method('create')->with(
            $this->equalTo(self::DUMMY_TICKET_SUBJECT), $this->equalTo(self::DUMMY_TICKET_DESCRIPTION),
            $this->equalTo($branch), $this->equalTo($reporter), $this->equalTo($assignee),
            $this->equalTo($priority), $this->equalTo($source)
        )->will($this->returnValue($ticket));

        $attachmentInputs = $this->attachmentInputs();

        $this->ticketAttachmentService->expects($this->once())->method('createAttachmentsForItHolder')
            ->with($this->equalTo($attachmentInputs), $this->equalTo($ticket));

        $this->ticketRepository->expects($this->once())->method('store')->with($this->equalTo($ticket));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('CREATE'), $this->equalTo('Entity:EltrinoDiamanteDeskBundle:Ticket'))
            ->will($this->returnValue(true));

        $this->ticketService->createTicket(
            $branchId,
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $reporterId,
            $assigneeId,
            $priority,
            $source,
            null,
            $attachmentInputs
        );
    }

    /**
     * @test
     */
    public function thatTicketUpdatesWithNoAttachments()
    {
        $reporterId = 2;
        $assigneeId = 3;
        $reporter = $this->createReporter();
        $assignee = $this->createAssignee();

        $this->userService->expects($this->at(0))->method('getUserById')->with($this->equalTo($reporterId))
            ->will($this->returnValue($reporter));

        $this->userService->expects($this->at(1))->method('getUserById')->with($this->equalTo($assigneeId))
            ->will($this->returnValue($assignee));

        $newStatus = Status::IN_PROGRESS;
        $branch = $this->createBranch();

        $ticket = new Ticket(
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $branch,
            $reporter,
            $assignee,
            Priority::DEFAULT_PRIORITY,
            Source::PHONE,
            Status::NEW_ONE
        );

        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($ticket));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('EDIT'), $this->equalTo($ticket))
            ->will($this->returnValue(true));

        $this->ticketService->updateTicket(
            self::DUMMY_TICKET_ID,
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $reporterId,
            $assigneeId,
            Priority::DEFAULT_PRIORITY,
            Source::PHONE,
            $newStatus
        );

        $this->assertEquals($ticket->getStatus()->getValue(), $newStatus);
    }

    /**
     * @test
     */
    public function thatTicketUpdatesWithAttachments()
    {
        $reporterId = 2;
        $assigneeId = 3;
        $reporter = $this->createReporter();
        $assignee = $this->createAssignee();

        $this->userService->expects($this->at(0))->method('getUserById')->with($this->equalTo($reporterId))
            ->will($this->returnValue($reporter));

        $this->userService->expects($this->at(1))->method('getUserById')->with($this->equalTo($assigneeId))
            ->will($this->returnValue($assignee));

        $newStatus = Status::IN_PROGRESS;
        $branch = $this->createBranch();

        $ticket = new Ticket(
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $branch,
            $reporter,
            $assignee,
            Priority::DEFAULT_PRIORITY,
            Source::PHONE,
            Status::NEW_ONE
        );

        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($ticket));

        $attachmentInputs = $this->attachmentInputs();

        $this->ticketAttachmentService->expects($this->once())->method('createAttachmentsForItHolder')
            ->with($this->equalTo($attachmentInputs), $this->equalTo($ticket));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('EDIT'), $this->equalTo($ticket))
            ->will($this->returnValue(true));

        $this->ticketService->updateTicket(
            self::DUMMY_TICKET_ID,
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $reporterId,
            $assigneeId,
            Priority::DEFAULT_PRIORITY,
            Source::PHONE,
            $newStatus,
            $attachmentInputs
        );

        $this->assertEquals($ticket->getStatus()->getValue(), $newStatus);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Ticket loading failed, ticket not found.
     */
    public function thatAttachmentRetrievingThrowsExceptionWhenTicketDoesNotExists()
    {
        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue(null));

        $this->ticketService->getTicketAttachment(self::DUMMY_TICKET_ID, self::DUMMY_ATTACHMENT_ID);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Attachment loading failed. Ticket has no such attachment.
     */
    public function thatAttachmentRetrievingThrowsExceptionWhenTicketHasNoAttachment()
    {
        $ticket = new Ticket(
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $this->createBranch(),
            $this->createReporter(),
            $this->createAssignee(),
            Priority::DEFAULT_PRIORITY,
            Source::PHONE,
            Status::CLOSED
        );
        $ticket->addAttachment($this->attachment());
        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($ticket));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('VIEW'), $this->equalTo($ticket))
            ->will($this->returnValue(true));

        $this->ticketService->getTicketAttachment(self::DUMMY_TICKET_ID, self::DUMMY_ATTACHMENT_ID);
    }

    /**
     * @test
     */
    public function thatTicketAttachmentRetrieves()
    {
        $attachment = $this->attachment();
        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($this->ticket));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('VIEW'), $this->equalTo($this->ticket))
            ->will($this->returnValue(true));

        $this->ticket->expects($this->once())->method('getAttachment')->with($this->equalTo(self::DUMMY_ATTACHMENT_ID))
            ->will($this->returnValue($attachment));
        $this->ticketService->getTicketAttachment(self::DUMMY_TICKET_ID, self::DUMMY_ATTACHMENT_ID);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Ticket loading failed, ticket not found.
     */
    public function thatAttachmentsAddingThrowsExceptionWhenTicketNotExists()
    {
        $this->ticketService->addAttachmentsForTicket($this->attachmentInputs(), self::DUMMY_TICKET_ID);
    }

    /**
     * @test
     */
    public function thatAttachmentsAddsForTicket()
    {
        $ticket = new Ticket(
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $this->createBranch(),
            $this->createReporter(),
            $this->createAssignee(),
            Priority::DEFAULT_PRIORITY,
            Source::PHONE,
            Status::CLOSED
        );
        $attachmentInputs = $this->attachmentInputs();
        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($ticket));
        $this->ticketAttachmentService->expects($this->once())->method('createAttachmentsForItHolder')
            ->with($this->equalTo($attachmentInputs), $this->equalTo($ticket));
        $this->ticketRepository->expects($this->once())->method('store')->with($this->equalTo($ticket));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('EDIT'), $this->equalTo($ticket))
            ->will($this->returnValue(true));

        $this->ticketService->addAttachmentsForTicket($attachmentInputs, self::DUMMY_TICKET_ID);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Ticket loading failed, ticket not found.
     */
    public function thatAttachmentRemovingThrowsExceptionWhenTicketDoesNotExists()
    {
        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue(null));

        $this->ticketService->removeAttachmentFromTicket(self::DUMMY_TICKET_ID, self::DUMMY_ATTACHMENT_ID);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Attachment loading failed. Ticket has no such attachment.
     */
    public function thatAttachmentRemovingThrowsExceptionWhenTicketHasNoAttachment()
    {
        $ticket = new Ticket(
            self::DUMMY_TICKET_SUBJECT,
            self::DUMMY_TICKET_DESCRIPTION,
            $this->createBranch(),
            $this->createReporter(),
            $this->createAssignee(),
            Priority::DEFAULT_PRIORITY,
            Source::PHONE,
            Status::CLOSED
        );
        $ticket->addAttachment($this->attachment());
        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($ticket));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('EDIT'), $this->equalTo($ticket))
            ->will($this->returnValue(true));

        $this->ticketService->removeAttachmentFromTicket(self::DUMMY_TICKET_ID, self::DUMMY_ATTACHMENT_ID);
    }

    /**
     * @test
     */
    public function thatAttachmentRemovesFromTicket()
    {
        $attachment = $this->attachment();
        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($this->ticket));

        $this->ticket->expects($this->once())->method('getAttachment')->with($this->equalTo(self::DUMMY_ATTACHMENT_ID))
            ->will($this->returnValue($attachment));

        $this->ticket->expects($this->once())->method('removeAttachment')->with($this->equalTo($attachment));

        $this->ticketAttachmentService->expects($this->once())->method('removeAttachmentFromItHolder')
            ->with($this->equalTo($attachment));

        $this->ticketRepository->expects($this->once())->method('store')->with($this->equalTo($this->ticket));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('EDIT'), $this->equalTo($this->ticket))
            ->will($this->returnValue(true));

        $this->ticketService->removeAttachmentFromTicket(self::DUMMY_TICKET_ID, self::DUMMY_ATTACHMENT_ID);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Ticket loading failed, ticket not found.
     */
    public function testUpdateStatusWhenTicketDoesNotExists()
    {
        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue(null));

        $this->ticketService->updateStatus(self::DUMMY_TICKET_ID, self::DUMMY_STATUS);
    }

    /**
     * @test
     */
    public function testUpdateStatus()
    {
        $status = STATUS::NEW_ONE;
        $assignee = $currentUserId = 3;

        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($this->ticket));

        $this->ticket->expects($this->once())->method('updateStatus')->with($status);
        $this->ticketRepository->expects($this->once())->method('store')->with($this->equalTo($this->ticket));

        $this->ticket->expects($this->once())->method('getAssigneeId')->will($this->returnValue($assignee));

        $this->securityFacade
            ->expects($this->any())
            ->method('getLoggedUserId')
            ->will($this->returnValue($currentUserId));

        $this->securityFacade
            ->expects($this->never())
            ->method('isGranted');

        $this->ticketService->updateStatus(self::DUMMY_TICKET_ID, $status);
    }

    public function testUpdateStatusOfTicketAssignedToSomeoneElse()
    {
        $status = STATUS::NEW_ONE;
        $assignee = 3;
        $currentUserId = 2;

        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($this->ticket));

        $this->ticket->expects($this->once())->method('updateStatus')->with($status);
        $this->ticket->expects($this->once())->method('getAssigneeId')->will($this->returnValue($assignee));
        $this->ticketRepository->expects($this->once())->method('store')->with($this->equalTo($this->ticket));

        $this->securityFacade
            ->expects($this->any())
            ->method('getLoggedUserId')
            ->will($this->returnValue($currentUserId));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('EDIT'), $this->equalTo($this->ticket))
            ->will($this->returnValue(true));

        $this->ticketService->updateStatus(self::DUMMY_TICKET_ID, $status, $currentUserId);
    }

    private function createBranch()
    {
        return new Branch('DUMMY_NAME', 'DUMYY_DESC');
    }

    private function createReporter()
    {
        return new User();
    }

    private function createAssignee()
    {
        return new User();
    }

    /**
     * @return Attachment
     */
    private function attachment()
    {
        return new Attachment(new File('filename.ext'));
    }

    /**
     * @return AttachmentInput
     */
    private function attachmentInputs()
    {
        $attachmentInput = new AttachmentInput();
        $attachmentInput->setFilename(self::DUMMY_FILENAME);
        $attachmentInput->setContent(self::DUMMY_FILE_CONTENT);
        return array($attachmentInput);
    }

    public function testAssignTicket()
    {
        $assigneeId = $currentUserId = 3;
        $assignee = $this->createAssignee();

        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($this->ticket));

        $this->userService->expects($this->at(0))->method('getUserById')->with($this->equalTo($assigneeId))
            ->will($this->returnValue($assignee));

        $this->ticket->expects($this->any())->method('getAssigneeId')->will($this->returnValue($assigneeId));
        $this->ticket->expects($this->once())->method('assign')->with($assignee);
        $this->ticketRepository->expects($this->once())->method('store')->with($this->equalTo($this->ticket));

        $this->securityFacade
            ->expects($this->any())
            ->method('getLoggedUserId')
            ->will($this->returnValue($currentUserId));

        $this->securityFacade
            ->expects($this->never())
            ->method('isGranted');

        $this->ticketService->assignTicket(self::DUMMY_TICKET_ID, $assigneeId);
    }

    public function testAssignTicketOfTicketAssignedToSomeoneElse()
    {
        $currentUserId = 2;
        $assigneeId = 3;
        $assignee = $this->createAssignee();

        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($this->ticket));

        $this->userService->expects($this->at(0))->method('getUserById')->with($this->equalTo($assigneeId))
            ->will($this->returnValue($assignee));

        $this->ticket->expects($this->any())->method('getAssigneeId')->will($this->returnValue($assigneeId));
        $this->ticket->expects($this->once())->method('assign')->with($assignee);
        $this->ticketRepository->expects($this->once())->method('store')->with($this->equalTo($this->ticket));

        $this->securityFacade
            ->expects($this->any())
            ->method('getLoggedUserId')
            ->will($this->returnValue($currentUserId));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('EDIT'), $this->equalTo($this->ticket))
            ->will($this->returnValue(true));

        $this->ticketService->assignTicket(self::DUMMY_TICKET_ID, $assigneeId);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Ticket loading failed, ticket not found.
     */
    public function testAssignTicketWhenTicketDoesNotExist()
    {
        $assigneeId = 3;

        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue(null));

        $this->ticketService->assignTicket(self::DUMMY_TICKET_ID, $assigneeId);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Assignee loading failed, assignee not found.
     */
    public function testAssignTicketWhenAssigneeDoesNotExist()
    {
        $currentUserId = 2;
        $assigneeId = 3;

        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($this->ticket));

        $this->securityFacade
            ->expects($this->any())
            ->method('getLoggedUserId')
            ->will($this->returnValue($currentUserId));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('EDIT'), $this->equalTo($this->ticket))
            ->will($this->returnValue(true));

        $this->userService->expects($this->at(0))->method('getUserById')->with($this->equalTo($assigneeId))
            ->will($this->returnValue(null));

        $this->ticketService->assignTicket(self::DUMMY_TICKET_ID, $assigneeId);
    }

    public function testDeleteTicket()
    {
        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue($this->ticket));

        $this->ticketRepository->expects($this->once())->method('remove')->with($this->equalTo($this->ticket));

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('DELETE'), $this->equalTo($this->ticket))
            ->will($this->returnValue(true));

        $this->ticketService->deleteTicket(self::DUMMY_TICKET_ID);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Ticket loading failed, ticket not found.
     */
    public function testDeleteTicketWhenTicketDoesNotExist()
    {
        $this->ticketRepository->expects($this->once())->method('get')->with($this->equalTo(self::DUMMY_TICKET_ID))
            ->will($this->returnValue(null));

        $this->ticketService->deleteTicket(self::DUMMY_TICKET_ID);
    }
}
