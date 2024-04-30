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


/**
 * Autoloader for Classes used in Observium
 *
 */
function observium_autoload($class_name) {
    //var_dump($class_name);
    if (isset($GLOBALS['config']['install_dir'])) {
        $base_dir = $GLOBALS['config']['install_dir'] . '/libs/';
    } else {
        // not know why in phpunit $GLOBALS and $config reset on this stage
        $base_dir = dirname(__DIR__) . '/libs/';
    }

    $class_array = explode('\\', $class_name);
    $class_file  = str_replace('_', '/', implode('/', $class_array)) . '.php';
    //print_vars($class_array);
    switch ($class_array[0]) {
        case 'cli':
            include_once($base_dir . 'cli/cli.php'); // Cli classes required base functions
            $class_file = str_replace('/Table/', '/table/', $class_file);
            //var_dump($class_file);
            break;

        case 'Phpfastcache':
        case 'phpFastCache':
            if (PHP_VERSION_ID >= 70300) {
                $class_array[0] = 'Phpfastcache8';
                $class_file     = str_replace('_', '/', implode('/', $class_array)) . '.php';
            }
            break;

        case 'flight':
        case 'Flight':
            $class_file = array_pop($class_array) . '.php';
            if (PHP_VERSION_ID >= 70400) {
                // PHP 7.4+ (for 8.1 required)
                $class_file = 'flight3/' . $class_file;
            } else {
                // Old compat version
                $class_file = 'flight/' . $class_file;
            }
            break;

        case 'Ramsey':
            if (PHP_VERSION_ID >= 80000 && $class_array[1] === 'Uuid') {
                // PHP 7.2+ (for 8.1 required)
                //$class_array[1] = 'Uuid4';
                $class_file = str_replace('/Uuid/', '/Uuid4/', $class_file);
                //$class_file     = str_replace('_', '/', implode('/', $class_array)) . '.php';
            }
            break;

        case 'Brick':
            if (PHP_VERSION_ID >= 80000 && $class_array[1] === 'Math') {
                // PHP 8.0+ (for 0.11 required)
                $class_file = str_replace('/Math/', '/Math11/', $class_file);
                //$class_file     = str_replace('_', '/', implode('/', $class_array)) . '.php';
            }
            break;

        case 'Defuse':
        case 'donatj':
            $class_file = str_replace($class_array[0] . '/', '', $class_file);

            // Initial base class file
            $class_file_base = $base_dir . end($class_array) . '.php';
            if (is_file($class_file_base)) {
                $base_status = include_once($class_file_base);
                if (defined('OBS_DEBUG') && OBS_DEBUG > 1 && function_exists('print_message')) {
                    // autoload included before common
                    print_message("%WLoad base file for class '$class_name' from '$class_file_base': " . ($base_status ? '%gOK' : '%rFAIL'), 'console');
                }
            }
            break;

        case 'PhpUnitsOfMeasure':
            include_once($base_dir . 'PhpUnitsOfMeasure/UnitOfMeasureInterface.php');
            break;

        case 'Tracy':
            $status = require_once($base_dir . 'Nette/tracy.php');
            if (defined('OBS_DEBUG') && OBS_DEBUG > 1 && function_exists('print_message')) {
                print_message("%WLoad class '$class_name' loader from '{$base_dir}Nette/tracy.php': " . ($status ? '%gOK' : '%rFAIL'), 'console');
            }
            return $status;
            //array_unshift($class_array, 'Nette');
            //$class_file     = str_replace('_', '/', implode('/', $class_array)) . '.php';
            break;

        default:
            if (strpos($class_name, 'Parsedown') === 0) {
                $class_file = 'parsedown/' . $class_file;
            } elseif (is_file($base_dir . 'pear/' . $class_file)) {
                // By default try Pear file
                $class_file = 'pear/' . $class_file;
            } elseif (is_dir($base_dir . 'pear/' . $class_name)) {
                // And Pear dir
                $class_file = 'pear/' . $class_name . '/' . $class_file;
            }
        //elseif (!is_cli() && is_file($GLOBALS['config']['html_dir'] . '/includes/' . $class_file))
        //{
        //  // For WUI check class files in html_dir
        //  $base_dir   = $GLOBALS['config']['html_dir'] . '/includes/';
        //}
    }
    $full_path = $base_dir . $class_file;

    if ($status = is_file($full_path)) {
        $status = include_once($full_path);
    }
    if (defined('OBS_DEBUG') && OBS_DEBUG > 1 &&
        function_exists('print_message')) {
        print_message("%WLoad class '$class_name' from '$full_path': " . ($status ? '%gOK' : '%rFAIL'), 'console');
    }
    return $status;
}

// Register autoload function
spl_autoload_register('observium_autoload');