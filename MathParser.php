<?php

class MathParser
{
    private $math;
    private $levels;

    public function evaluate(string $math)
    {
        if (!$this->isValid($math)) return false;

        // trim normally & TODO: remove unnecessary brackets like ((((2+2)))) = 2+2
        $math = trim($math);

        $this->math = "($math)"; // add parentheses so it counts as first level
        $this->levels = $this->parseParentheses($math);
        $this->levels = $this->replaceChildren($this->levels);

        return $this->calc();
    }

    private function calc(float $lastResult = 0)
    {
        if (count($this->levels) >= 1) {
            $prevLevel =& $this->getDeepestLevel($this->levels, true);
            $levels = [$prevLevel];
            $deepestLevel =& $this->getDeepestLevel($levels);

            // TODO: evaluate $deepestLevel['value']
        }

        return $lastResult;
    }

    private function & getDeepestLevel(array &$levels, bool $getParent = false, ?array &$prevLevel = null)
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

    private function array_depth(array $array)
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

    private function parseParentheses(string $subject)
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

    private function replaceChildren(array $data, int $lastKey = 0)
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

    private function str_replace_first(string $search, string $replace, string $subject)
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }

    private function getResult(string $math)
    {
        if (!$this->isValid($math)) return false;

        echo '<pre>';
        print_r($math);
        die();

    }

    // TODO
    private function isValid(string $math)
    {
        return true;
    }
}