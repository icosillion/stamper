<?php


namespace Icosillion\Stamper;


class KeyedBucket
{
    /** @var string[] */
    private array $fragments = [];

    public function setFragment(string $key, string $style): void {
        $this->fragments[$key] = $style;
    }

    public function build(): string {
        $output = "";
        foreach ($this->fragments as $key => $fragment) {
            $output .= $fragment . "\n";
        }

        return $output;
    }
}