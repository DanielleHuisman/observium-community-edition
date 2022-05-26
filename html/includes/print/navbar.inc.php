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

/**
 * Generate Bootstrap-format Navbar
 *
 *   A little messy, but it works and lets us move to having no navbar markup on pages :)
 *   Examples:
 *   print_navbar(array('brand' => "Apps", 'class' => "navbar-narrow", 'options' => array('mysql' => array('text' => "MySQL", 'url' => generate_url($vars, 'app' => "mysql")))))
 *
 * @param array $vars
 * @return null
 *
 */
function print_tabbar($tabbar)
{
  $output = '<ul class="nav nav-tabs">';

  foreach ($tabbar['options'] as $option => $array)
  {
    if ($array['right'] == TRUE) { $array['class'] .= ' pull-right'; }
    $output .= '<li class="' . $array['class'] . '">';
    $output .= '<a href="'.$array['url'].'">';
    if (isset($array['icon']))
    {
      $output .= '<i class="'.$array['icon'].'"></i> ';
    }

    $output .= $array['text'].'</a></li>';
  }
  $output .= '</ul>';

  echo $output;
}

/**
 * Generate Bootstrap-format navigation bar
 *
 *   A little messy, but it works and lets us move to having no navbar markup on pages :)
 *   Examples:
 *   print_navbar(array('brand' => "Apps", 'class' => "navbar-narrow", 'options' => array('mysql' => array('text' => "MySQL", 'url' => generate_url($vars, 'app' => "mysql")))))
 *
 * @param array $vars
 * @return void
 *
 */
function print_navbar($navbar) {
  global $config;

  if (OBSERVIUM_EDITION === 'community' && isset($navbar['community']) && $navbar['community'] === FALSE) {
    // Skip nonexistent features on community edition
    return;
  }

  $id = strgen();
  // Detect allowed screen ratio for current browser, cached!
  $ua_info = detect_browser();

  ?>

  <div class="navbar <?php echo $navbar['class']; ?>" style="<?php echo $navbar['style']; ?>">
    <div class="navbar-inner">
      <div class="container">
        <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target="#nav-<?php echo $id; ?>">
          <span class="oicon-bar"></span>
        </button>

  <?php

  if (isset($navbar['brand'])) {
    echo ' <a class="brand '.(isset($navbar['brand-class']) ? $navbar['brand-class'] : '' ).'">'.$navbar['brand'].'</a>';
  }

  echo('<div class="nav-collapse" id="nav-'.$id.'">');

  //rewrite navbar (for class pull-right)
  $newbar = array();
  foreach (array('options', 'options_right') as $array_name) {
    if (isset($navbar[$array_name])) {
      foreach ($navbar[$array_name] as $option => $array) {
        if (isset($array['userlevel']) && isset($_SESSION['userlevel']) &&
            $_SESSION['userlevel'] < $array['userlevel']) {
          // skip not permitted menu items
          continue;
        }
        if (OBSERVIUM_EDITION === 'community' &&
            isset($array['community']) && $array['community'] === FALSE) {
          // Skip not exist features on community
          continue;
        }

        if ($array_name === 'options_right' || $array['right'] || str_contains($array['class'], 'pull-right')) {
          $array['class'] = str_replace('pull-right', '', $array['class']);
          $newbar['options_right'][$option] = $array;
        } else {
          $newbar['options'][$option] = $array;
        }
      }
    }
  }

  foreach (array('options', 'options_right') as $array_name) {
    if ($array_name === 'options_right') {
      if (!$newbar[$array_name]) { break; }
      echo('<ul class="nav pull-right">');
    } else {
      echo('<ul class="nav">');
    }

    foreach ($newbar[$array_name] as $option => $array) {

      // if($array['divider']) { echo '<li class="divider"></li>'; break;}

      if (!is_array($array['suboptions'])) {
        echo('<li class="'.$array['class'].'">');

        $attribs = [];
        if (isset($array['tooltip'])) {
          $array['alt'] = $array['tooltip'];
        }
        if (isset($array['alt'])) {
          $attribs['data-rel'] = 'tooltip';
          $attribs['data-tooltip'] = $array['alt'];
        }
        if (isset($array['id'])) {
          $attribs['id'] = $array['id'];
        }

        $link_opts = generate_html_attribs($attribs);
        if (isset($array['link_opts'])) {
          $link_opts .= ' ' . $array['link_opts'];
        }

        if (empty($array['url']) || $array['url'] === '#') {
          $array['url'] = 'javascript:void(0)';
        }
        echo('<a href="'.$array['url'].'" '.$link_opts.'>');

        if (isset($array['icon'])) {
          echo('<i class="'.$array['icon'].'"></i>&nbsp;');
          $array['text'] = '<span>'.$array['text'].'</span>'; // Added span for allow hide by class 'icon'
        }

        if (isset($array['image'])) {
          if (isset($array['image_2x']) && $ua_info['screen_ratio'] > 1) {
            // Add hidpi image set
            $srcset = ' srcset="' . $array['image_2x'] . ' 2x"';
          } else {
            $srcset = '';
          }
          echo('<img src="' . $array['image'] . '"' . $srcset . ' alt="" /> ');
        }

        echo($array['text'].'</a>');
        echo('</li>');
      } else {
        echo('  <li class="dropdown '.$array['class'].'">');

        $attribs = [
          'class' => 'dropdown-toggle',
          'data-hover' => "dropdown",
          /*'data-toggle' => "dropdown", */ // -- Disables clicking the navbar entry. May need enabling only in touch mode?
        ];
        if (get_var_true($_SESSION['touch'])) {
          // Enable dropdown click on mobile & tablets
          $attribs['data-toggle'] = "dropdown";
        }
        if (isset($array['tooltip'])) {
          $array['alt'] = $array['tooltip'];
        }
        if (isset($array['alt'])) {
          $attribs['data-rel'] = 'tooltip';
          $attribs['data-tooltip'] = $array['alt'];
        }
        if (isset($array['id'])) {
          $attribs['id'] = $array['id'];
        }
        $link_opts = generate_html_attribs($attribs);
        if (isset($array['link_opts'])) {
          $link_opts .= ' ' . $array['link_opts'];
        }

        if (empty($array['url']) || $array['url'] === '#') {
          $array['url'] = 'javascript:void(0)';
        }
        echo('    <a href="'.$array['url'].'" '.$link_opts.'>');
        if (isset($array['icon'])) {
          echo('<i class="'.$array['icon'].'"></i> ');
        }
        echo($array['text'].'
            <strong class="caret"></strong>
          </a>
        <ul class="dropdown-menu">');

        foreach ($array['suboptions'] as $suboption => $subentry) {

          if (safe_count($subentry['entries'])) {
            navbar_submenu($subentry, $level+1);
          } else {
            navbar_entry($subentry, $level+2);
          }

        }
        echo('    </ul>
      </li>');
      }
    }
    echo('</ul>');
  }

  ?>
        </div>
      </div>
    </div>
  </div>

 <?php

}


// DOCME needs phpdoc block
function navbar_location_menu($array) {
  if ($count = safe_count($array['entries'])) {
    ksort($array['entries']);
  }

  echo('<ul role="menu" class="dropdown-menu">');

  if ($count > 5) {
    foreach ($array['entries'] as $entry => $entry_data)
    {
      $image = get_icon('location');
      if ($entry_data['level'] === "location_country")
      {
        $entry = country_from_code($entry);
        $image = get_icon_country($entry);
      }
      elseif ($entry_data['level'] === "location")
      {
        $name = ($entry === '' ? OBS_VAR_UNSET : escape_html($entry));
        echo('            <li>' . generate_menu_link(generate_location_url($entry), $image . '&nbsp;' . $name, $entry_data['count']) . '</li>');
        continue;
      }

      if ($entry_data['level'] === "location_country")
      {
        $url = $entry;
        // Attach country code to sublevel
        $entry_data['country'] = $entry;
      } else {
        $url = $entry;
        // Attach country code to sublevel
        $entry_data['country'] = $array['country'];
      }
      if ($url === '') { $url = array(''); }
      $link_array = array('page' => 'devices',
                          $entry_data['level'] => var_encode($url));
      if (isset($array['country'])) { $link_array['location_country'] = var_encode($array['country']); }

      echo('<li class="dropdown-submenu">' . generate_menu_link(generate_url($link_array), $image . '&nbsp;' . $entry, $entry_data['count']));

      navbar_location_menu($entry_data);
      echo('</li>');
    }
  } else {
    $new_entry_array = array();

    foreach ($array['entries'] as $new_entry => $new_entry_data)
    {
      $image = get_icon('location');
      if ($new_entry_data['level'] === "location_country")
      {
        $new_entry = country_from_code($new_entry);
        $image = get_icon_country($new_entry);
      }
      elseif ($new_entry_data['level'] === "location")
      {
        $name = ($new_entry === '' ? OBS_VAR_UNSET : escape_html($new_entry));
        echo('            <li>' . generate_menu_link(generate_location_url($new_entry), $image . '&nbsp;' . $name, $new_entry_data['count']) . '</li>');
        continue;
      }

      echo('<li class="nav-header">'.$image.$new_entry.'</li>');
      foreach ($new_entry_data['entries'] as $sub_entry => $sub_entry_data)
      {
        if (is_array($sub_entry_data['entries']))
        {
          $link_array = array('page' => 'devices',
                              $sub_entry_data['level'] => var_encode($sub_entry));
          if (isset($array['country'])) { $link_array['location_country'] = var_encode($array['country']); }

          echo('<li class="dropdown-submenu">' . generate_menu_link(generate_url($link_array), $image.'&nbsp;' . $sub_entry, $sub_entry_data['count']));
          navbar_location_menu($sub_entry_data);
        } else {
          $name = ($sub_entry === '' ? OBS_VAR_UNSET : escape_html($sub_entry));
          echo('            <li>' . generate_menu_link(generate_location_url($sub_entry), $image.'&nbsp;' . $name, $sub_entry_data['count']) . '</li>');
        }
      }
    }
  }
  echo('</ul>');
}

// DOCME needs phpdoc block
function navbar_submenu($entry, $level = 1)
{

  if(isset($entry['text'])) { $entry['title'] = $entry['text']; }

  // autoscroll set by navbar-narrow + dropdown-menu, but override max-height
  echo(str_pad('',($level-1)*2) . '                <li class="dropdown-submenu">' . generate_menu_link($entry['url'], '<i class="' . $entry['icon'] . '"></i>&nbsp;' . $entry['title'], $entry['count'], 'label', NULL, $entry['alert_count']) . PHP_EOL);
  echo(str_pad('',($level-1)*2) . '                  <ul role="menu" class="dropdown-menu" style="max-height: 85vh;">' . PHP_EOL);

  foreach ($entry['entries'] as $subentry) {
    if (safe_count($subentry['entries'])) {
      navbar_submenu($subentry, $level+1);
    } else {
      navbar_entry($subentry, $level+2);
    }
  }

  echo(str_pad('',($level-1)*2) . '                  </ul>' . PHP_EOL);
  echo(str_pad('',($level-1)*2) . '                <li>' . PHP_EOL);
}

// DOCME needs phpdoc block
// FIXME. Move to print navbar
function navbar_entry($entry, $level = 1) {
  global $cache;

  if ($entry['divider']) {
    echo(str_pad('',($level-1)*2) . '                <li class="divider"></li>' . PHP_EOL);
  } elseif (isset($entry['userlevel']) && isset($_SESSION['userlevel']) &&
            $_SESSION['userlevel'] < $entry['userlevel']) {
     // skip not permitted menu items
     return;
  } elseif (OBSERVIUM_EDITION === 'community' && isset($entry['community']) && !$entry['community']) {
     // Skip not exist features on community
     return;
  } elseif ($entry['locations']) {// Workaround until the menu builder returns an array instead of echo()
    echo(str_pad('',($level-1)*2) . '                <li class="dropdown-submenu">' . PHP_EOL);
    echo(str_pad('',($level-1)*2) . '                  ' . generate_menu_link(generate_url(array('page'=>'locations')), '<i class="'.$GLOBALS['config']['icon']['location'].'"></i> Locations') . PHP_EOL);
    navbar_location_menu($cache['locations']);
    echo(str_pad('',($level-1)*2) . '                </li>' . PHP_EOL);
  } else {
    //$entry_text = '<i class="menu-icon ' . $entry['icon'] . '"></i> ';
    $entry_text = '';

    if (isset($entry['image'])) {
      // Detect allowed screen ratio for current browser, cached!
      $ua_info = detect_browser();
      if (isset($entry['image_2x']) && $ua_info['screen_ratio'] > 1) {
        // Add hidpi image set
        $srcset = ' srcset="' . $entry['image_2x'] . ' 2x"';
      } else {
        $srcset = '';
      }
      $entry_text .= '<img src="' . $entry['image'] . '"' . $srcset . ' alt="" /> ';
    }

    if (isset($entry['title'])) {
      $entry_text .= $entry['title'];
    } elseif (isset($entry['text'])) {
      $entry_text .= $entry['text'];
    }

    $entry['text'] = $entry_text;

    echo(str_pad('',($level-1)*2) . '                <li>' . generate_menu_link_new($entry) . '</li>' . PHP_EOL);
  }
}


// EOF
