<?php
/*
 * This file is part of the prooph/service-bus.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 21.04.14 - 01:15
 */

namespace Prooph\ServiceBusTest\Mock;

use Prooph\EventSourcing\DomainEvent\AggregateChangedEvent;

/**
 * Class UserCreated
 *
 * @package Prooph\ServiceBusTest\Mock
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class UserCreated extends AggregateChangedEvent
{
    public function getName()
    {
        return $this->toPayloadReader()->stringValue("name");
    }
}
 