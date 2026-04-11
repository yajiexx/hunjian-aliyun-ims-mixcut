<?php

require __DIR__ . '/bootstrap.php';

$suite = new \Hunjian\AliyunImsMixcut\Tests\SmokeTest();
$results = $suite->run();

foreach ($results as $name => $passed) {
    echo sprintf("[%s] %s\n", $passed ? 'PASS' : 'FAIL', $name);
}
