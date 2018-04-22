<?php
/**
 * Dispatcher
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);

namespace Apine\Dispatcher;

use InvalidArgumentException;
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
     * @var MiddlewareInterface[]
     */
    private $queue = [];
    
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
        if (is_null($fallback)) {
            throw new InvalidArgumentException("Fallback must be set to an implementation of RequestHandlerInterface");
        }

        $this->fallback = $fallback;
    }
    
    /**
     * @param MiddlewareInterface[] $middlewares
     *
     * @return Dispatcher
     * @throws MiddlewareQueueException
     */
    public function withMiddlewareQueue(array $middlewares) : self
    {
        if ($this->locked) {
            throw new MiddlewareQueueException("Cannot add middlewares once the stack is dequeueing");
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
    public function withMiddleware(MiddlewareInterface $middleware) : self
    {
        if ($this->locked) {
            throw new MiddlewareQueueException("Cannot add middleware once the stack is dequeueing");
        }
        
        $clone = clone $this;
        $clone->queue[] = $middleware;
        
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
        if (count($this->queue) === 0) {
            return $this->fallback->handle($request);
        }
        
        $dispatcher = clone $this;
        $dispatcher->locked = true;
        $middleware = array_shift($dispatcher->queue);
        return $middleware->process($request, $dispatcher);
    }
}