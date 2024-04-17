<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        $argIndex = 0;
        return preg_replace_callback(pattern: '/(\\?b|\\?d|\\?f|\\?a|\\?#|\\?|\{[^}]*\})/',
            callback: function ($matches) use (&$argIndex, $args) {
                return $this->prepairing($matches[0], $args[$argIndex++]);
            },
            subject: $query);
    }

    private function prepairing(string $original, mixed $arguments, bool $isInside = false): string
    {
        switch ($original) {
            case '?':
                return escapeshellarg($arguments);
            case '?d':
                if (is_null($arguments)) return 'NULL';
                elseif (!is_int($arguments) && !is_bool($arguments)) throw new Exception('Invalid integer argument');
                else return $arguments;
            case '?f':
                if (is_null($arguments)) return 'NULL';
                elseif (!is_float($arguments)) throw new Exception('Invalid float argument');
                return (int) $arguments;
            case '?#':
                if (is_null($arguments)) throw new Exception('Invalid ?# argument, the can not be null');
                if (is_array($arguments)) {
                    if (empty($arguments)) throw new Exception('Invalid ?# argument (array), the can not be empty');
                    return "`". implode("`, `", $arguments) . "`";
                }
                else return "`{$arguments}`";
            case '?a':
                if (is_array($arguments)) {
                    if (empty($arguments)) throw new Exception('Invalid ?# argument (array), the can not be empty');
                    $result = '';
                    foreach ($arguments as $key => $item) {
                        if (is_string($key)) {
                            $result .= ', `' . $key . '` = ';
                            if (is_string($item)) $result .= escapeshellarg($item);
                            elseif (is_null($item)) $result .= 'NULL';
                            else $result .= $item;
                        } elseif(is_int($key)) {
                            $result .= ', ';
                            if (is_string($item)) $result .= escapeshellarg($item);
                            elseif (is_null($item)) $result .= 'NULL';
                            else $result .= $item;
                        }
                    }
                    return ltrim($result, ', ');
                }
                else throw new Exception('Invalid ?# argument (array), the can not be empty');
            default:
                if (!$isInside) {
                    if (is_null($arguments)) return '';
                    elseif (str_starts_with($original, '{') && str_ends_with($original, '}')) {
                        return trim(preg_replace_callback('/(\?b|\?d|\?f|\?a|\?#|\?)/', callback: function ($matches) use (&$argIndex1, $arguments) {
                            return $this->prepairing($matches[0], $arguments, true);
                        }, subject: $original), '{}');
                    }
                }
                throw new Exception('Invalid ' . $original . ' argument');
//                if (is_null($arg)) return '';
//                elseif (str_starts_with($original, '{') && str_ends_with($original, '}')) {
//                    return str_replace('?d', $arg, trim($original, '{}'));
//                }
//                throw new Exception('Invalid ' . $original . ' argument');
        }
    }

    public function skip()
    {
        return null;
//        throw new Exception();
    }
}
