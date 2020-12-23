<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Icosillion\Stamper\Stamper;

$stamper = new Stamper();
$stamper->registerComponent('warning', __DIR__ . '/../templates/warning.html');

$output = $stamper->render(__DIR__ . '/../templates/component.html', [
    'person' => [
        'name' => 'Alex',
        'age' => 25,
        'hobbies' => [
            'programming',
            'art'
        ]
    ],
    'test' => 'hello'
]);

echo $output['html'];