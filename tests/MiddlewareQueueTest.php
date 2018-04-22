<?php
/**
 * MiddlewareQueueTest
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */

/** @noinspection PhpParamsInspection */

declare(strict_types=1);

use Apine\Dispatcher\MiddlewareQueue;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareQueueTest extends TestCase
{
    /**
     * @return MiddlewareQueue
     * @throws ReflectionException
     */
    public function testConstructor() : MiddlewareQueue
    {
        $mockHandler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMock();
    
        $mockHandler->method('handle')->willReturn($this->createMock(ResponseInterface::class));

        $dispatcher = new MiddlewareQueue($mockHandler);
    
        $this->assertAttributeEquals(false, 'locked', $dispatcher);
        $this->assertAttributeNotEmpty('fallback', $dispatcher);
        $this->assertAttributeEmpty('queue', $dispatcher);
    
        return $dispatcher;
    }

    /**
     * @return MiddlewareQueue
     * @throws ReflectionException
     */
    public function testConstructorWithQueue() : MiddlewareQueue
    {
        $mockHandler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMock();
    
        $mockHandler->method('handle')->willReturn($this->createMock(ResponseInterface::class));
    
        $mockMiddleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->setMethods(['process'])
            ->getMock();
    
        $mockMiddleware->method('process')->willReturn($this->createMock(ResponseInterface::class));
    
        $dispatcher = new MiddlewareQueue($mockHandler, [$mockMiddleware]);
        $this->assertAttributeNotEmpty('queue', $dispatcher);
        
        return $dispatcher;
    }

    /**
     * @depends testConstructor
     * @param MiddlewareQueue $dispatcher
     * @throws ReflectionException
     * @throws \Apine\Dispatcher\MiddlewareQueueException
     */
    public function testAdd(MiddlewareQueue $dispatcher)
    {
        $dispatcher = clone $dispatcher;
        $mockMiddleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->setMethods(['process'])
            ->getMock();
    
        $mockMiddleware->method('process')->willReturn($this->createMock(ResponseInterface::class));
    
        $this->assertAttributeEmpty('queue', $dispatcher);
        $dispatcher->add($mockMiddleware);
        $this->assertAttributeNotEmpty('queue', $dispatcher);
    }

    /**
     * @depends testConstructor
     * @param MiddlewareQueue $dispatcher
     * @throws ReflectionException
     * @throws \Apine\Dispatcher\MiddlewareQueueException
     */
    public function testSeedQueue(MiddlewareQueue $dispatcher)
    {
        $dispatcher = clone $dispatcher;
        $mockMiddleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->setMethods(['process'])
            ->getMock();
    
        $mockMiddleware->method('process')->willReturn($this->createMock(ResponseInterface::class));
    
        $this->assertAttributeEmpty('queue', $dispatcher);
        $dispatcher->seedQueue([$mockMiddleware]);
        $this->assertAttributeNotEmpty('queue', $dispatcher);
    }

    /**
     * @depends testConstructor
     * @expectedException \Apine\Dispatcher\MiddlewareQueueException
     * @param MiddlewareQueue $dispatcher
     * @throws ReflectionException
     * @throws \Apine\Dispatcher\MiddlewareQueueException
     */
    public function testAddWhenQueueLocked(MiddlewareQueue $dispatcher)
    {
        $dispatcher = clone $dispatcher;
        $this->setProtectedProperty($dispatcher, 'locked', true);
        
        $mockMiddleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->setMethods(['process'])
            ->getMock();
        
        $dispatcher->add($mockMiddleware);
    }

    /**
     * @depends testConstructor
     * @expectedException \Apine\Dispatcher\MiddlewareQueueException
     * @param MiddlewareQueue $dispatcher
     * @throws ReflectionException
     * @throws \Apine\Dispatcher\MiddlewareQueueException
     */
    public function testWithMiddlewareQueueWhenQueueLocked(MiddlewareQueue $dispatcher)
    {
        $dispatcher = clone $dispatcher;
        $this->setProtectedProperty($dispatcher, 'locked', true);
        
        $mockMiddleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->setMethods(['process'])
            ->getMock();
        
        $dispatcher->seedQueue([$mockMiddleware]);
    }

    /**
     * @depends testConstructorWithQueue
     * @param MiddlewareQueue $dispatcher
     * @throws ReflectionException
     */
    public function testHandle(MiddlewareQueue $dispatcher)
    {
        $dispatcher = clone $dispatcher;
        $mockRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $response = $dispatcher->handle($mockRequest);
    
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @depends testConstructor
     * @param MiddlewareQueue $dispatcher
     * @throws ReflectionException
     */
    public function testHandleWhenQueueEmpty(MiddlewareQueue $dispatcher)
    {
        $dispatcher = clone $dispatcher;
        $mockRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $response = $dispatcher->handle($mockRequest);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
    
    /**
     * Sets a protected property on a given object via reflection
     *
     * @param $object - instance in which protected value is being modified
     * @param $property - property on instance being modified
     * @param $value - new value of the property being modified
     *
     * @return void
     * @throws \ReflectionException
     */
    public function setProtectedProperty($object, $property, $value)
    {
        $reflection = new ReflectionClass($object);
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($object, $value);
    }
}
