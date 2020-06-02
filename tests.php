<?php

require_once 'MathParser.php';

// key   = operation
// value = correct result
$tests = [
    '2+2' => 4.0,
    '5*5' => 25.0,
    '3+5*6' => 33.0,
    '5*(3-1)' => 10.0,
    '3 + 2*(1+2)' => 9.0,
    '100-(4*2*(2+2))' => 68.0,
    '100-(4/2*(2^2))' => 92.0,
    '4 + 7*(3+6*3/(2+1))' => 67.0,
    '7^((3+4*(5-2)/(0-3)) / 2)' => 0.37796447300923
];

$mathParser = new MathParser();

foreach ($tests as $operation => $correctResult) {
    $result = $mathParser->evaluate($operation);

    echo "$operation = $result (" . ($result === $correctResult ? 'correct' : 'wrong') . ")" . PHP_EOL;
}