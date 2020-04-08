<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// This is an include so that we don't lose variable scope.

if ($include_dir_regexp == "" || !isset($include_dir_regexp))
{
  $include_dir_regexp = "/\.inc\.php$/";
}

if ($handle = opendir($config['install_dir'] . '/' . $include_dir))
{
  while (false !== ($file = readdir($handle)))
  {
    if (preg_match($include_dir_regexp, $file) && is_file($config['install_dir'] . '/' . $include_dir . '/' . $file))
    {
      //print_debug('Including: ' . $config['install_dir'] . '/' . $include_dir . '/' . $file);
      //if (OBS_DEBUG > 1) { echo('Including: ' . $config['install_dir'] . '/' . $include_dir . '/' . $file . PHP_EOL); } // do not use print_debug, which not included for definitions!

      include($config['install_dir'] . '/' . $include_dir . '/' . $file);
    }
  }
  closedir($handle);
}

unset($include_dir_regexp, $include_dir, $file, $handle);

// EOF
