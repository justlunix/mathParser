# PHP MathParser

This is a WIP math parser for PHP.

## Usage
```php
require_once 'MathParser.php';

$parser = new MathParser();
$result = $parser->evaluate('2+2*(7-2)');
echo $result;
```