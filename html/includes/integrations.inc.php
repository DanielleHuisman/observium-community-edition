<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/**
 * Return the filename of the device RANCID config file
 *
 * @param string $hostname
 * @param boolean $debug
 *
 * @return false|string
 */
function get_rancid_filename($hostname, $debug = FALSE) {
    global $config;

    $hostnames = generate_device_hostnames($hostname, $config['rancid_suffix'], $debug);

    foreach ((array)$config['rancid_configs'] as $config_path) {
        if (!str_ends($config_path, '/')) {
            $config_path .= '/';
        }
        if ($debug) {
            echo("Looking in configured directory: <b>$config_path</b><br />");
        }

        foreach ($hostnames as $host) {
            if (is_file($config_path . $host)) {
                $real_filename = is_link($config_path . $host) ? readlink($config_path . $host) : $config_path . $host;
                if ($debug) {
                    echo("File <b>" . $real_filename . "</b> found.<br />");
                }
                return $real_filename;
            }
            if ($debug) {
                echo("File <b>" . $config_path . $host . "</b> not found.<br />");
            }
        }
    }

    return FALSE;
}

/**
 * Return the filename of the device NFSEN rrd file
 *
 * @param string $hostname
 *
 * @return false|string
 */
function get_nfsen_filename($hostname)
{
    global $config;

    foreach ((array)$config['nfsen_rrds'] as $nfsen_rrd) {
        if (!str_ends($nfsen_rrd, '/')) {
            $nfsen_rrd .= '/';
        }
        $basefilename_underscored = str_replace(".", $config['nfsen_split_char'], $hostname);

        // Remove suffix and prefix from basename
        $nfsen_filename = $basefilename_underscored;
        if (!safe_empty($config['nfsen_suffix'])) {
            $nfsen_filename = (strstr($nfsen_filename, $config['nfsen_suffix'], TRUE));
        }
        if (!safe_empty($config['nfsen_prefix'])) {
            $nfsen_filename = (strstr($nfsen_filename, $config['nfsen_prefix']));
        }

        $nfsen_rrd_file = $nfsen_rrd . $nfsen_filename . '.rrd';
        if (is_file($nfsen_rrd_file)) {
            return $nfsen_rrd_file;
        }
    }

    return FALSE;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function get_smokeping_files($debug = FALSE)
{
    global $config;

    $smokeping_files = [];

    if ($debug) {
        echo('- Recursing through ' . $config['smokeping']['dir'] . '<br />');
    }

    if (isset($config['smokeping']['master_hostname'])) {
        $master_hostname = $config['smokeping']['master_hostname'];
    } else {
        $master_hostname = $config['own_hostname'] ?: get_localhost();
    }

    if (is_dir($config['smokeping']['dir'])) {
        foreach (get_recursive_directory_iterator($config['smokeping']['dir']) as $file => $info)
        {
            if (!str_ends_with($info->getFilename(), '.rrd')) { continue; }

            if ($debug) {
                echo('- Found file ending in ".rrd": ' . basename($file) . '<br />');
            }

            $basename = basename($file, ".rrd");

            if (str_contains($file, '~')) {
                [ $target, $slave ] = explode("~", $basename);
                if ($debug) {
                    echo('- Determined to be a slave file for target <b>' . $target . '</b><br />');
                }
                $target = str_replace($config['smokeping']['split_char'], ".", $target);
                if ($config['smokeping']['suffix']) {
                    $target = $target . $config['smokeping']['suffix'];
                    if ($debug) {
                        echo('- Suffix is configured, target is now <b>' . $target . '</b><br />');
                    }
                }
                $smokeping_files['incoming'][$target][$slave] = $file;
                $smokeping_files['outgoing'][$slave][$target] = $file;
            } else {
                $target = $basename;
                if ($debug) {
                    echo('- Determined to be a local file, for target <b>' . $target . '</b><br />');
                }
                $target = str_replace($config['smokeping']['split_char'], ".", $target);
                if ($debug) {
                    echo('- After replacing configured split_char ' . $config['smokeping']['split_char'] . ' by . target is <b>' . $target . '</b><br />');
                }
                if ($config['smokeping']['suffix']) {
                    $target = $target . $config['smokeping']['suffix'];
                    if ($debug) {
                        echo('- Suffix is configured, target is now <b>' . $target . '</b><br />');
                    }
                }
                $smokeping_files['incoming'][$target][$master_hostname] = $file;
                $smokeping_files['outgoing'][$master_hostname][$target] = $file;
            }

        }
    } elseif ($debug) {
        echo("- Smokeping RRD directory not found: " . $config['smokeping']['dir']);
    }

    return $smokeping_files;
}

// EOF
