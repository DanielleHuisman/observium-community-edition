<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage functions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Debug nicer functions
//$config['devel'] = TRUE; // DEVEL
if (!defined('OBS_API') && PHP_SAPI !== 'cli' && PHP_VERSION_ID >= 70200 &&
    isset($config['devel']) && $config['devel']) {
    Tracy\Debugger::enable(Tracy\Debugger::Development);
}

if ((defined('OBS_DEBUG') && OBS_DEBUG) || !empty($_SERVER['REMOTE_ADDR']) ||
    (PHP_SAPI === 'cli' && is_array($options['d']))) {
    // this include called before definitions :(
    if (function_exists('token_get_all') && !class_exists('ref')) {
        // Class ref loaded by class_exist call
        include_once($config['install_dir'] . "/libs/ref.inc.php");
    }
}

// Set tracy theme (here for clarity. theme might not be correct if there's a bug in the load *after* a theme change)
// Enable dark mode for Tracy dump()
if (isset($_SESSION['mode']) && $_SESSION['mode'] === 'dark' &&
    class_exists('Tracy\Debugger', FALSE)) {
    Tracy\Debugger::$dumpTheme = 'dark';
}


// Fallback debugging functions

if (!function_exists('r')) {
    function r($var) {
        if (function_exists('dump')) {
            dump($var);
        } else {
            print_r($var);
        }
    }
}

if (!function_exists('rt')) {
    function rt($var) {
        if (function_exists('dump')) {
            dump($var);
        } else {
            print_r($var);
        }
    }
}

if (!function_exists('dump')) {
    function dump($var) {
        if (PHP_SAPI === 'cli' && class_exists('ref', FALSE)) {
            rt($var);
        } elseif (class_exists('ref', FALSE)) {
            r($var);
        } else {
            print_r($var);
        }
    }
}

if (!function_exists('bdump')) {
    function bdump($var) {
        if (PHP_SAPI === 'cli' && class_exists('ref', FALSE)) {
            rt($var);
        } else {
            $backtrace   = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            $file        = $backtrace[0]['file'] ?? 'unknown';
            $line        = $backtrace[0]['line'] ?? 'unknown';
            $caller_info = "$file : $line";

            if (!isset($GLOBALS['dump'])) {
                $GLOBALS['dump'] = [];
            }

            $GLOBALS['dump'][] = [
              'data'        => $var,
              'caller_info' => $caller_info,
            ];
        }
    }
}

/**
 * Observium's variable debugging. Chooses nice output depending upon web or cli
 *
 * @param $vars
 * @param $trace
 *
 * @return void
 */
function print_vars($vars, $trace = NULL) {

    if (PHP_SAPI === 'cli' || (defined('OBS_CLI') && OBS_CLI)) {
        // In cli, still prefer ref
        if (class_exists('ref', FALSE)) {
            try {
                ref::config('shortcutFunc', [ 'print_vars', 'print_debug_vars' ]);
                ref::config('showUrls', FALSE);
                if (defined('OBS_DEBUG') && OBS_DEBUG > 0) {
                    if (is_null($trace)) {
                        $backtrace = defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : debug_backtrace();
                    } else {
                        $backtrace = $trace;
                    }
                    ref::config('Backtrace', $backtrace); // pass original backtrace
                    ref::config('showStringMatches', FALSE);
                } else {
                    ref::config('showBacktrace', FALSE);
                    ref::config('showResourceInfo', FALSE);
                    ref::config('showStringMatches', FALSE);
                    ref::config('showMethods', FALSE);
                }
                rt($vars);
            } catch (Exception $e) {
                print_r($vars);
                echo PHP_EOL;
            }
        } else {
            print_r($vars);
            echo PHP_EOL;
        }
    } elseif (class_exists('ref')) {
        // in Web use old ref class, when Tracy not possible to use
        try {
            ref::config('shortcutFunc', [ 'print_vars', 'print_debug_vars' ]);
            ref::config('showUrls', FALSE);
            if (defined('OBS_DEBUG') && OBS_DEBUG > 0) {
                if (is_null($trace)) {
                    $backtrace = defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : debug_backtrace();
                } else {
                    $backtrace = $trace;
                }
                ref::config('Backtrace', $backtrace); // pass original backtrace
            } else {
                ref::config('showBacktrace', FALSE);
                ref::config('showResourceInfo', FALSE);
                ref::config('showStringMatches', FALSE);
                ref::config('showMethods', FALSE);
            }
            //ref::config('stylePath',  $GLOBALS['config']['html_dir'] . '/css/ref.css');
            //ref::config('scriptPath', $GLOBALS['config']['html_dir'] . '/js/ref.js');
            r($vars);
        } catch (Exception $e) {
            echo '<div class="code">';
            print_r($vars);
            echo '</div>';
        }
    } else {
        // Just fallback to php var dump
        echo '<div class="code">';
        print_r($vars);
        echo '</div>';
    }
}

/**
 * Call to print_vars in debug mode only
 * By default var displayed only for debug level 2
 *
 * @param mixed $vars Variable to print
 * @param integer $debug_level Minimum debug level, default 2
 *
 * @return void
 */
function print_debug_vars($vars, $debug_level = 2) {
    // For level 2 display always (also empty), for level 1 only non empty vars
    if (defined('OBS_DEBUG') && OBS_DEBUG &&
        OBS_DEBUG >= $debug_level && (OBS_DEBUG > 1 || !safe_empty($vars))) {
        $trace = defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : debug_backtrace();
        print_vars($vars, $trace);
    } elseif (defined('OBS_CACHE_DEBUG') && OBS_CACHE_DEBUG) {
        $trace = defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) : debug_backtrace();
        print_vars($vars, $trace);
    }
}

/**
 * Observium's SQL debugging. Chooses nice output depending upon web or cli.
 * Use format param:
 *  'compress' (default) print compressed and highlight query;
 *  'full', 'html' print fully formatted query (multiline);
 *  'log' for a return compressed query.
 *
 * @param string $query
 * @param string $format
 */
function print_sql($query, $format = 'compress') {
    switch ($format) {
        case 'full':
        case 'format':
        case 'html':
            // Fully formatted
            $output = (new Doctrine\SqlFormatter\SqlFormatter())->format($query);
            break;

        case 'log':
            // Only compress and return for log
            return (new Doctrine\SqlFormatter\SqlFormatter())->compress($query);

        default:
            // Only compress and highlight in single line (default)
            $compressed = (new Doctrine\SqlFormatter\SqlFormatter())->compress($query);
            $output     = (new Doctrine\SqlFormatter\SqlFormatter())->highlight($compressed);
    }

    if (PHP_SAPI === 'cli' || (isset($GLOBALS['cli']) && $GLOBALS['cli'])) {
        $output = rtrim($output);
    } else {
        $output = '<p>' . $output . '</p>';
    }

    echo $output;
}

function whimsical_error_handler($errno, $errstr, $errfile, $errline) {
    // Handle only fatal errors
    if (!($errno & (E_ERROR | E_USER_ERROR))) {
        return FALSE;
    }

    // Call the display_error_page function
    display_error_page($errno, $errstr, $errfile, $errline, debug_backtrace());
    exit();
}

function whimsical_shutdown_handler() {
    $error = error_get_last();

    if ($error !== NULL && $error['type'] & (E_ERROR | E_USER_ERROR | E_COMPILE_ERROR | E_PARSE)) {
        display_error_page($error['type'], $error['message'], $error['file'], $error['line'], []);
        exit();
    }
}

function get_context_lines($file, $line_number, $context = 5) {
    $lines         = file($file);
    $start         = max(0, $line_number - $context - 1);
    $length        = $context * 2 + 1;
    $context_lines = array_slice($lines, $start, $length);

    // Add <b> tags around the line number being requested
    $context_lines[$line_number - $start - 1] = '' . $context_lines[$line_number - $start - 1] . '';

    return [
      'start' => $start + 1,
      'lines' => $context_lines
    ];
}

function display_error_page($errno, $errstr, $errfile, $errline, $backtrace) {
    // Log the error
    //error_log("Custom error: [$errno] $errstr in $errfile on line $errline");

    if (!defined('OBS_MIN_PHP_VERSION')) {
        define('OBS_MIN_PHP_VERSION', '7.1.30');
    }

    // If the error is an uncaught exception, get the correct backtrace
    if (preg_match('/^Uncaught Error: (.+) in (.+):(\d+)$/m', $errstr, $matches)) {
        $backtrace = (new ErrorException($matches[1], 0, $errno, $matches[2], $matches[3])) -> getTrace();
        $errstr    = $matches[1];
    }

    array_unshift($backtrace, ['file' => $errfile, 'line' => $errline]);


    // Remove the error handler and shutdown handler from the stack trace
    //$backtrace = array_filter($backtrace, function ($trace) {
    //  return !in_array($trace['function'], ['whimsical_error_handler', 'whimsical_shutdown_handler', 'display_error_page']);
    //});

    // Define color codes
    $color_red    = "\033[31m";
    $color_yellow = "\033[33m";
    $color_green  = "\033[32m";
    $color_cyan   = "\033[36m";
    $color_reset  = "\033[0m";

    if (PHP_SAPI === "cli") {
        // Output text-only error for CLI
        echo "
    ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣀⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⡶⠋⠁⠙⣦⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⣠⡶⠛⠛⠲⣤⣀⠀⠀⠀⠀⡀⠀⠀⠀⠀⠀⠀⠀⣠⠄⣠⣾⣡⠀⠀⠀⠀⠸⡆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⢰⡏⠀⠀⠀⠀⠈⣿⣷⣤⡐⣄⣽⡾⠧⠤⠤⣤⣤⣾⣿⣾⣿⣯⣀⡀⠀⠀⠀⢠⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⢷⡀⠀⠀⠀⠀⣿⣿⣿⣿⣿⣯⣤⣤⡀⣠⣈⣹⣽⣿⣿⣿⣿⣷⣦⣄⠀⢀⡾⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠈⠻⣆⠀⠀⠀⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡟⠀⣠⠞⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠈⢳⣆⠘⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣧⣾⣷⣶⣤⣀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⣘⣿⣿⣿⣿⣿⣟⣿⣿⣿⣿⣿⣿⣿⣿⢡{$color_red}⡼{$color_reset}⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⣾⣿⣿⣿⣿⣿⡏{$color_red}⣯⡇{$color_reset}⣿⣿⣿⡏⣿⣿⣿{$color_red}⣜⣛{$color_reset}⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡷⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⣿⣿⣿⣿⣿⣿⣿⣭⣾⣿⣿⣿⣇⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⠃⠀⠘⢷⣄⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⢠⣿⣿⡏⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡏⢿⣿⣿⡿⠁⠀⠀⠀⠸⣿⣷⡀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⢸⣿⣿⡇⠈⢻⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠻⣿⣿⡟⢠⣿⣿⡟⠁⠀⠀⠀⠀⠙⣻⣿⣿⣆⠀⠀⠀⠀⠀
⠀⠀⠀⠈⣿⣿⣷⡈⠛⠿⣿⣿⡆⠻⢿⣿⣽⣿⣯⣿⠿⠃⠀⠻⠛⣰⣿⣿⠏⠀⠀⠀⠀⢀⣙⣿⣿⣿⣿⣿⣧⡀⠀⠀⠀
⠀⠀⠀⠀⢿⠈⠙⠻⠷⠤⠄⢉⡁⠀⠀⠙⢉⣵⣌⠀⠀⠀⣀⠄⠘⠛⠋⠁⢀⣀⣤⣤⣭⣭⣽⣿⣿⣿⣿⣿⣿⣷⡀⠀⠀
⠀⠀⠀⢀⣿⡄⠀⠀⠀⠀⠀⠀⠉⠓⢦⣄⡉⠉⢉⣩⠶⠋⠁⠀⠀⠀⣠⣾⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⡀⠀
⠀⠀⠀⣼⣿⣿⣷⣶⣶⣆⣀⡀⠀⠀⠀⠀⠉⠉⠉⠀⠀⠀⠀⠀⣠⣾⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣧⠀
⠀⠀⠀⣿⣿⣿⣿⣿⣿⣿⣥⣄⡀⠀⠀⠀⠀⠀⠀⠀⠀⣸⣦⣾⣿⣿⣿⣿⣿⣿⡿⠿⠿⡟⠿⣿⣟⡿⢿⡛⠀⣘⣿⣿⡆
⠀⠀⠀⢿⣿⣿⠿⠿⠿⠿⢿⣿⣿⣷⣿⡆⠀⠀⣶⣶⣾⡿⠛⠛⠛⠛⠻⣿⣿⣿⡟⠉⠀⠀⠀⠈⠁⠁⢀⢀⠲⣬⣿⣿⡇
⠀⠀⠀⠘⡏⠳⣤⡄⠀⡄⠀⠀⠈⠙⢿⣧⠀⢀⣿⣿⠏⠀⠀⠀⠀⢀⣠⣿⣿⠋⠀⠀⠀⠀⠀⠀⠀⠀⠈⣯⣷⣿⣿⣿⡇
⠀⠀⠀⠀⢿⠀⠀⠉⠳⣇⠀⠀⠀⠀⠀⢻⠀⢸⣿⣿⣆⢠⠀⡆⣰⡿⠟⠉⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⡾⣿⣿⣿⣿⣿⡇
⠀⠀⠀⠀⠘⣧⠀⠀⠀⠹⣷⣤⣄⠀⡈⣾⡆⢸⡿⠋⠿⠾⠴⠷⠋⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣠⠞⠉⣶⣾⣿⣿⣿⣿⠀
⠀⠀⠀⠀⠀⠈⠳⣄⠀⠀⠀⠉⠻⣿⣿⣿⣇⠸⡷⠀⠀⠀⢀⣠⢄⣠⣶⣤⣤⣤⡤⠤⠖⠛⠉⠀⠠⣤⣼⣿⣿⣿⣿⡏⠀
⠀⠀⠀⠀⠀⠀⠀⠉⢻⣷⠶⠒⠀⠀⠁⠈⢿⡄⣷⣆⣤⣶⡿⠛⠛⠛⠉⠉⠉⠁⠀⠀⠀⠀⠀⠀⠀⠈⢿⣿⣿⣿⣿⠇⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠘⣏⠀⠀⠀⠀⠀⠀⠘⣧⣿⣿⡿⢋⡤⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠰⣾⣿⣿⣿⣿⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⢻⡄⠀⠀⠀⠀⠀⠀⢸⣿⣿⣶⣫⠆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⡀⢦⣵⣿⣿⣿⣿⠇⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢻⣆⠀⠀⠀⠀⠀⠀⣿⣿⣿⠇⠀⣀⠀⠀⠀⠀⠀⠀⠀⠀⠳⠦⣴⣿⣶⣿⣿⣿⣿⡿⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠛⢷⣄⠀⠀⠀⠀⢹⣿⣿⣾⣿⣋⣤⡄⠀⠀⠀⠀⠀⠀⠀⠠⣈⠻⣿⣿⣿⣿⣿⡇⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠉⠛⣦⣤⣤⣾⣿⣿⣿⣿⣿⣯⡴⠂⣀⡀⠀⡀⡀⡀⢠⣬⣻⣿⣿⣿⣿⡿⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⢀⣀⣀⣀⣀⣠⡴⠊⠉⠀⠈⢹⠿⣿⣿⣿⣿⣿⣷⣾⣿⠾⡿⡿⠿⣷⣦⣿⣿⣿⣿⣿⡟⠁⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠛⠷⠦⠤⠤⠴⠶⠶⠶⠶⠚⠉⠀⠀⠈⣉⣩⠽⠟⠋⠁⠀⠀⣁⣠⠿⠛⠋⠉⠉⠉⠁⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠉⠉⠉⠉⠉⠉⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀\n";


        /*
        echo "\n{$color_rd}";
        echo "            ___                  _         \n";
        echo "           / _ \  ___  _ __  ___| |        \n";
        echo "          | | | |/ _ \| '_ \/ __| |        \n";
        echo "          | |_| | (_) | |_) \__ \_|        \n";
        echo "           \___/ \___/| .__/|___(_)        \n";
        echo "                      |_|                  {$color_reset}\n";
        echo "\n";
        */
        echo "{$color_red}Oops! Something went wrong! You may want to report this to the Observium developers.{$color_reset}\n";
        echo "\n";
        echo "{$color_green}$errstr in $errfile:$errline{$color_reset}\n";

        // Check and print a warning if the current PHP version is below the minimum required
        if (version_compare(PHP_VERSION, OBS_MIN_PHP_VERSION, '<')) {
            echo "\n{$color_red}Warning: Your PHP version (" . PHP_VERSION . ") is below the minimum required version (" . OBS_MIN_PHP_VERSION . ").\nPlease upgrade your PHP version before reporting issues.{$color_reset}\n";
        }

        echo "\n{$color_yellow}Stack trace:{$color_reset}\n";
        foreach ($backtrace as $i => $trace) {
            $file     = $trace['file'] ?? '(unknown file)';
            $line     = $trace['line'] ?? '(unknown line)';
            $function = $trace['function'] ?? '(unknown function)';

            echo "\n{$color_cyan}#$i: {$function} called at [{$file}:{$line}]{$color_reset}\n";

            if (file_exists($file)) {
                $context_lines = get_context_lines($file, $line);

                foreach ($context_lines['lines'] as $j => $context_line) {
                    $context_line = trim($context_line);
                    $line_number  = $context_lines['start'] + $j;

                    if ($line_number == $line) {
                        echo "{$color_red}{$line_number}: {$context_line}{$color_reset}\n";
                    } else {
                        echo "{$color_reset}{$line_number}: {$context_line}{$color_reset}\n";
                    }
                }
            }
        }

        echo "\n";

        exit();
    }

    // Display the whimsical error page
    http_response_code(500);
    ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Oops! Something went wrong.</title>
            <style>

                body {
                    text-align: center;
                    padding: 5%;
                }

                h1 {
                    font-size: 4em;
                    color: #ff6347;
                }

                p {
                    font-size: 1.5em;
                    color: #696969;
                }

                img {
                    max-width: 100%;
                    margin: 20px;
                }

                .stack-trace {
                    text-align: left;
                    max-width: 800px;
                    margin: 1em auto;
                    padding: 1em;
                    border: 1px solid #ccc;
                    font-size: 14px;
                }

                h2 {
                    font-size: 1.2em;
                }

            </style>
        </head>
        <body>
        <div style="margin: auto; max-width: 900px; text-align: center;">
            <h1>Oops! Something went wrong.</h1>
            <img src="/images/hamster-panic.jpg" alt="Hamster Panic">


            <?php
            // Check and print a warning if the current PHP version is below the minimum required
            if (version_compare(PHP_VERSION, OBS_MIN_PHP_VERSION, '<')) {
                echo "<div style='margin: auto; max-width: 900px; text-align: center;'>";
                echo "<p style='background: #ff6347; color: white; padding: 10px; '>Warning: Your PHP version (" . PHP_VERSION . ") is below the minimum required version (" . OBS_MIN_PHP_VERSION . "). Please upgrade your PHP version before reporting issues.</p>";
                echo "</div>";
            }
            ?>

            <h2><?php echo $errstr . ' in ' . $errfile . ':' . $errline ?></h2>
        </div>

        <div class="stack-trace">
            <?php foreach ($backtrace as $i => $trace): ?>
                <?php
                $file     = $trace['file'] ?? '(unknown file)';
                $line     = $trace['line'] ?? '(unknown line)';
                $function = $trace['function'] ?? '(unknown function)';
                ?>
                <div class="trace-entry">
                    <h2>#<?= $i ?>:</strong> <?= htmlspecialchars($function) ?> called at [<?= htmlspecialchars($file) ?>:<?= htmlspecialchars($line) ?>]</h2>
                    <?php if (file_exists($file)): ?>
                        <?php $context_lines = get_context_lines($file, $line); ?>

                        <?php foreach ($context_lines['lines'] as $j => $context_line): ?>
                            <?php $context_line = trim($context_line); ?>

                            <?php if ($context_lines['start'] + $j == $line): ?>
                                <code><span style="color: red; font-weight: bold;">
            <?= htmlspecialchars($context_lines['start'] + $j) ?>: <?= htmlspecialchars($context_line) ?>
        </span></code><br/>
                            <?php else: ?>
                                <code>
                                    <?= htmlspecialchars($context_lines['start'] + $j) ?>: <?= htmlspecialchars($context_line) ?>
                                </code><br/>
                            <?php endif; ?>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        </body>
        </html>
    <?php
    exit();
}

function display_error_http($errno, $message = NULL)
{
    switch ($errno) {
        case 401:
            http_response_code($errno);
            $error = 'Unauthorized';
            break;
        case 403:
            http_response_code($errno);
            $error = 'Forbidden';
            break;
        case 404:
            http_response_code($errno);
            $error = 'Not Found';
            break;
        case 408:
            http_response_code($errno);
            $error = 'Request Timeout';
            break;
        default:
            http_response_code(400);
            $error = 'Bad Request';
    }
    if (!empty($message)) {
        $error .= '. ' . escape_html($message) . '.';
    }

    ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oops! Something went wrong.</title>
    <style>

        body {
            text-align: center;
            padding: 5%;
        }

        h1 {
            font-size: 4em;
            color: #ff6347;
        }

        p {
            font-size: 1.5em;
            color: #696969;
        }

        img {
            max-width: 100%;
            margin: 20px;
        }

        .stack-trace {
            text-align: left;
            max-width: 800px;
            margin: 1em auto;
            padding: 1em;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        h2 {
            font-size: 1.2em;
        }

    </style>
  </head>
  <body>
  <div style="margin: auto; max-width: 900px; text-align: center;">
    <h1><?php echo $error ?></h1>
    <img src="/images/hamster-panic.jpg" alt="Hamster Panic">

    <h2><?php echo $error ?></h2>
  </div>

  </body>
  </html>
    <?php
    die();
}

function display_exception($exception) {
    display_error_page(
        $exception -> getCode(),
        $exception -> getMessage(),
        $exception -> getFile(),
        $exception -> getLine(),
        $exception -> getTrace()
    );
}

// Set the custom error handler
set_error_handler("whimsical_error_handler");
set_exception_handler('display_exception');
register_shutdown_function("whimsical_shutdown_handler");

// Set QUIET
define('OBS_QUIET', isset($options['q']));

// Set DEBUG
if (isset($options['d'])) {
    // CLI
    echo("DEBUG!\n");
    define('OBS_DEBUG', is_array($options['d']) ? count($options['d']) : 1); // -d == 1, -dd == 2..
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    if (OBS_DEBUG > 1) {
        //ini_set('error_reporting', E_ALL ^ E_NOTICE); // FIXME, too many warnings ;)
        ini_set('error_reporting', E_ALL ^ E_DEPRECATED ^ E_NOTICE ^ E_WARNING);
    } else {
        ini_set('error_reporting', E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR); // Only various errors
    }
} elseif (defined('OBS_API') && OBS_API) {
    // API
    $debug_web_requested = ((isset($_REQUEST['debug']) && $_REQUEST['debug']) ||
                            (isset($_SERVER['PATH_INFO']) && strpos($_SERVER['PATH_INFO'], 'debug')) ||
                            (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'debug')));
    if ($debug_web_requested) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 0);
        ini_set('log_errors', 0);
        ini_set('allow_url_fopen', 0);
        ini_set('error_reporting', E_ALL);
        $debug = TRUE;
        // API used self debug output
        if (isset($config['web_debug_unprivileged']) && $config['web_debug_unprivileged'] &&
            is_numeric($_GET['debug']) && $_GET['debug'] > 1) {
            define('OBS_DEBUG', 1);
        } else {
            define('OBS_DEBUG', 0);
        }
    } else {
        define('OBS_DEBUG', 0);
    }
} elseif ($debug_web_requested = ((isset($_REQUEST['debug']) && $_REQUEST['debug']) ||
                                  (isset($_SERVER['PATH_INFO']) && strpos($_SERVER['PATH_INFO'], 'debug')) ||
                                  (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'debug')))) {
    // WEB

    // Note, for security reasons set OBS_DEBUG constant in WUI moved to auth module
    if (isset($config['web_debug_unprivileged']) && $config['web_debug_unprivileged']) {
        define('OBS_DEBUG', 1);

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        //ini_set('error_reporting', E_ALL ^ E_NOTICE);
        ini_set('error_reporting', E_ALL ^ E_DEPRECATED ^ E_NOTICE ^ E_WARNING);
    } // else not set anything before auth

} else {
    define('OBS_DEBUG', 0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    //ini_set('error_reporting', 0); // Use default php config
}
if (!defined('OBS_DEBUG') || !OBS_DEBUG) {
    // Disable E_NOTICE from reporting.
    error_reporting(error_reporting() & ~E_NOTICE);
}
ini_set('log_errors', 1);

// EOF