<?php


namespace Icosillion\Stamper;


use Gt\Dom\Document;

class State
{
    public array $context;

    public Document $doc;

    public StyleBucket $styleBucket;

    public function __construct(array $context, Document $doc, StyleBucket $styleBucket)
    {
        $this->context = $context;
        $this->doc = $doc;
        $this->styleBucket = $styleBucket;
    }

    public function withAdditionalContext(array $context): State {
        return new State(
            array_merge($this->context, $context),
            $this->doc,
            $this->styleBucket
        );
    }
}