<?php

$groups = cache_groups();

echo ' Migrating groups to new format ';

foreach($groups['group'] as $group)
{
  if(!$group['group_assoc']) {

    $ruleset = migrate_assoc_rules($group);

    $query = parse_qb_ruleset($group['entity_type'], $ruleset);

    $data = dbFetchRows($query);
    $error = dbError();

    $field = $config['entities'][$group['entity_type']]['table_fields']['id'];

    $existing_entities = get_group_entities($group['group_id']);
    $entities = array();

    foreach($data as $datum)
    {
      $entities[$datum[$field]] = $datum[$field];
    }

    dbUpdate(array('group_assoc' => json_encode($ruleset)), 'groups', '`group_id` = ?', array($group['group_id']));
    //dbDelete('groups_assoc', '`group_id` = ?', array($group['group_id']));
  }
}
