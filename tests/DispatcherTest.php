<?php
/**
 * DispatcherTest
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);


use Apine\Dispatcher\Dispatcher;
use Apine\Dispatcher\MiddlewareQueueException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DispatcherTest extends TestCase
{
    
    
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
     */
    public function testWithMiddleware(Dispatcher $dispatcher)
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
     */
    public function testWithMiddlewareQueue(Dispatcher $dispatcher)
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
     * @expectedException \Apine\Core\Dispatcher\MiddlewareQueueException
     */
    public function testWithMiddlewareWhenQueueLocked(Dispatcher $dispatcher)
    {
        $this->setProtectedProperty($dispatcher, 'locked', true);
        
        $mockMiddleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->setMethods(['process'])
            ->getMock();
    
        $newDispatcher = $dispatcher->withMiddleware($mockMiddleware);
    }
    
    /**
     * @depends testConstructor
     * @expectedException \Apine\Core\Dispatcher\MiddlewareQueueException
     */
    public function testWithMiddlewareQueueWhenQueueLocked(Dispatcher $dispatcher)
    {
        $this->setProtectedProperty($dispatcher, 'locked', true);
        
        $mockMiddleware = $this->getMockBuilder(MiddlewareInterface::class)
            ->setMethods(['process'])
            ->getMock();
        
        $newDispatcher = $dispatcher->withMiddlewareQueue([$mockMiddleware]);
    }
    
    /**
     * @depends testConstructor
     */
    public function testHandle(Dispatcher $dispatcher)
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
     */
    public function testHandleWhenQueueEmpty(Dispatcher $dispatcher)
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
    public function setProtectedProperty($object, $property, $value)
    {
        $reflection = new ReflectionClass($object);
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($object, $value);
    }
}
