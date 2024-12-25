<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage common
 * @copyright  (C) Adam Armstrong
 *
 */

// PHP (7.3) compat functions

if (PHP_VERSION_ID < 70300) {
    /**
     * Gets the first key of an array
     *
     * @param array $array
     *
     * @return mixed
     */
    function array_key_first($array) {
        if (!is_array($array) || empty($array)) {
            return NULL;
        }

        return array_keys($array)[0];
    }

    /**
     * Gets the last key of an array
     *
     * @param array $array
     *
     * @return mixed
     */
    function array_key_last($array) {
        if (!is_array($array) || empty($array)) {
            return NULL;
        }

        end($array);
        return key($array);
        //return $array && is_array($array) ? array_keys($array)[count($array) - 1] : NULL;
    }

    function is_countable($var) {
        return (is_array($var) || $var instanceof Countable);
    }
}

// PHP (8.0) compat functions

if (PHP_VERSION_ID < 80000) {
    // Note. We use better implementations str_contains_array() and str_icontains_array()
    function str_contains($haystack, $needle) {
        return '' === $needle || FALSE !== strpos($haystack, $needle);
    }

    function str_starts_with($haystack, $needle) {
        return 0 === strncmp($haystack, $needle, strlen($needle));
    }

    function str_ends_with($haystack, $needle) {
        if ('' === $needle || $needle === $haystack) {
            return TRUE;
        }

        if ('' === $haystack) {
            return FALSE;
        }

        $needle_len = strlen($needle);

        return $needle_len <= strlen($haystack) && 0 === substr_compare($haystack, $needle, -$needle_len);
    }

    function fdiv($dividend, $divisor) {
        return @($dividend / $divisor);
    }
}

// PHP (8.1) compat functions

if (PHP_VERSION_ID < 80100) {
    function array_is_list(array $array) {
        if ([] === $array || $array === array_values($array)) {
            return TRUE;
        }

        $next_key = -1;

        foreach ($array as $k => $v) {
            if ($k !== ++$next_key) {
                return FALSE;
            }
        }

        return TRUE;
    }
}

// PHP (8.3) compat functions

if (PHP_VERSION_ID < 80300) {

    function json_validate(string $json, int $depth = 512, int $flags = 0) {
        if (0 !== $flags && defined('JSON_INVALID_UTF8_IGNORE') && JSON_INVALID_UTF8_IGNORE !== $flags) {
            throw new \ValueError('json_validate(): Argument #3 ($flags) must be a valid flag (allowed flags: JSON_INVALID_UTF8_IGNORE)');
        }

        if ($depth <= 0) {
            throw new \ValueError('json_validate(): Argument #2 ($depth) must be greater than 0');
        }

        // see https://www.php.net/manual/en/function.json-decode.php
        if ($depth > 2147483647) {
            throw new \ValueError(sprintf('json_validate(): Argument #2 ($depth) must be less than %d', 2147483647));
        }

        json_decode($json, NULL, $depth, $flags);

        return JSON_ERROR_NONE === json_last_error();
    }
}

// PHP (8.4) compat functions

if (PHP_VERSION_ID < 80400) {

    function array_find(array $array, callable $callback) {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return NULL;
    }

    function array_find_key(array $array, callable $callback) {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $key;
            }
        }

        return NULL;
    }

    function array_any(array $array, callable $callback) {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    function array_all(array $array, callable $callback) {
        foreach ($array as $key => $value) {
            if (!$callback($value, $key)) {
                return FALSE;
            }
        }

        return TRUE;
    }
}

// EOF
