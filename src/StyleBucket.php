<?php


namespace Icosillion\Stamper;


class StyleBucket
{
    /** @var string[] */
    private array $style = [];

    public function setStyle(string $key, string $style): void {
        $this->style[$key] = $style;
    }

    public function buildStyleSheet(): string {
        $output = "";
        foreach ($this->style as $key => $fragment) {
            $output .= $fragment . "\n";
        }

        return $output;
    }
}
