<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\LogBundle\Messenger\Functional\Message;

class OpenToolMessage implements FunctionalMessageInterface
{
    public const EVENT_NAME = 'tool_open';

    private $message;
    private $userId;
    private $workspaceId;

    public function __construct(
        string $message,
        int $userId,
        ?int $workspaceId = null
    ) {
        $this->message = $message;
        $this->userId = $userId;
        $this->workspaceId = $workspaceId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getEventName(): string
    {
        return self::EVENT_NAME;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getWorkspaceId(): ?int
    {
        return $this->workspaceId;
    }
}
