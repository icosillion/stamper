<?php

declare(strict_types=1);

namespace Icosillion\Stamper\Tokenizer;

class StringBuffer
{
    /**
     * @var string
     */
    private $data = '';

    public function push(string $string): void
    {
        $this->data .= $string;
    }

    public function clear(): void
    {
        $this->data = '';
    }

    public function get(): string
    {
        return $this->data;
    }

    public function getAndClear(): string
    {
        $output = $this->get();
        $this->clear();

        return $output;
    }

    public function isEmpty(): bool
    {
        return $this->data === '';
    }
}