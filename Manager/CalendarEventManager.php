<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\CoreBundle\Entity\Calendar\Event;

/**
 * @DI\Service("claroline.manager.calendar_event_manager")
 */
class CalendarEventManager 
{

    /**
     * @DI\InjectParams({
     *      "om"   = @DI\Inject("claroline.persistence.object_manager")
     * })
     */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    public function create(Event $event)
    {
        $this->om->persist($event);
        $this->om->flush();
    }

    public function delete(Event $event)
    {
        $this->om->remove($event);
        $this->om->flush();
    }

    public function edit(Event $event)
    {
        $this->om->persist($event);
        $this->om->flush();
    }
}
