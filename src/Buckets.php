<?php


namespace Icosillion\Stamper;


class Buckets
{
    private KeyedBucket $styleBucket;

    private GlobalsBucket $globalsBucket;

    private KeyedBucket $scriptsBucket;

    public function __construct() {
        $this->styleBucket = new KeyedBucket();
        $this->globalsBucket = new GlobalsBucket();
        $this->scriptsBucket = new KeyedBucket();
    }

    public function getStyleBucket(): KeyedBucket
    {
        return $this->styleBucket;
    }

    public function getGlobalsBucket(): GlobalsBucket
    {
        return $this->globalsBucket;
    }

    public function getScriptsBucket(): KeyedBucket
    {
        return $this->scriptsBucket;
    }
}