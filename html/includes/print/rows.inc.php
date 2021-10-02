<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

// These functions are used to generate our boxes. It's probably easier to put this into functions.

function generate_box_open($args = array())
{
  // r($args);

  $return = '<div ';
  if (isset($args['id'])) {  $return .= 'id="' . $args['id'] . '" '; }

  $return .= 'class="' . OBS_CLASS_BOX . ($args['box-class'] ? ' '.$args['box-class'] : '') . '" '.($args['box-style'] ? 'style="'.$args['box-style'].'"' : ''). '>' . PHP_EOL;

  if (isset($args['title']))
  {
    $return .= '  <div class="box-header' . ($args['header-border'] ? ' with-border' : '') . '">'.PHP_EOL;
    if(isset($args['url'])) {  $return .= '<a href="'.$args['url'].'">'; }
    if(isset($args['icon'])) {  $return .= '<i class="'.$args['icon'].'"></i>'; }
    $return .= '<' . (isset($args['title-element']) ? $args['title-element'] : 'h3').' class="box-title"';
    $return .= (isset($args['title-style']) ? ' style="'.$args['title-style'].'"' : '');
    $return .= '>';
    $return .= $args['title'].'</' . (isset($args['title-element']) ? $args['title-element'] : 'h3').'>'.PHP_EOL;
    if(isset($args['url'])) {  $return .= '</a>'; }

    if (isset($args['header-controls']) && is_array($args['header-controls']['controls']))
    {
      $return .= '    <div class="box-tools pull-right">';

      foreach($args['header-controls']['controls'] as $control)
      {
        if (isset($control['anchor']) && $control['anchor'] == TRUE)
        {
          $return .= ' <a role="button"';
        } else {
          $return .= '<button type="button"';
        }
        if (isset($control['url']) && strlen($control['url']) && $control['url'] !== '#') {
          $return .= ' href="'.$control['url'].'"';
        } else {
          //$return .= ' onclick="return false;"';
        }

        $return .= ' class="btn btn-box-tool';
        if (isset($control['class'])) { $return .= ' '.$control['class']; }
        $return .= '"';

        if (isset($control['data']))  { $return .= ' '.$control['data']; }
        $return .= '>';

        if (isset($control['icon'])) { $return .= '<i class="'.$control['icon'].'"></i> '; }
        if (isset($control['text'])) { $return .= $control['text']; }

        if (isset($control['anchor']) && $control['anchor'] == TRUE) {
          $return .= '</a>';
        } else {
          $return .= '</button>';
        }
      }

      $return .= '    </div>';
    }
    $return .= '  </div>'.PHP_EOL;
  }

  $return .= '  <div class="box-body'.($args['padding'] ? '' : ' no-padding').'"';
  if (isset($args['body-style']))
  {
    $return .= ' style="'.$args['body-style'].'"';
  }
  $return .= '>'.PHP_EOL;
  return $return;

}

function generate_box_close($args = array())
{
  $return  = '  </div>' . PHP_EOL;

  if(isset($args['footer_content']))
  {
    $return .= '  <div class="box-footer';
    if(isset($args['footer_nopadding'])) { $return .= ' no-padding'; }
    $return .= '">';
    $return .= $args['footer_content'];
    $return .= '  </div>' . PHP_EOL;
  }

  $return .= '</div>' . PHP_EOL;
  return $return;
}

// DOCME needs phpdoc block
function print_graph_row_port($graph_array, $port)
{

  global $config;

  $graph_array['to']     = $config['time']['now'];
  $graph_array['id']     = $port['port_id'];

  print_graph_row($graph_array);
}

function generate_graph_summary_row($graph_summary_array, $state_marker = FALSE)
{

  global $config;

  $graph_array = $graph_summary_array;

  unset($graph_array['types']);

  if ($_SESSION['widescreen'])
  {
    if ($_SESSION['big_graphs'])
    {
      if (!$graph_array['height']) { $graph_array['height'] = "110"; }
      if (!$graph_array['width']) { $graph_array['width']  = "372"; }
      $limit = 4;
    } else {
      if (!$graph_array['height']) { $graph_array['height'] = "110"; }
      if (!$graph_array['width']) { $graph_array['width']  = "287"; }
      $limit = 5;
    }
  } else {
    if ($_SESSION['big_graphs'])
    {
      if (!$graph_array['height']) { $graph_array['height'] = "100"; }
      if (!$graph_array['width']) { $graph_array['width']  = "323"; }
      $limit = 3;
    } else {
      if (!$graph_array['height']) { $graph_array['height'] = "100"; }
      if (!$graph_array['width']) { $graph_array['width']  = "228"; }
      $limit = 4;
    }
  }

  if(!isset($graph_summary_array['period'])) { $graph_summary_array['period'] = "day"; }
  if($state_marker) { $graph_array['width'] -= 2; }
  $graph_array['to']     = $config['time']['now'];
  $graph_array['from']   = $config['time'][$graph_summary_array['period']];

  $graph_rows = array();
  foreach ($graph_summary_array['types'] as $graph_type)
  {

// FIX THIS LATER :DDDD
//    $hide_lg = $period[0] === '!';
//    if ($hide_lg)
//    {
//      $period = substr($period, 1);
//    }


    $graph_array['type']        = $graph_type;

    preg_match('/^(?P<type>[a-z0-9A-Z-]+)_(?P<subtype>[a-z0-9A-Z-_]+)/', $graph_type, $type_parts);
    if (isset($config['graph_types'][$type_parts['type']][$type_parts['subtype']]['descr']))
    {
      $descr = $config['graph_types'][$type_parts['type']][$type_parts['subtype']]['descr'];
    } else {
      $descr = nicecase($graph_type);
    }

    $graph_array_zoom           = $graph_array;
    $graph_array_zoom['height'] = "175";
    $graph_array_zoom['width']  = "600";
    unset($graph_array_zoom['legend']);

    $link_array = $graph_array;
    $link_array['page'] = "graphs";
    unset($link_array['height'], $link_array['width']);
    $link = generate_url($link_array);

    $popup_contents  = '<h3>'.$descr.'</h3>';
    $popup_contents .= generate_graph_tag($graph_array_zoom);

    $graph_link = overlib_link($link, generate_graph_tag($graph_array), $popup_contents,  NULL);

//    if ($hide_lg)
//    {
      // Hide this graph on lg/xl screen since it not fit to single row (on xs always visible, since here multirow)
//      $graph_link = '<div class="visible-xs-inline visible-lg-inline visible-xl-inline">' . $graph_link . '</div>';
//    }
    if(count($graph_rows) < $limit) {
      $graph_rows[] = $graph_link;
    }

  }

  return implode(PHP_EOL, $graph_rows);
}


// DOCME needs phpdoc block
function generate_graph_row($graph_array, $state_marker = FALSE)
{
  global $config;

  if ($_SESSION['widescreen'])
  {
    if ($_SESSION['big_graphs'])
    {
      if (!$graph_array['height']) { $graph_array['height'] = "110"; }
      if (!$graph_array['width']) { $graph_array['width']  = "372"; }
      $periods = array('day', 'week', 'month', 'year');
    } else {
      if (!$graph_array['height']) { $graph_array['height'] = "110"; }
      if (!$graph_array['width']) { $graph_array['width']  = "287"; }
      $periods = array('day', 'week', 'month', 'year', 'twoyear');
    }
  } else {
    if ($_SESSION['big_graphs'])
    {
      if (!$graph_array['height']) { $graph_array['height'] = "100"; }
      if (!$graph_array['width']) { $graph_array['width']  = "323"; }
      $periods = array('day', 'week', '!month');
    } else {
      if (!$graph_array['height']) { $graph_array['height'] = "100"; }
      if (!$graph_array['width']) { $graph_array['width']  = "228"; }
      $periods = array('day', 'week', 'month', '!year');
    }
  }

  if ($graph_array['shrink']) { $graph_array['width'] = $graph_array['width'] - $graph_array['shrink']; }

  // If we're printing the row inside a table cell with "state-marker", we need to make the graphs a tiny bit smaller to fit
  if($state_marker) { $graph_array['width'] -= 2; }

  $graph_array['to']     = $config['time']['now'];

  $graph_rows = array();
  foreach ($periods as $period)
  {
    $hide_lg = $period[0] === '!';
    if ($hide_lg)
    {
      $period = substr($period, 1);
    }
    $graph_array['from']        = $config['time'][$period];
    $graph_array_zoom           = $graph_array;
    $graph_array_zoom['height'] = "175";
    $graph_array_zoom['width']  = "600";
    unset($graph_array_zoom['legend']);

    $link_array = $graph_array;
    $link_array['page'] = "graphs";
    unset($link_array['height'], $link_array['width']);
    $link = generate_url($link_array);

    $graph_link = overlib_link($link, generate_graph_tag($graph_array), generate_graph_tag($graph_array_zoom),  NULL);
    if ($hide_lg)
    {
      // Hide this graph on lg/xl screen since it not fit to single row (on xs always visible, since here multirow)
      $graph_link = '<div class="visible-xs-inline visible-lg-inline visible-xl-inline">' . $graph_link . '</div>';
    }
    $graph_rows[] = $graph_link;
  }

  return implode(PHP_EOL, $graph_rows);
}

function print_graph_row($graph_array, $state_marker = FALSE)
{
  echo(generate_graph_row($graph_array, $state_marker));
}

function print_graph_summary_row($graph_array, $state_marker = FALSE)
{
  echo(generate_graph_summary_row($graph_array, $state_marker));
}


// EOF
