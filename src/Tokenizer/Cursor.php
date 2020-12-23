<?php

namespace Icosillion\Stamper\Tokenizer;

class Cursor
{
    private $data;

    private $pointer = 0;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function current(): ?string
    {
        if (!$this->isInBounds()) {
            return null;
        }

        return $this->data[$this->pointer];
    }

    public function next(): ?string
    {
        $this->pointer++;

        if (!$this->isInBounds()) {
            return null;
        }

        return $this->current();
    }

    public function prev(): ?string
    {
        $this->pointer--;

        if (!$this->isInBounds()) {
            return null;
        }

        return $this->current();
    }

    public function peek($offset = 1): ?string
    {
        if (!$this->isInBounds($this->pointer + $offset)) {
            return null;
        }

        return $this->data[$this->pointer + $offset];
    }

    public function skip($offset = 1): void
    {
        $this->pointer += $offset;
    }

    public function isInBounds($localPointer = null): bool
    {
        if ($localPointer === null) {
            $localPointer = $this->pointer;
        }

        return $localPointer >= 0 && $localPointer < \strlen($this->data);
    }
}