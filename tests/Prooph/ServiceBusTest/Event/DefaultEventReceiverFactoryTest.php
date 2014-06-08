<?php
/*
 * This file is part of the prooph/php-service-bus.
 * (c) Alexander Miertsch <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 11.03.14 - 22:25
 */

namespace Prooph\ServiceBusTest\Event;

use Prooph\ServiceBus\Event\DefaultEventReceiverFactory;
use Prooph\ServiceBus\Event\EventFactory;
use Prooph\ServiceBus\Message\MessageHeader;
use Prooph\ServiceBus\Message\StandardMessage;
use Prooph\ServiceBus\Service\Definition;
use Prooph\ServiceBus\Service\EventReceiverManager;
use Prooph\ServiceBus\Service\InvokeStrategyManager;
use Prooph\ServiceBus\Service\ServiceBusManager;
use Prooph\ServiceBusTest\Mock\SomethingDoneHandler;
use Prooph\ServiceBusTest\Mock\SomethingDoneInvokeStrategy;
use Prooph\ServiceBusTest\TestCase;
use Rhumsaa\Uuid\Uuid;

/**
 * Class DefaultEventReceiverFactoryTest
 *
 * @package Prooph\ServiceBusTest\Event
 * @author Alexander Miertsch <contact@prooph.de>
 */
class DefaultEventReceiverFactoryTest extends TestCase
{
    /**
     * @var ServiceBusManager
     */
    private $serviceBusManager;

    /**
     * @var SomethingDoneHandler
     */
    private $somethingDoneHandler;

    /**
     * @var EventReceiverManager
     */
    private $eventReceiverManager;

    protected function setUp()
    {
        $this->serviceBusManager = new ServiceBusManager();

        $config = array(
            Definition::CONFIG_ROOT => array(
                Definition::EVENT_BUS => array(
                    //name of the bus, must match with the Message.header.sender
                    'test-case-bus' => array(
                        Definition::EVENT_MAP => array(
                            //SomethingDone event is mapped to the SomethingDoneHandler alias
                            'Prooph\ServiceBusTest\Mock\SomethingDone' => 'something_done_handler'
                        )
                    )
                ),
                Definition::EVENT_HANDLER_INVOKE_STRATEGIES => array(
                    //Alias of the SomethingDoneInvokeStrategy
                    'something_done_invoke_strategy'
                )
            )
        );

        //Add global config as service
        $this->serviceBusManager->setService('configuration', $config);

        //Should handle the SomethingDone event
        $this->somethingDoneHandler = new SomethingDoneHandler();

        //Register SomethingDoneHandler as Service
        $this->serviceBusManager->setService('something_done_handler', $this->somethingDoneHandler);

        $invokeStrategyManager = new InvokeStrategyManager();

        //Register DoSomethingInvokeStrategy as Service
        $invokeStrategyManager->setService('something_done_invoke_strategy', new SomethingDoneInvokeStrategy());

        $this->serviceBusManager->setAllowOverride(true);

        //Register InvokeStrategyManager as Service
        $this->serviceBusManager->setService(Definition::INVOKE_STRATEGY_MANAGER, $invokeStrategyManager);

        //Register EventFactory as Service, this is not necessary but we do it for testing purposes
        $this->serviceBusManager->setService(Definition::EVENT_FACTORY, new EventFactory());

        $this->eventReceiverManager = new EventReceiverManager();

        //Set MainServiceManager as ServiceLocator for the CommandReceiverManager
        $this->eventReceiverManager->setServiceLocator($this->serviceBusManager);
    }

    /**
     * @test
     */
    public function it_can_create_an_event_receiver()
    {
        $defaultEventReceiverFactory = new DefaultEventReceiverFactory();

        $this->assertTrue(
            $defaultEventReceiverFactory
                ->canCreateServiceWithName($this->eventReceiverManager, 'testcasebus', 'test-case-bus')
        );
    }

    /**
     * @test
     */
    public function it_creates_a_fully_configured_event_receiver()
    {
        $defaultEventReceiverFactory = new DefaultEventReceiverFactory();

        $eventReceiver = $defaultEventReceiverFactory
            ->createServiceWithName($this->eventReceiverManager, 'testcasebus', 'test-case-bus');

        $this->assertSame(
            $this->serviceBusManager->get(Definition::EVENT_FACTORY),
            $eventReceiver->getEventFactory()
        );

        $this->assertSame(
            $this->serviceBusManager->get(Definition::INVOKE_STRATEGY_MANAGER),
            $eventReceiver->getInvokeStrategyManager()
        );

        $this->assertEquals(
            array('something_done_invoke_strategy'),
            $eventReceiver->getInvokeStrategies()
        );

        $message = new StandardMessage(
            'Prooph\ServiceBusTest\Mock\SomethingDone',
            new MessageHeader(Uuid::uuid4(), new \DateTime(), 1, 'test-case-bus', MessageHeader::TYPE_EVENT),
            array('data' => 'test payload')
        );

        $eventReceiver->handle($message);

        $this->assertEquals('test payload', $this->somethingDoneHandler->lastEvent()->data());
    }
}
 