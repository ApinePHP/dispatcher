<?php
/**
 * DispatcherTest
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */

/** @noinspection PhpUnusedLocalVariableInspection */
/** @noinspection PhpParamsInspection */

declare(strict_types=1);

use Apine\Dispatcher\Dispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DispatcherTest extends TestCase
{
    /**
     * @return Dispatcher
     */
    public function testConstructor() : Dispatcher
    {
        $mockHandler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->setMethods(['handle'])
            ->getMock();
        
        $mockHandler->method('handle')->willReturn($this->createMock(ResponseInterface::class));
        
        $dispatcher = new Dispatcher($mockHandler);
        
        $this->assertAttributeEquals(false, 'locked', $dispatcher);
        $this->assertAttributeNotEmpty('fallback', $dispatcher);
        
        return $dispatcher;
    }

    /**
     * @depends testConstructor
     * @param Dispatcher $dispatcher
     * @throws \Apine\Dispatcher\MiddlewareQueueException
     */
    public function testWithMiddleware(Dispatcher $dispatcher): void
    {
        $mockMiddleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->setMethods(['process'])
            ->getMock();
        
        $mockMiddleware->method('process')->willReturn($this->createMock(ResponseInterface::class));
        
        $newDispatcher = $dispatcher->withMiddleware($mockMiddleware);
        
        $this->assertInstanceOf(Dispatcher::class, $newDispatcher);
        $this->assertAttributeEmpty('queue', $dispatcher);
        $this->assertAttributeNotEmpty('queue', $newDispatcher);
    }

    /**
     * @depends testConstructor
     * @param Dispatcher $dispatcher
     * @throws \Apine\Dispatcher\MiddlewareQueueException
     */
    public function testWithMiddlewareQueue(Dispatcher $dispatcher): void
    {
        $mockMiddleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->setMethods(['process'])
            ->getMock();
    
        $mockMiddleware->method('process')->willReturn($this->createMock(ResponseInterface::class));
    
        $newDispatcher = $dispatcher->withMiddlewareQueue([$mockMiddleware]);
    
        $this->assertInstanceOf(Dispatcher::class, $newDispatcher);
        $this->assertAttributeEmpty('queue', $dispatcher);
        $this->assertAttributeNotEmpty('queue', $newDispatcher);
    }

    /**
     * @depends testConstructor
     * @expectedException \Apine\Dispatcher\MiddlewareQueueException
     * @param Dispatcher $dispatcher
     * @throws ReflectionException
     * @throws \Apine\Dispatcher\MiddlewareQueueException
     */
    public function testWithMiddlewareWhenQueueLocked(Dispatcher $dispatcher): void
    {
        $this->setProtectedProperty($dispatcher, 'locked', true);
        
        $mockMiddleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->setMethods(['process'])
            ->getMock();
    
        $newDispatcher = $dispatcher->withMiddleware($mockMiddleware);
    }

    /**
     * @depends testConstructor
     * @expectedException \Apine\Dispatcher\MiddlewareQueueException
     * @param Dispatcher $dispatcher
     * @throws ReflectionException
     * @throws \Apine\Dispatcher\MiddlewareQueueException
     */
    public function testWithMiddlewareQueueWhenQueueLocked(Dispatcher $dispatcher): void
    {
        $this->setProtectedProperty($dispatcher, 'locked', true);
        
        $mockMiddleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->setMethods(['process'])
            ->getMock();
        
        $newDispatcher = $dispatcher->withMiddlewareQueue([$mockMiddleware]);
    }

    /**
     * @depends testConstructor
     * @param Dispatcher $dispatcher
     * @throws ReflectionException
     * @throws \Apine\Dispatcher\MiddlewareQueueException
     */
    public function testHandle(Dispatcher $dispatcher): void
    {
        $this->setProtectedProperty($dispatcher, 'locked', false);
        $mockRequest = $this->getMockForAbstractClass(ServerRequestInterface::class);
        $mockMiddleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->setMethods(['process'])
            ->getMock();
    
        $mockMiddleware->method('process')->willReturn($this->createMock(ResponseInterface::class));
    
        $newDispatcher = $dispatcher->withMiddleware($mockMiddleware);
        $response = $newDispatcher->handle($mockRequest);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @depends testConstructor
     * @param Dispatcher $dispatcher
     * @throws ReflectionException
     */
    public function testHandleWhenQueueEmpty(Dispatcher $dispatcher): void
    {
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
    public function setProtectedProperty($object, $property, $value): void
    {
        $reflection = new ReflectionClass($object);
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($object, $value);
    }
}
