<?php


namespace Icosillion\Stamper;


class GlobalsBucket
{
    private array $globals = [];
    
    public function pushGlobals(array $globals)
    {
        foreach ($globals as $key => $value) {
            $this->globals[$key] = $value;
        }
    }
    
    public function getGlobals(): array
    {
        return $this->globals;
    }
}
