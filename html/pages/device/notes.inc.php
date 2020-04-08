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

register_html_resource('css', 'simplemde.min.css');
register_html_resource('js', 'simplemde.min.js');

if($_SESSION['userlevel'] >= 7 && isset($vars['notes_text']))
{
  set_entity_attrib('device', $device['device_id'], 'notes', $vars['notes_text'], $device['device_id']);
  unset($vars['notes_text']);
}

$notes = get_entity_attrib('device', $device['device_id'], 'notes');



if ($vars['edit']) {

  echo generate_box_open();
  echo '<form method="POST" id="edit" name="edit" action="'.generate_url($vars, array('edit' => NULL)).'" class="form form-horizontal" style="margin-bottom: 0px;">';

  echo '  <textarea name="notes_text" id="notes_text">' . $notes . '</textarea>';

  echo generate_box_close();
  echo '    <button id="submit" name="submit" type="submit" class="btn btn-primary text-nowrap pull-right" value="save"><i class="icon-ok icon-white" style="margin-right: 0px;"></i>&nbsp;&nbsp;Save Changes</button>  ';
  echo '  </form>';

  echo '<script>
var simplemde = new SimpleMDE();
</script>';

} else {

  $Parsedown = new Parsedown();
  echo generate_box_open(array('padding' => TRUE));
  echo $Parsedown->text($notes);
  echo generate_box_close();

  if ($_SESSION['userlevel'] >= 7)
  {
    echo '<a href="'.generate_url($vars, array('edit' => TRUE)).'" id="edit" name="edit" type="submit" class="btn btn-primary text-nowrap pull-right" value="edit"><i class="icon-ok icon-white" style="margin-right: 0px;"></i>&nbsp;&nbsp;Edit Notes</a>';
  }
  
}