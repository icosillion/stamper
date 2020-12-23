<?php

declare(strict_types=1);

namespace Icosillion\Stamper;

class Context
{
    /**
     * @var Context|null
     */
    private $parent;

    /**
     * @var Context[][]
     */
    private $properties;

    public function __construct(?Context $parent = null, array $properties = [])
    {
        $this->parent = $parent;
        $this->properties = $properties;
    }

    /**
     * @return Context|null
     */
    public function getParent(): ?Context
    {
        return $this->parent;
    }

    /**
     * @param Context|null $parent
     * @return Context
     */
    public function setParent(?Context $parent): Context
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getProperty(string $name)
    {
        if (!\array_key_exists($name, $this->properties)) {
            if ($this->getParent() !== null) {
                return null;
            }

            return $this->getParent()->getProperty($name);
        }

        return $this->properties[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return Context
     */
    public function setProperty(string $name, $value): Context
    {
        $this->properties[$name] = $value;
        return $this;
    }
}