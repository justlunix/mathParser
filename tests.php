<?php

require_once 'MathParser.php';

// key   = operation
// value = correct result
$tests = [
    '2+2' => 4.0,
    '5*5' => 25.0,
    '3+5*6' => 33.0,
    '5*(3-1)' => 10.0,
    '100-(4*2*(2+2))' => 68.0,
    '100-(4/2*(2^2))' => 92.0,
];

$mathParser = new MathParser();

foreach ($tests as $operation => $correctResult) {
    $result = $mathParser->evaluate($operation);

    echo "$operation = $result (" . ($result === $correctResult ? 'correct' : 'wrong') . ")" . PHP_EOL;
}