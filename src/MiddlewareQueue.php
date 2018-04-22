<?php
/**
 * MiddlewareQueue
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);

namespace Apine\Dispatcher;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class MiddlewareQueue
 *
 * @package Apine\Core\Middlewares
 */
class MiddlewareQueue implements RequestHandlerInterface
{
    /**
     * @var bool
     */
    private $locked = false;

    /**
     * @var MiddlewareInterface[]
     */
    private $queue = [];

    /**
     * @var RequestHandlerInterface
     */
    private $fallback;

    /**
     * Queue constructor.
     *
     * @param RequestHandlerInterface $fallback
     * @param MiddlewareInterface[]   $middlewares
     */
    public function __construct(RequestHandlerInterface $fallback, array $middlewares = [])
    {
        if (is_null($fallback)) {
            throw new InvalidArgumentException("Fallback must be set to an implementation of RequestHandlerInterface");
        }

        $this->fallback = $fallback;

        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof MiddlewareInterface) {
                throw new InvalidArgumentException("Array must only contain implementations of MiddlewareInterface");
            }

            $this->queue[] = $middleware;
        }
    }

    /**
     * @param MiddlewareInterface[] $middlewares
     * @throws MiddlewareQueueException
     */
    public function seedQueue(array $middlewares) : void
    {
        if ($this->locked) {
            throw new MiddlewareQueueException("Cannot add middlewares once the stack is dequeueing");
        }

        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }
    }

    /**
     * @param MiddlewareInterface $middleware
     * @throws MiddlewareQueueException
     */
    public function add(MiddlewareInterface $middleware) : void
    {
        if ($this->locked) {
            throw new MiddlewareQueueException("Cannot add middleware once the stack is dequeueing");
        }

        $this->queue[] = $middleware;
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
        $this->locked = true;

        if (count($this->queue) === 0) {
            return $this->fallback->handle($request);
        }

        $middleware = array_shift($this->queue);
        return $middleware->process($request, $this);
    }
}