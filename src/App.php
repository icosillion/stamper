<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Icosillion\Stamper\Interpo;
use Icosillion\Stamper\Stamper;

$stamper = new Stamper();

$output = $stamper->render(__DIR__ . '/../templates/component.html', [
    'person' => [
        'name' => 'Alex',
        'age' => 25,
        'hobbies' => [
            'programming',
            'art'
        ]
    ]
]);

echo $output;