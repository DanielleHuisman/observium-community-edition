<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage webui
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

if ($vars['ignoreport'])
{
  if ($readonly)
  {
    print_error_permission('You have insufficient permissions to edit settings.');
  } else {
    include($config['html_dir']."/includes/port-edit.inc.php");
  }

  if ($updated && $update_message)
  {
    print_message($update_message);
  }
  else if ($update_message)
  {
    print_error($update_message);
  }
}

?>
<form id="ignoreport" name="ignoreport" method="post" action="" class="form form-inline">

<div class="box box-solid">
  <div class="box-header with-border">
    <h3 class="box-title">Port Properties</h3>
  </div>
<div class="box-body no-padding">
<?php
  $item = array('id'          => 'ignoreport',
                'readonly'    => $readonly,
                'value'       => 'yes');
  echo(generate_form_element($item, 'hidden'));
  $item = array('id'          => 'device',
                'readonly'    => $readonly,
                'value'       => $device['device_id']);
  echo(generate_form_element($item, 'hidden'));
?>
<table class="table table-striped table-condensed">
  <thead>
    <tr>
      <th class="state-marker"></th>
      <th style="width: 50px;">ifIndex
      </th>
      <th>Port</th>
      <th style="width: 200px;">ifType / Status</th>
      <th style="width: 120px;">Polling (Disable)</th>
      <th style="width: 120px;">Alerts (Ignore)</th>
      <!-- <th style="width: 110px;">% Threshold</th>   -->
      <!-- <th style="width: 110px;">BPS Threshold</th> -->
      <!-- <th style="width: 110px;">PPS Threshold</th> -->
      <th style="width: 90px;">ifSpeed</th>
      <th style="width: 45px;"></th>
      <th style="width: 70px;">64bit</th>
    </tr>
  </thead>
    <tr>
      <td style="padding: 0;"></td>
      <td></td>
      <td><!-- <button class="btn btn-xs btn-danger" type="submit" value="Reset" id="form-reset" title="Reset form to previously-saved settings"><i class="icon-remove icon-white"></i> Reset</button> --></td>
      <td><button class="btn btn-xs btn-danger" style="margin: 3px;" type="submit" value="Alerted"  id="alerted-toggle" title="Toggle alerting on all currently-alerted ports">Enabled & Down</button>
          <button class="btn btn-xs" type="submit" value="Disabled" id="down-select"    title="Disable alerting on all currently-down ports">Disabled</button></td>
      <td class="text-center"><button class="btn btn-xs btn-primary" type="submit" value="Toggle"   id="disable-toggle" title="Toggle polling for all ports">Toggle</button>
          <button class="btn btn-xs btn-default" type="submit" value="Select"   id="disable-select" title="Disable polling on all ports">All</button></td>
      <td class="text-center"><button class="btn btn-xs btn-primary" type="submit" value="Toggle"   id="ignore-toggle"  title="Toggle alerting for all ports">Toggle</button>
          <button class="btn btn-xs btn-default" type="submit" value="Select"   id="ignore-select"  title="Disable alerting on all ports">All</button></td>
      <td></td>
      <td></td>
      <td><button class="btn btn-xs btn-primary" type="submit" value="Save" title="Save current port disable/ignore settings"><i class="icon-ok icon-white"></i> Save</button></td>

<!--      <th></th>
      <th></th>
      <td></th> -->
    </tr>

<?php

$ports_attribs = get_device_entities_attribs($device['device_id'], 'port'); // Get all attribs

foreach (dbFetchRows("SELECT * FROM `ports` WHERE `deleted` = '0' AND `device_id` = ? ORDER BY `ifIndex` ", array($device['device_id'])) as $port)
{
  humanize_port($port);
  //r($port);

  if (isset($ports_attribs['port'][$port['port_id']]))
  {
    $port = array_merge($port, $ports_attribs['port'][$port['port_id']]);
  }

  echo('<tr class="'.$port['row_class'].' vertical-align">');
  echo('<td class="state-marker"></td>');
  echo("<td>". $port['ifIndex']."</td>");
  echo('<td style="vertical-align: top;"><span class="entity">'.generate_entity_link('port', $port).'</span><br />'.escape_html($port['ifAlias']).'</td>');
  echo("<td>".$port['human_type']."<br />");

  echo(($port['admin_status'] == 'enabled' ? '<span class="'.$port['row_class'].'">' : '<span class="">').$port['admin_status'].'</span> / <span data-name="operstatus_'.$port['port_id'].'" class="'.$port['row_class'].'">'. escape_html($port['ifOperStatus']) .'</span></td>');

  echo('<td class="text-center">');
  $item = array('id'            => 'port[]',
                'readonly'      => $readonly,
                'value'         => $port['port_id']);
  echo(generate_form_element($item, 'hidden'));
  $item = array('id'            => 'disabled_'.$port['port_id'],
                'size'          => 'mini',
                'on-text'       => 'Disable',
                'on-color'      => 'danger',
                'off-text'      => 'Yes',
                'off-color'     => 'success',
                'width'         => '58px',
                'readonly'      => $readonly,
                'value'         => $port['disabled']);
  echo(generate_form_element($item, 'switch-ng'));
  echo("</td>");

  echo('<td class="text-center">');
  $item = array('id'            => 'ignore_'.$port['port_id'],
                'size'          => 'mini',
                'on-text'       => 'Ignore',
                'on-color'      => 'danger',
                'off-text'      => 'Yes',
                'off-color'     => 'success',
                'width'         => '58px',
                'readonly'      => $readonly,
                'value'         => $port['ignore']);
  echo(generate_form_element($item, 'switch-ng'));
  echo("</td>");

#  echo('<td>  <input class="input-mini" name="threshold_perc_in-'.$port['port_id'].'" size="3" value="'.$port['threshold_perc_in'].'"></input>');
#  echo('<br /><input class="input-mini" name="threshold_perc_out-'.$port['port_id'].'" size="3" value="'.$port['threshold_perc_out'].'"></input></td>');
#  echo('<td>  <input class="input-mini" name="threshold_bps_in-'.$port['port_id'].'" size="3" value="'.$port['threshold_bps_in'].'"></input>');
#  echo('<br /><input class="input-mini" name="threshold_bps_out-'.$port['port_id'].'" size="3" value="'.$port['threshold_bps_out'].'"></input></td>');
#  echo('<td>  <input class="input-mini" name="threshold_pps_in-'.$port['port_id'].'" size="3" value="'.$port['threshold_pps_in'].'"></input>');
#  echo('<br /><input class="input-mini" name="threshold_pps_out-'.$port['port_id'].'" size="3" value="'.$port['threshold_pps_out'].'"></input></td>');

  // Custom port speed
  echo '<td class="text-nowrap">';
  $ifSpeed_custom_bool = isset($port['ifSpeed_custom']);
  $ifSpeed = $ifSpeed_custom_bool ? $port['ifSpeed_custom'] : $port['ifSpeed'];
  $item = array('id'          => 'ifSpeed_custom_'.$port['port_id'],
                //'name'        => 'Group name',
                'placeholder' => formatRates($port['ifSpeed'], 4, 4),
                'disabled'    => !$ifSpeed_custom_bool,
                'width'       => '75px',
                'readonly'    => $readonly,
                'ajax'        => TRUE,
                'ajax_vars'   => array('field' => 'ifspeed'),
                'value'       => formatRates($ifSpeed, 4, 4));
  echo(generate_form_element($item, 'text'));
  echo('</td>');

  echo '<td>';
  // Custom port speed toggle switch
  $item = array('id'       => 'ifSpeed_custom_bool_'.$port['port_id'],
                        'size'     => 'large',
                        'view'     => $locked ? 'lock' : 'square', // note this is data-tt-type, but 'type' key reserved for element type
                        'onchange' => "toggleAttrib('disabled', obj.attr('data-onchange-id'));",
                        'onchange-id' => "ifSpeed_custom_".$port['port_id'], // target id for onchange, set attrib: data-onchange-id
                        'value' => (bool)$ifSpeed_custom_bool);
          echo(generate_form_element($item, 'toggle'));

  echo '</span>';

  echo('</td>');

  echo '<td>';
  if ($port['port_64bit'] == 1)
  {
    echo '<span class="label label-success">64bit</span>';
  }
  else if ($port['port_64bit'] == 0)
  {
    echo '<span class="label label-warning">32bit</span>';
  } else {
    echo '<span class="label">Unchecked</span>';
  }

  echo '</td></tr>'.PHP_EOL;

  $row++;
}
?>
</table>
</div>

<div id="submit" class="box-footer">
  <?php
  $item = array('id'          => 'submit',
                'name'        => 'Save Changes',
                'class'       => 'btn-primary pull-right',
                'icon'        => 'icon-ok icon-white',
                'readonly'    => $readonly,
                'value'       => 'save');
  echo(generate_form_element($item, 'submit'));
  ?>
</div>

</form>

<script type="text/javascript">

  $('#disable-toggle').click(function(event) {
    // invert selection on all disable buttons
    event.preventDefault();
    $('[id^="disabled_"]').each(function() {
      var id = $(this).attr('id');
      // get the interface number from the object name
      var port_id = id.split('_')[1];
      // find its corresponding checkbox and toggle it
      //$('[id="disabled_' + port_id + '"]').bootstrapSwitch('toggleState');
      $('[id="disabled_' + port_id + '"]').bootstrapToggle('toggle');
    });
  });
  $('#ignore-toggle').click(function(event) {
    // invert selection on all ignore buttons
    event.preventDefault();
    $('[id^="ignore_"]').each(function() {
      var id = $(this).attr('id');
      // get the interface number from the object name
      var port_id = id.split('_')[1];
      // find its corresponding checkbox and toggle it
      //$('[id="ignore_' + port_id + '"]').bootstrapSwitch('toggleState');
      $('[id="ignore_' + port_id + '"]').bootstrapToggle('toggle');
    });
  });
  $('#alerted-toggle').click(function(event) {
    // toggle ignore buttons for all ports which are in class red
    event.preventDefault();
    $('.error').each(function() {
      var name = $(this).attr('data-name');
      if (name) {
        // get the interface number from the object name
        var port_id = name.split('_')[1];
        // find its corresponding checkbox and toggle it
        //$('[id="ignore_' + port_id + '"]').bootstrapSwitch('state', true);
        $('[id="ignore_' + port_id + '"]').bootstrapToggle('on');
      }
    });
  });

  $('#disable-select').click(function(event) {
    // select all disable buttons
    event.preventDefault();
    //$('[id^="disabled_"]').bootstrapSwitch('state', true);
    $('[id^="disabled_"]').bootstrapToggle('on');
  });
  $('#ignore-select').click(function(event) {
    // select all ignore buttons
    event.preventDefault();
    //$('[id^="ignore_"]').bootstrapSwitch('state', true);
    $('[id^="ignore_"]').bootstrapToggle('on');
  });
  $('#down-select').click(function(event) {
    // select ignore buttons for all ports which are down
    event.preventDefault();
    $('[data-name^="operstatus_"]').each(function() {
      var name = $(this).attr('data-name');
      var text = $(this).text();
      if (name && text == 'down' || name && text == 'lowerLayerDown') {
        // get the interface number from the object name
        var port_id = name.split('_')[1];
        // find its corresponding checkbox and toggle it
        //$('[id="ignore_' + port_id + '"]').bootstrapSwitch('state', true);
        $('[id="ignore_' + port_id + '"]').bootstrapToggle('on');
      }
    });
  });

  $('#form-reset').click(function(event) {
    // reset objects in the form to their previous values
    event.preventDefault();
    $('#ignoreport')[0].reset();
  });

</script>

<?php

// EOF
