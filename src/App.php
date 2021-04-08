<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Icosillion\Stamper\Stamper;

$stamper = new Stamper();
$stamper->registerComponent('warning', __DIR__ . '/../templates/warning.html');
$stamper->registerComponent('footer', __DIR__ . '/../templates/footer.html');
$stamper->registerComponent('obj', __DIR__ . '/../templates/object.html');

class Obj {
    public function getId() {
        return "id1234";
    }
}

$output = $stamper->render(__DIR__ . '/../templates/component.html', [
    'person' => [
        'name' => 'Alex',
        'age' => 25,
        'hobbies' => [
            'programming',
            'art'
        ]
    ],
    'object' => new Obj()
]);

echo $output['html'];

echo $output['stylesheet'];

var_dump($output['globals']);
