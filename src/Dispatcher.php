<?php
/**
 * Dispatcher
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);

namespace Apine\Dispatcher;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;


/**
 * Class Dispatcher
 *
 * @package Apine\Core\Middlewares
 */
class Dispatcher implements RequestHandlerInterface
{
    /**
     * @var MiddlewareQueue
     */
    private $queue;
    
    /**
     * @var RequestHandlerInterface
     */
    private $fallback;
    
    /**
     * @var bool
     */
    private $locked = false;
    
    /**
     * Dispatcher constructor.
     *
     * @param RequestHandlerInterface $fallback
     */
    public function __construct(RequestHandlerInterface $fallback)
    {
        $this->fallback = $fallback;
        $this->queue = new MiddlewareQueue();
    }
    
    /**
     * @param MiddlewareInterface[] $middlewares
     *
     * @return Dispatcher
     * @throws MiddlewareQueueException
     */
    public function withMiddlewareQueue(array $middlewares): self
    {
        if ($this->locked) {
            throw new MiddlewareQueueException('Cannot add middlewares once the stack is dequeueing');
        }
        
        $clone = clone $this;
        
        foreach ($middlewares as $middleware) {
            $clone = $clone->withMiddleware($middleware);
        }
        
        return $clone;
    }
    
    /**
     * @param MiddlewareInterface $middleware
     *
     * @return Dispatcher
     * @throws MiddlewareQueueException
     */
    public function withMiddleware(MiddlewareInterface $middleware): self
    {
        if ($this->locked) {
            throw new MiddlewareQueueException('Cannot add middleware once the stack is dequeueing');
        }
        
        $cloneQueue = clone $this->queue;
        $cloneQueue->add($middleware);
    
        $clone = clone $this;
        $clone->queue = $cloneQueue;
        
        return $clone;
    }
    
    /**
     * Handle the request and return a response.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dispatcher = clone $this;
        $dispatcher->locked = true;
        $middleware = $dispatcher->queue->next();
        
        if (null === $middleware) {
            return $this->fallback->handle($request);
        }
        
        return $middleware->process($request, $dispatcher);
    }
}