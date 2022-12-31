<?php

/*
<div class="navbar navbar-narrow" style="">
    <div class="navbar-inner">
      <div class="container">
        <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target="#nav-0uGMD3QMVHudRAti">
          <span class="oicon-bar"></span>
        </button>
        <a class="brand ">Weathermap</a><div class="nav-collapse" id="nav-0uGMD3QMVHudRAti">
          <ul class="nav">
          <li class=""><a id="tb_newfile">Change File</a></li>
          <li class=""><a id="tb_addnode">Add Node</a></li>
          <li class=""><a id="tb_addlink">Add Link</a></li>
          <li class=""><a id="tb_poslegend">Move Legend</a></li>
          <li class=""><a id="tb_postime">Move Timestamp</a></li>
          <li class=""><a id="tb_mapprops">Map Properties</a></li>
          <li class=""><a id="tb_mapstyle">Map Style</a></li>
          <!-- <li class=""><a id="tb_colours">Manage Colors</a></li> -->
          <!-- <li class=""><a id="tb_manageimages">Manage Images</a></li> -->
          <li class=""><a id="tb_prefs">Editor Settings</a></li>
          <li class=""><a id="tb_coords">Position ----, ---</a></li>
          <li class="tb_help"><span id="tb_help">or click a Node or Link to edit it's properties</span></li>
        </div>
      </div>
    </div>
  </div>
*/

if($_SESSION['userlevel'] > 5 && $config['weathermap']['enable']) {

  $navbar['class'] = 'navbar-narrow';
  $navbar['brand'] = 'Weathermaps';

  // Allow use of wmap_id as well as mapname
  if (isset($vars['wmap_id'])) {
    $vars['mapname'] = dbFetchCell("SELECT `wmap_name` FROM `weathermaps` WHERE `wmap_id` = ?", array($vars['wmap_id']));
  }
  if (isset($vars['mapname']) && dbExist("weathermaps", "`wmap_name` = ?", array($vars['mapname']))) {
    if ($_SESSION['userlevel'] > 7) {
      $editing = TRUE;
    }
  } else {
    unset($vars['mapname']);
  }

  //$editing = TRUE;

  if ($editing === TRUE) {
    $navbar['options']['add_node']['text'] = 'Add Node';
    $navbar['options']['add_node']['id']   = 'tb_addnode';
    $navbar['options']['add_link']['text'] = 'Add Link';
    $navbar['options']['add_link']['id']   = 'tb_addlink';

    /* Disabled for space reasons. Can move these by clicking the elements directly.
    $navbar['options']['tb_poslegend']['text'] = 'Move Legend';
    $navbar['options']['tb_poslegend']['id']   = 'tb_poslegend';
    $navbar['options']['tb_postime']['text'] = 'Move Time';
    $navbar['options']['tb_postime']['id']   = 'tb_postime';
    */

    $navbar['options']['tb_mapprops']['text'] = 'Map Properties';
    $navbar['options']['tb_mapprops']['id']   = 'tb_mapprops';
    $navbar['options']['tb_mapstyle']['text'] = 'Map Style';
    $navbar['options']['tb_mapstyle']['id']   = 'tb_mapstyle';
    $navbar['options']['tb_prefs']['text']    = 'Settings';
    $navbar['options']['tb_prefs']['id']      = 'tb_prefs';

    $navbar['options_right']['help']['id']        = 'tb_help';
    $navbar['options_right']['help']['text']      = '';
    $navbar['options_right']['tb_coords']['text'] = 'Return to List';
    $navbar['options_right']['tb_coords']['icon'] = 'sprite-return';
    $navbar['options_right']['tb_coords']['url']  = generate_url(array('page' => "wmap"));
    $navbar['options_right']['tb_coords']['id']   = 'tb_coords';
  } else if (isset($vars['mapname'])) {

    //  $navbar['options_right']['edit']['text'] = 'Edit Map';
    //  $navbar['options_right']['edit']['icon'] = 'sprite-cog';
    //  $navbar['options_right']['edit']['url']  = generate_url(array('page' => "wmap", 'mapname' => $vars['mapname'], 'edit' => TRUE));

  }

  // Print out the navbar defined above
  print_navbar($navbar);
  unset($navbar);

  if (isset($vars['mapname'])) {

    if ($editing === TRUE) {
      include($config['install_dir'] . "/includes/weathermap/editor.php");
    } else {
      echo '<div class="box box-solid">';
      echo '<img src="/weathermap.php?mapname=' . htmlentities($vars['mapname']) . '&action=draw&unique=' . time() . '">';
      echo '</div>';
    }

  } else {

    echo '<div class="row">';

    foreach (dbFetchRows("SELECT * FROM `weathermaps`") as $wmap) {

      echo '
    <div class="box box-solid" style="float: left; margin-left: 10px; margin-bottom: 10px;  width:612px; min-width: 612px; max-width:612px; min-height:500px; max-height:500px;">
      <div class="box-header with-border">
        <a href="' . generate_url(array('page' => "wmap", 'mapname' => $wmap['wmap_name'])) . '"><h3 class="box-title">' . htmlentities($wmap['wmap_name']) . '</h3>
        </a>
      </div>
      <div class="box-body">
       <div style="position:absolute; width:100%; height:100%">
        <a href="' . generate_url(array('page' => "wmap", 'mapname' => $wmap['wmap_name'])) . '">
          <img src="/weathermap.php?mapname=' . htmlentities($wmap['wmap_name']) . '&action=draw&unique=' . time() . '&width=590&height=490">
        </a>
        </div>
      </div>
    </div>
    ';

    }

    echo '</div>';

  }
} else {

  print_error("Not Permitted");

}



?>
