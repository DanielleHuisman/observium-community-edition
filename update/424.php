<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$sql = "SELECT * FROM `ipv4_addresses` WHERE `ipv4_type` IS NULL OR `ipv4_type` = ''";
$addresses = dbFetchRows($sql);

if (count($addresses))
{
  echo 'Update IPv4 addresses: ';

  $multi_update_db = [];
  foreach ($addresses as $entry)
  {
    $full_address = $entry['ipv4_address'] . '/' . $entry['ipv4_prefixlen'];
    $type = get_ip_type($full_address);
    if (strlen($type))
    {
      $multi_update_db[] = [ 'ipv4_address_id' => $entry['ipv4_address_id'], 'ipv4_type' => $type ];
      echo('.');
    }
  }
  if (count($multi_update_db))
  {
    dbUpdateMulti($multi_update_db, 'ipv4_addresses');
  }
  echo(PHP_EOL);
}

$sql = "SELECT * FROM `ipv6_addresses` WHERE `ipv6_type` IS NULL OR `ipv6_type` = ''";
$addresses = dbFetchRows($sql);

if (count($addresses))
{
  echo 'Update IPv6 addresses: ';

  $multi_update_db = [];
  foreach ($addresses as $entry)
  {
    $full_address = $entry['ipv6_address'] . '/' . $entry['ipv6_prefixlen'];
    $type = get_ip_type($full_address);
    if (strlen($type))
    {
      $multi_update_db[] = [ 'ipv6_address_id' => $entry['ipv6_address_id'], 'ipv6_type' => $type ];
      echo('.');
    }
  }
  if (count($multi_update_db))
  {
    dbUpdateMulti($multi_update_db, 'ipv6_addresses');
  }
  echo(PHP_EOL);
}

unset($addresses, $multi_update_db, $entry, $full_address, $type, $sql);

// EOF

