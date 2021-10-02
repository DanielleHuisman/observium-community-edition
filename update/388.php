<?php

echo "Renaming files containing commas [";

if (is_dir($config['rrd_dir']))
{

  foreach (get_recursive_directory_iterator($config['rrd_dir']) as $file => $info)
  {

    if ($info->getExtension() == 'rrd' && str_contains_array($info->getFilename(), ','))
    {
      $safename = str_replace(",", "_", $file);
      rename($file, $safename);
      echo '.';
    }

  }

} else { echo "No RRD dir! New install?"; }

echo ']';

// EOF
