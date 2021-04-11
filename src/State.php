<?php


namespace Icosillion\Stamper;


use Gt\Dom\Document;

class State
{
    public array $context;

    public Document $doc;

    public Buckets $buckets;

    public function __construct(array $context, Document $doc, Buckets $buckets)
    {
        $this->context = $context;
        $this->doc = $doc;
        $this->buckets = $buckets;
    }

    public function withAdditionalContext(array $context): State {
        return new State(
            array_merge($this->context, $context),
            $this->doc,
            $this->buckets
        );
    }
}