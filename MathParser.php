<?php

class MathParser
{
    private $levels;

    /**
     * @param string $math
     *
     * @return bool|float
     */
    public function evaluate(string $math)
    {
        if (!$this->isValid($math)) return false;

        // trim normally & TODO: remove unnecessary brackets like ((((2+2)))) = 2+2
        $math = trim($math);
        $math = preg_replace('/\s+/', '', $math);

        $math = "($math)"; // add parentheses so it counts as first level
        $this->levels = $this->parseParentheses($math);
        $this->levels = $this->replaceChildren($this->levels);

        return $this->calc();
    }

    /**
     * @param float $lastResult
     *
     * @return float
     */
    private function calc(float $lastResult = 0): float
    {
        if (count($this->levels) >= 1) {
            $prevLevel =& $this->getDeepestLevel($this->levels, true);
            if (!$prevLevel) {
                $deepestLevel = $this->getDeepestLevel($this->levels);
                $lastOperation = $deepestLevel['value'];

                return $this->getResult($lastOperation);
            } else {
                $levels = [$prevLevel];
                $deepestLevel =& $this->getDeepestLevel($levels);

                $result = $this->getResult($deepestLevel['value']);

                $replaces = $deepestLevel['replaces'];
                $prevLevel['value'] = str_replace($replaces, $result, $prevLevel['value']);

                foreach ($prevLevel['children'] as $key => $child) {
                    if ($child['replaces'] === $replaces) {
                        unset($prevLevel['children'][$key]);
                    }
                }
                if (empty($prevLevel['children'])) {
                    unset($prevLevel['children']);
                }

                return $this->calc($lastResult + $result);
            }
        }

        return $lastResult;
    }

    /**
     * @param string $operation
     *
     * @return float
     */
    private function getResult(string $operation): float
    {
        $operator = null;
        if (strpos($operation, '^') !== false) {
            $operator = '\^';
        } else if (strpos($operation, '*') !== false) {
            $operator = '\*';
        } else if (strpos($operation, '/') !== false) {
            $operator = '\/';
        }

        if ($operator) {
            $c = preg_match_all('/[0-9]+' . $operator . '[0-9]+/', $operation, $matches);
            if ($c > 0 && isset($matches)) {
                $operator = str_replace('\\', '', $operator);

                foreach ($matches[0] as $match) {
                    $parts = explode($operator, $match);

                    $first = $parts[0];
                    $second = $parts[1];
                    switch ($operator) {
                        case '^': // TODO: right to left..
                            $result = $first ^ $second;
                            break;
                        case '*':
                            $result = $first * $second;
                            break;
                        case '/': // TODO: dividing 0
                            $result = $first / $second;
                            break;
                        default:
                            $result = 0;
                            break;
                    }

                    $operation = $this->str_replace_first($match, $result, $operation);
                }

                return $this->getResult($operation);
            }
        }

        if (strpos($operation, '+') !== false || strpos($operation, '-') !== false) {
            $c = preg_match('/[0-9]+([-|+])[0-9]+/', $operation, $matches);
            if ($c > 0 && isset($matches)) {
                $match = $matches[0];
                $operator = $matches[1];
                $parts = explode($operator, $match);

                $first = $parts[0];
                $second = $parts[1];
                switch ($operator) {
                    case '+':
                        $result = $first + $second;
                        break;
                    case '-':
                        $result = $first - $second;
                        break;
                    default:
                        $result = 0;
                        break;
                }

                $operation = $this->str_replace_first($match, $result, $operation);

                return $this->getResult($operation);
            }
        }

        return floatval($operation);
    }

    /**
     * @param array      $levels
     * @param bool       $getParent
     * @param array|null $prevLevel
     *
     * @return array
     */
    private function & getDeepestLevel(array &$levels, bool $getParent = false, ?array &$prevLevel = null): ?array
    {
        $levelDepths = [];
        foreach ($levels as $key => $level) {
            $levelDepths[$key] = $this->array_depth($level);
        }

        arsort($levelDepths);

        $deepestLevel = &$levels[array_key_first($levelDepths)];

        if (empty($deepestLevel['children'])) {
            if ($getParent) {
                return $prevLevel;
            }
            return $deepestLevel;
        }

        return $this->getDeepestLevel($deepestLevel['children'], $getParent, $deepestLevel);
    }

    /**
     * Creates an array to match levels of parentheses in $subject.
     *
     * @see https://gist.github.com/Xeoncross/4710324#gistcomment-2786921
     *
     * @param string $subject
     *
     * @return array
     */
    private function parseParentheses(string $subject): array
    {
        $result = [];

        preg_match_all('~[^\(\)]+|\((?<nested>(?R)*)\)~', $subject, $matches);

        foreach (array_filter($matches['nested']) as $match) {
            $item = [];

            $item['value'] = $match;

            if ([] !== $children = $this->parseParentheses($match)) {
                $item['children'] = $children;
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Changes $this->levels to have replace keys for each children's values.
     *
     * @param array $data
     * @param int   $lastKey
     *
     * @return array
     */
    private function replaceChildren(array $data, int $lastKey = 0): array
    {
        foreach ($data as &$item) {
            if (empty($item['children'])) continue;

            foreach ($item['children'] as $key => &$child) {
                $childValue = $child['value'];
                $child['replaces'] = '#' . $lastKey++;
                $item['value'] = $this->str_replace_first("($childValue)", $child['replaces'], $item['value']);

                if (!empty($child['children'])) {
                    $child = $this->replaceChildren([$child], $lastKey)[0];
                }
            }
        }

        return $data;
    }

    // TODO
    private function isValid(string $math)
    {
        return true;
    }

    /**
     * Replaces only the first occurrence of $search in $subject with $replace.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string|string[]
     */
    private function str_replace_first(string $search, string $replace, string $subject)
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }

    /**
     * @see https://stackoverflow.com/a/262944/13162601
     *
     * @param array $array
     *
     * @return int
     */
    private function array_depth(array $array): int
    {
        $max_depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = $this->array_depth($value) + 1;

                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }

        return $max_depth;
    }
}