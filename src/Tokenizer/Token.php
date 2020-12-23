<?php

declare(strict_types=1);

namespace Icosillion\Stamper\Tokenizer;

class Token
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $value;

    /**
     * @var string[]
     */
    private $properties = [];

    /**
     * @param string $type
     * @param string|null $value
     */
    public function __construct(string $type, ?string $value = null)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getProperty(string $name)
    {
        if (!\array_key_exists($name, $this->properties)) {
            return null;
        }

        return $this->properties[$name];
    }

    public function setProperty(string $name, $value): void
    {
        $this->properties[$name] = $value;
    }
}