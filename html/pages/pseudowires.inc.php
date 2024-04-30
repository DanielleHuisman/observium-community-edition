<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

register_html_title("Pseudowires");

$link_array = ['page' => 'pseudowires'];
$link_array = array_merge($link_array, $vars);

$navbar = ['brand' => "Pseudowires", 'class' => "navbar-narrow"];

if (!isset($vars['type'])) {
    $navbar['options']['all']['class'] = "active";
}
$navbar['options']['all']['url']  = generate_url($link_array, ['pwtype' => NULL]);
$navbar['options']['all']['text'] = "All Types";

$vars_filter = $vars;
unset($vars_filter['pwtype']); // Do not filter type

$sql = generate_pseudowire_query($vars_filter);

$pw_types = [];
foreach (dbFetchRows($sql) as $pw) {
    $pw_type  = $pw['pwType'];
    $pw_label = nicecase($pw_type);

    // Combine different types with same label
    if (!in_array($pw_type, (array)$pw_types[$pw_label])) {
        $pw_types[$pw_label][] = $pw_type;
    }
}
ksort($pw_types);

foreach ($pw_types as $text => $type) {

    if($type[0] == "") { continue; }

    $type = implode(',', $type);

    if ($vars['pwtype'] == $type) {
        $navbar['options'][$type]['class'] = "active";
        unset($navbar['options']['all']['class']);
    }
    $navbar['options'][$type]['url']  = generate_url($link_array, ['pwtype' => $type]);
    $navbar['options'][$type]['text'] = $text;
}

// Groups
$groups = get_type_groups('pseudowire');

$navbar['options']['group'] = ['text' => 'Groups', 'right' => TRUE, 'community' => FALSE];
foreach ($groups as $group) {
    if (!empty($vars['group']) && ($group['group_id'] == $vars['group'] || in_array($group['group_id'], (array)$vars['group']))) {
        $navbar['options']['group']['class']                                   = 'active';
        $navbar['options']['group']['url']                                     = generate_url($vars, ['group' => NULL]);
        $navbar['options']['group']['text']                                    .= " (" . $group['group_name'] . ')';
        $navbar['options']['group']['suboptions'][$group['group_id']]['url']   = generate_url($vars, ['group' => NULL]);
        $navbar['options']['group']['suboptions'][$group['group_id']]['class'] = 'active';
    } else {
        $navbar['options']['group']['suboptions'][$group['group_id']]['url'] = generate_url($vars, ['group' => $group['group_id']]);
    }
    $navbar['options']['group']['suboptions'][$group['group_id']]['text'] = $group['group_name'];
}

// Graphs
$navbar['options']['graphs']['text']  = 'Graphs';
$navbar['options']['graphs']['icon']  = $config['icon']['graphs'];
$navbar['options']['graphs']['right'] = TRUE;

if ($vars['view'] == "graphs") {
    if (!$vars['graph']) {
        $vars['graph'] = 'pseudowire_bits';
    }
    unset($vars['view']);
} else {
    $navbar['options']['graphs']['url'] = generate_url($vars, ['view' => "graphs"]);
}

foreach ($cache['graphs'] as $entry) {
    if (preg_match('/^(pseudowire_(\w+))/', $entry, $matches)) {
        $graph = $matches[1];
        if (!isset($navbar['options']['graphs']['suboptions'][$graph])) {
            $navbar['options']['graphs']['suboptions'][$graph] = ['text' => nicecase($matches[2])];
            if ($graph == $vars['graph']) {
                $navbar['options']['graphs']['class']                       = 'active';
                $navbar['options']['graphs']['url']                         = generate_url($vars, ['view' => NULL]);
                $navbar['options']['graphs']['text']                        .= " (" . nicecase($matches[2]) . ')';
                $navbar['options']['graphs']['suboptions'][$graph]['url']   = generate_url($vars, ['graph' => NULL]);
                $navbar['options']['graphs']['suboptions'][$graph]['class'] = 'active';
            } else {
                $navbar['options']['graphs']['suboptions'][$graph]['url'] = generate_url($vars, ['graph' => $graph]);
            }
        }
    }
}

/*
if ($vars['view'] == "graphs")
{
  $navbar['options']['graphs']['class'] = 'active';
  $navbar['options']['graphs']['url']   = generate_url($vars, [ 'view' => NULL ]);
} else {
  $navbar['options']['graphs']['url']    = generate_url($vars, [ 'view' => "graphs" ]);
}
*/
print_navbar($navbar);
unset($navbar);

print_pseudowire_form($vars);

// Pagination
$vars['pagination'] = TRUE;

// Print pseudowires
print_pseudowire_table($vars);

// EOF
