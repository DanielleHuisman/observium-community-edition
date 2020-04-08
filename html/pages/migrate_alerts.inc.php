<?php

if ($_SESSION['userlevel'] < '7') { print_warning("Insufficient Permissions"); exit(); }

echo generate_box_open(array('title' => 'Alert Association Rule Migration', 'padding' => TRUE, 'header-border' => TRUE));

$checkers = cache_alert_rules();
$assocs   = cache_alert_assoc();

foreach($assocs as $assoc)
{
   $checkers[$assoc['alert_test_id']]['assocs'][] = $assoc;
}

//echo '<h4>Alert Rules</h4>';

echo 'This page will assist you to migrate from legacy association format to the new association format.';

//print_vars($checkers);
echo generate_box_close();

foreach($checkers as $alert)
{

  if($vars['migrate'] == $alert['alert_test_id'] || (!$alert['alert_assoc'] && $vars['migrate'] == 'all'))
  {

    print_message("Migrating ".$alert['alert_name']);

    $ruleset = migrate_assoc_rules($alert);

    dbUpdate(array('alert_assoc' => json_encode($ruleset)), 'alert_tests', '`alert_test_id` = ?', array($alert['alert_test_id']));
    dbDelete('alert_assoc', '`alert_test_id` = ?', array($alert['alert_test_id']));
    $alert['alert_assoc'] = json_encode($ruleset);
    update_alert_table($alert, FALSE);

  }

}

$checkers = cache_alert_rules();
$assocs   = cache_alert_assoc();

foreach($assocs as $assoc)
{
  $checkers[$assoc['alert_test_id']]['assocs'][] = $assoc;
}

foreach($checkers as $alert)
{

   //if($alert['alert_test_id'] != 11) { continue; }

   echo generate_box_open(array('title' => $alert['alert_name'], 'padding' => TRUE, 'header-border' => TRUE));

   if(!$alert['alert_assoc']) {

      $ruleset = migrate_assoc_rules($alert);

      $query = parse_qb_ruleset($alert['entity_type'], $ruleset, TRUE);

      $data = dbFetchRows($query);
      $error = dbError();

      $field = $config['entities'][$alert['entity_type']]['table_fields']['id'];

      $existing_entities = get_alert_entities($alert['alert_test_id']);
      $entities = array();

      foreach($data as $datum)
      {
         $entities[$datum[$field]] = array('entity_id' => $datum[$field], 'device_id' => $datum['device_id']);
      }

      $legacy = get_alert_entities_from_assocs($alert);

      //r($legacy);
      //r($entities);
      //r($existing_entities);

      $add = array_diff_key($entities, $existing_entities);
      $remove = array_diff_key($existing_entities, $entities);

      //print_vars(array_diff_key($legacy, $entities));
      //echo count($add) .' to add, '.count($remove).' to remove. <br />';

      $changed = count($add) + count($remote);

      $result = count($data);
      $existing = count(get_alert_entities($alert['alert_test_id']));

      echo '<div class="row">';
      echo '<div class="col-md-6">';
      echo '<div class="box box-solid">';

      if(is_array($alert['assocs']))
      {

         echo '<h4>Original Association Rules</h4>';

         echo('<table class="table table-condensed-more table-striped" style="margin-bottom: 0px;">');

         // Loop the associations which link this alert to this device
         foreach ($alert['assocs'] as $assoc_id => $assoc)
         {

            echo('<tr>');
            echo('<td style="width: 50%">');
            if (is_array($assoc['device_attribs']))
            {
               $text_block = array();
               foreach ($assoc['device_attribs'] as $attribute)
               {
                  $text_block[] = escape_html($attribute['attrib'].' '.$attribute['condition'].' '.$attribute['value']);
               }
               echo('<code>'.implode($text_block,'<br />').'</code>');
            } else {
               echo '<code>*</code>';
            }

            echo('</td>');

            echo('<td>');
            if (is_array($assoc['entity_attribs']))
            {
               $text_block = array();
               foreach ($assoc['entity_attribs'] as $attribute)
               {
                  $text_block[] = escape_html($attribute['attrib'].' '.$attribute['condition'].' '.$attribute['value']);
               }
               echo('<code>'.implode($text_block,'<br />').'</code>');
            } else {
               echo '<code>*</code>';
            }
            echo('</td>');

            echo('</tr>');

         }
         // End loop of associations
         echo '</table>';
      } else {

         print_message("No Legacy Associations!");

      }
      echo '</div></div>';

      echo '<div class="col-md-6">
              <div class="box box-solid">
                <h4>New Association Ruleset</h4>';
      echo render_qb_rules($alert['entity_type'], $ruleset);
      echo '  </div>
            </div>';

      echo '</div>';

      $c_exist  = count($existing_entities);
      $c_legacy = count($legacy);
      $c_new    = count($entities);

      //echo '<table class="table table-bordered"><tr><th>Database</th><td>'.count($existing_entities).'</td><th>Legacy Rules</th><td>'.count($legacy).'</td><th>New Ruleset</th><td>'.count($entities).'</td></tr></table>';

      if ($changed != 0) {

        print_error("Migration results don't match for group ".$alert['alert_name'].". Please report this to the Observium developers. (<b>DB</b>: ". $c_exist ." | <b>Old</b>: ".$c_legacy." | <b>New</b>: ".$c_new.")");

        echo '<pre>';
         print_r($alert['assocs']);
         echo '</pre>';
         echo '<pre>' . _json_encode($ruleset, JSON_PRETTY_PRINT) . '</pre>';

         r($query);

         //r($add);
         //r($remove);

      } else {
        print_message("Migration results match! (<b>DB</b>: ". $c_exist ." | <b>Old</b>: ".$c_legacy." | <b>New</b>: ".$c_new.")", 'success');

        echo '<a class="btn btn-primary" href="'.generate_url($vars, array('migrate' => $alert['alert_test_id'])).'">Migrate Alert</a>';

      }

      //echo "results: " . $result . "<br />";
      //echo "existing:" . $existing . "<br />";
      //echo "error:" . $error;

      //echo "</span>";


   } else {

      echo 'Alert '.$alert['alert_name']. ' already has new-style association rules <br />';

   }
   echo generate_box_close();
}