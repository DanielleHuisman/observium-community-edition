<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage libs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

/**
 * @var array   $config
 * @var string  $include_dir
 * @var string  $include_dir_regexp
 * @var integer $include_dir_depth
 * @var boolean $include_dir_sort
 */

// This is an include so that we don't lose variable scope.

if (!isset($include_dir_regexp) || $include_dir_regexp === '')
{
  $include_dir_regexp = "/\.inc\.php$/";
}
if (!isset($include_dir_depth) || !is_numeric($include_dir_depth))
{
  // Do not include files from (one level) subdir by default
  $include_dir_depth = 0;
}
// Sort results?
$include_dir_sort = isset($include_dir_sort) && $include_dir_sort;
$include_paths = [];

foreach (get_recursive_directory_iterator($config['install_dir'] . '/' . $include_dir, (int)$include_dir_depth) as $file => $info)
{
  if (preg_match($include_dir_regexp, $info->getFilename()))
  {
    if ($include_dir_sort)
    {
      $include_paths[] = $file;
      continue;
    }
    // do not use print_debug, which not included for definitions!
    //if (OBS_DEBUG > 1) { echo('Including: ' . $file . PHP_EOL); }

    include($file);
  }
}

// This loop used only when sorting includes
if ($include_dir_sort)
{
  asort($include_paths);
}
foreach ($include_paths as $file)
{
  // do not use print_debug, which not included for definitions!
  //if (OBS_DEBUG > 1) { echo('Including sorted: ' . $file . PHP_EOL); }

  include($file);
}

/* old
if ($handle = opendir($config['install_dir'] . '/' . $include_dir))
{
  while (false !== ($file = readdir($handle)))
  {
    $file_path = $config['install_dir'] . '/' . $include_dir . '/' . $file;
    if (preg_match($include_dir_regexp, $file) && is_file($file_path))
    {
      // do not use print_debug, which not included for definitions!
      if (OBS_DEBUG > 1)
      {
        echo('Including: ' . $file_path . PHP_EOL);
      }

      include($file_path);
    }
  }
  closedir($handle);
}
*/

unset($include_dir_regexp, $include_dir_depth, $include_dir, $include_paths);

// EOF
