<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

register_html_resource('css', 'simplemde.min.css');
register_html_resource('js', 'simplemde.min.js');

if (($_SESSION['userlevel'] >= 7 || is_entity_write_permitted($device['device_id'], 'device')) &&
    isset($vars['notes_text']) && is_string($vars['notes_text']) && request_token_valid($vars))
{
  set_entity_attrib('device', $device['device_id'], 'notes', $vars['notes_text'], $device['device_id']);
  unset($vars['notes_text']);
  if (isset($attribs) && is_array($attribs))
  {
    $attribs['notes'] = $vars['notes_text'];
  }
}

$notes = get_entity_attrib('device', $device['device_id'], 'notes');

if ($vars['edit'])
{

  echo generate_box_open();
  echo '<form method="POST" id="edit" name="edit" action="'.generate_url($vars, array('edit' => NULL)).'" class="form form-horizontal" style="margin-bottom: 0px;">';

  // Add CSRF Token
  if (isset($_SESSION['requesttoken']))
  {
    echo generate_form_element([ 'type' => 'hidden', 'id' => 'requesttoken', 'value' => $_SESSION['requesttoken'] ]) . PHP_EOL;
  }
  //echo generate_form_element([ 'type' => 'textarea', 'id' => 'notes_text', 'value' => $notes ]) . PHP_EOL; // not know why, this broke form
  echo '  <textarea name="notes_text" id="notes_text">' . escape_html($notes) . '</textarea>';

  echo generate_box_close();
  echo '    <button id="submit" name="submit" type="submit" class="btn btn-primary text-nowrap pull-right" value="save"><i class="icon-ok icon-white" style="margin-right: 0px;"></i>&nbsp;&nbsp;Save Changes</button>  ';
  echo '  </form>';

  register_html_resource('script', 'var simplemde = new SimpleMDE();');

} else {

  echo generate_box_open(array('padding' => TRUE));
  echo get_markdown($notes);
  echo generate_box_close();

  if (($_SESSION['userlevel'] >= 7 || is_entity_write_permitted($device['device_id'], 'device')) )
  {
    echo '<a href="'.generate_url($vars, array('edit' => TRUE)).'" id="edit" name="edit" type="submit" class="btn btn-primary text-nowrap pull-right" value="edit"><i class="icon-ok icon-white" style="margin-right: 0px;"></i>&nbsp;&nbsp;Edit Notes</a>';
  }
  
}

// EOF
