<?php

declare(strict_types=1);

namespace Icosillion\Stamper\Tokenizer;

class StateManager
{
    private $state = ['base'];

    private $handlers = [];

    /**
     * @return string
     * @throws \OutOfBoundsException
     */
    public function pop(): string
    {
        if ($this->state === []) {
            throw new \OutOfBoundsException('Stack Underflow: State stack is empty');
        }

        $poppedState = \array_pop($this->state);
        $this->stateChanged('pop', $poppedState);

        return $poppedState;
    }

    /**
     * @param string $state
     * @return string
     */
    public function push(string $state): string
    {
        $this->state[] = $state;
        $this->stateChanged('push', $state);

        return $state;
    }

    /**
     * @return string
     */
    public function current(): string
    {
        return \end($this->state);
    }

    /**
     * @param string $state
     * @return bool
     */
    public function is(string $state): bool
    {
        return $this->current() === $state;
    }

    public function addChangeHandler(callable $handler)
    {
        $this->handlers[] = $handler;
    }

    private function stateChanged(string $operation, string $state)
    {
        $manager = $this;
        \array_map(function (callable $handler) use ($operation, $state, $manager) {
            $handler($operation, $state, $manager);
        }, $this->handlers);
    }
}