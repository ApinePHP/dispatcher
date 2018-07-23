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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function count;

/**
 * Class MiddlewareQueue
 *
 * @package Apine\Core\Middlewares
 */
class MiddlewareQueue
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
     * Queue constructor.
     *
     * @param RequestHandlerInterface $fallback
     * @param MiddlewareInterface[]   $middlewares
     */
    public function __construct(array $middlewares = [])
    {
        
        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof MiddlewareInterface) {
                throw new InvalidArgumentException('Array must only contain implementations of MiddlewareInterface');
            }
            
            $this->queue[] = $middleware;
        }
    }
    
    /**
     * @param MiddlewareInterface[] $middlewares
     *
     * @throws MiddlewareQueueException
     */
    public function seedQueue(array $middlewares): void
    {
        if ($this->locked) {
            throw new MiddlewareQueueException('Cannot add middlewares once the stack is dequeueing');
        }
        
        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }
    }
    
    /**
     * @return array
     */
    public function getQueue(): array
    {
        return $this->queue;
    }
    
    /**
     * @param MiddlewareInterface $middleware
     *
     * @throws MiddlewareQueueException
     */
    public function add(MiddlewareInterface $middleware): void
    {
        if ($this->locked) {
            throw new MiddlewareQueueException('Cannot add middleware once the stack is dequeueing');
        }
        
        $this->queue[] = $middleware;
    }
    
    /**
     * @return null|MiddlewareInterface
     */
    public function next(): ?MiddlewareInterface
    {
        $this->locked = true;
        
        if (count($this->queue) === 0) {
            return null;
        }
        
        return array_shift($this->queue);
    }
}