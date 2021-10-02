<?php // vim:fenc=utf-8:filetype=php:ts=4
/*
 * Copyright (C) 2009  Bruno Prémont <bonbons AT linux-vserver.org>
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; only version 2 of the License is applicable.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

define('REGEXP_HOST', '/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/');
define('REGEXP_PLUGIN', '/^[a-zA-Z0-9_.-]+$/');

function makeTextBlock($text, $fontfile, $fontsize, $width) {
  // TODO: handle explicit line-break!
  $words = explode(' ', $text);
  $lines = array($words[0]);
  $currentLine = 0;
  foreach ($words as $word) {
    $lineSize = imagettfbbox($fontsize, 0, $fontfile, $lines[$currentLine] . ' ' . $word);
    if ($lineSize[2] - $lineSize[0] < $width) {
      $lines[$currentLine] .= ' ' . $word;
    } else {
      $currentLine++;
      $lines[$currentLine] = $word;
    }
  }
  error_log(sprintf('Handles message "%s", %d words => %d/%d lines', $text, count($words), $currentLine, count($lines)));
  return implode("\n", $lines);
}

/**
 * No RRD files found that could match request
 * @code HTTP error code
 * @code_msg Short text description of HTTP error code
 * @title Title for fake RRD graph
 * @msg Complete error message to display in place of graph content
 */
function error_collectd($code, $code_msg, $title, $msg) {
  global $config;

  header(sprintf("HTTP/1.0 %d %s", $code, $code_msg));
  header("Pragma: no-cache");
  header("Expires: Mon, 01 Jan 2008 00:00:00 CET");
  header("Content-Type: image/png");
  $w = $config['rrd_width']+81;
  $h = $config['rrd_height']+79;

  $png   = imagecreate($w, $h);
  $c_bkgnd = imagecolorallocate($png, 240, 240, 240);
  $c_fgnd  = imagecolorallocate($png, 255, 255, 255);
  $c_blt   = imagecolorallocate($png, 208, 208, 208);
  $c_brb   = imagecolorallocate($png, 160, 160, 160);
  $c_grln  = imagecolorallocate($png, 114, 114, 114);
  $c_grarr = imagecolorallocate($png, 128,  32,  32);
  $c_txt   = imagecolorallocate($png, 0, 0, 0);
  $c_etxt  = imagecolorallocate($png, 64, 0, 0);

  if (function_exists('imageantialias'))
    imageantialias($png, true);
  imagefilledrectangle($png, 0,   0, $w, $h, $c_bkgnd);
  imagefilledrectangle($png, 51, 33, $w-31, $h-47, $c_fgnd);
  imageline($png,  51,  30,  51, $h-43, $c_grln);
  imageline($png,  48, $h-46, $w-28, $h-46, $c_grln);
  imagefilledpolygon($png, array(49, 30,  51, 26,  53, 30), 3, $c_grarr);
  imagefilledpolygon($png, array($w-28, $h-48,  $w-24, $h-46,  $w-28, $h-44), 3, $c_grarr);
  imageline($png,  0,  0,   $w,  0, $c_blt);
  imageline($png,  0,  1,   $w,  1, $c_blt);
  imageline($png,  0,  0,  0,   $h, $c_blt);
  imageline($png,  1,  0,  1,   $h, $c_blt);
  imageline($png, $w-1,  0, $w-1,   $h, $c_brb);
  imageline($png, $w-2,  1, $w-2,   $h, $c_brb);
  imageline($png,  1, $h-2,   $w, $h-2, $c_brb);
  imageline($png,  0, $h-1,   $w, $h-1, $c_brb);

  imagestring($png, 4, ceil(($w-strlen($title)*imagefontwidth(4)) / 2), 10, $title, $c_txt);
  imagestring($png, 5, 60, 35, sprintf('%s [%d]', $code_msg, $code), $c_etxt);
  if (function_exists('imagettfbbox') && is_file($config['error_font'])) {
    // Detailled error message
    $fmt_msg = makeTextBlock($msg, $errorfont, 10, $w-86);
    $fmtbox  = imagettfbbox(12, 0, $errorfont, $fmt_msg);
    imagettftext($png, 10, 0, 55, 35+3+imagefontwidth(5)-$fmtbox[7]+$fmtbox[1], $c_txt, $errorfont, $fmt_msg);
  } else {
    imagestring($png, 4, 53, 35+6+imagefontwidth(5), $msg, $c_txt);
  }

  imagepng($png);
  imagedestroy($png);
}

/**
 * No RRD files found that could match request
 */
function error404($title, $msg) {
  error_collectd(404, "Not found", $title, $msg);
}

function error500($title, $msg) {
  error_collectd(500, "Not found", $title, $msg);
}

/**
 * Incomplete / invalid request
 */
function error400($title, $msg) {
  error_collectd(400, "Bad request", $title, $msg);
}


/**
 * Read input variable from GET, POST or COOKIE taking
 * care of magic quotes
 * @name Name of value to return
 * @array User-input array ($_GET, $_POST or $_COOKIE)
 * @default Default value
 * @return $default if name in unknown in $array, otherwise
 *         input value with magic quotes stripped off
 */
function read_var($name, &$array, $default = null) {
	if (isset($array[$name])) {
		if (is_array($array[$name])) {
			if (get_magic_quotes_gpc()) {
				$ret = array();
				foreach ($array[$name] as $k => $v)
				{
					$ret[stripslashes($k)] = stripslashes($v);
				}
				return $ret;
			} else
				return $array[$name];
		} else if (is_string($array[$name]) && get_magic_quotes_gpc()) {
			return stripslashes($array[$name]);
		} else
			return $array[$name];
	} else
		return $default;
}

/**
 * Alphabetically compare host names, comparing label
 * from tld to node name
 */
function collectd_compare_host($a, $b) {
	$ea = explode('.', $a);
	$eb = explode('.', $b);
	$i = count($ea) - 1;
	$j = count($eb) - 1;
	while ($i >= 0 && $j >= 0)
		if (($r = strcmp($ea[$i--], $eb[$j--])) != 0)
			return $r;
	return 0;
}

/**
 * Fetch list of hosts found in collectd's datadirs.
 * @return array Sorted list of hosts (sorted by label from right to left)
 */
function collectd_list_hosts() {
	global $config;

	$hosts = array();
	foreach($config['datadirs'] as $datadir)
		if ($d = @opendir($datadir)) {
			while (($dent = readdir($d)) !== false)
				if ($dent != '.' && $dent != '..' && is_dir($datadir.'/'.$dent) && preg_match(REGEXP_HOST, $dent))
					$hosts[] = $dent;
			closedir($d);
		} else
			error_log('Failed to open datadir: '.$datadir);
	$hosts = array_unique($hosts);
	usort($hosts, 'collectd_compare_host');
	return $hosts;
}

/**
 * Fetch list of plugins found in collectd's datadirs for given host.
 * @arg_host Name of host for which to return plugins
 * @return array Sorted list of plugins (sorted alphabetically)
 */
function collectd_list_plugins($arg_host) {
	global $config;

	$plugins = array();
	foreach ($config['datadirs'] as $datadir)
		if (preg_match(REGEXP_HOST, $arg_host) && ($d = @opendir($datadir.'/'.$arg_host))) {
			while (($dent = readdir($d)) !== false)
				if ($dent != '.' && $dent != '..' && is_dir($datadir.'/'.$arg_host.'/'.$dent)) {
					if ($i = strpos($dent, '-'))
						$plugins[] = substr($dent, 0, $i);
					else
						$plugins[] = $dent;
				}
			closedir($d);
		}
	$plugins = array_unique($plugins);
	sort($plugins);
	return $plugins;
}

/**
 * Fetch list of plugin instances found in collectd's datadirs for given host+plugin
 * @arg_host Name of host
 * @arg_plugin Name of plugin
 * @return array Sorted list of plugin instances (sorted alphabetically)
 */
function collectd_list_pinsts($arg_host, $arg_plugin) {
	global $config;

	$pinsts = array();
	foreach ($config['datadirs'] as $datadir)
		if (preg_match(REGEXP_HOST, $arg_host) && ($d = opendir($datadir.'/'.$arg_host))) {
			while (($dent = readdir($d)) !== false)
				if ($dent != '.' && $dent != '..' && is_dir($datadir.'/'.$arg_host.'/'.$dent)) {
					if ($i = strpos($dent, '-')) {
						$plugin = substr($dent, 0, $i);
						$pinst  = substr($dent, $i+1);
					} else {
						$plugin = $dent;
						$pinst  = '';
					}
					if ($plugin == $arg_plugin)
						$pinsts[] = $pinst;
				}
			closedir($d);
		}
	$pinsts = array_unique($pinsts);
	sort($pinsts);
	return $pinsts;
}

/**
 * Fetch list of types found in collectd's datadirs for given host+plugin+instance
 * @arg_host Name of host
 * @arg_plugin Name of plugin
 * @arg_pinst Plugin instance
 * @return Sorted list of types (sorted alphabetically)
 */
function collectd_list_types($arg_host, $arg_plugin, $arg_pinst) {
	global $config;

	$types = array();
	$my_plugin = $arg_plugin . (strlen($arg_pinst) ? '-'.$arg_pinst : '');
	if (!preg_match(REGEXP_PLUGIN, $my_plugin))
		return $types;
	foreach ($config['datadirs'] as $datadir)
		if (preg_match(REGEXP_HOST, $arg_host) && ($d = @opendir($datadir.'/'.$arg_host.'/'.$my_plugin))) {
			while (($dent = readdir($d)) !== false)
				if ($dent != '.' && $dent != '..' && is_file($datadir.'/'.$arg_host.'/'.$my_plugin.'/'.$dent) && substr($dent, strlen($dent)-4) == '.rrd') {
					$dent = substr($dent, 0, strlen($dent)-4);
					if ($i = strpos($dent, '-'))
						$types[] = substr($dent, 0, $i);
					else
						$types[] = $dent;
				}
			closedir($d);
		}
	$types = array_unique($types);
	sort($types);
	return $types;
}

/**
 * Fetch list of type instances found in collectd's datadirs for given host+plugin+instance+type
 * @arg_host Name of host
 * @arg_plugin Name of plugin
 * @arg_pinst Plugin instance
 * @arg_type Type
 * @return Sorted list of type instances (sorted alphabetically)
 */
function collectd_list_tinsts($arg_host, $arg_plugin, $arg_pinst, $arg_type) {
	global $config;

	$tinsts = array();
	$my_plugin = $arg_plugin . (strlen($arg_pinst) ? '-'.$arg_pinst : '');
	if (!preg_match(REGEXP_PLUGIN, $my_plugin))
		return $types;
	foreach ($config['datadirs'] as $datadir)
		if (preg_match(REGEXP_HOST, $arg_host) && ($d = @opendir($datadir.'/'.$arg_host.'/'.$my_plugin))) {
			while (($dent = readdir($d)) !== false)
				if ($dent != '.' && $dent != '..' && is_file($datadir.'/'.$arg_host.'/'.$my_plugin.'/'.$dent) && substr($dent, strlen($dent)-4) == '.rrd') {
					$dent = substr($dent, 0, strlen($dent)-4);
					if ($i = strpos($dent, '-')) {
						$type  = substr($dent, 0, $i);
						$tinst = substr($dent, $i+1);
					} else {
						$type  = $dent;
						$tinst = '';
					}
					if ($type == $arg_type)
						$tinsts[] = $tinst;
				}
			closedir($d);
		}
	$tinsts = array_unique($tinsts);
	sort($tinsts);
	return $tinsts;
}

/**
 * Parse symlinks in order to get an identifier that collectd understands
 * (e.g. virtualisation is collected on host for individual VMs and can be
 *  symlinked to the VM's hostname, support FLUSH for these by flushing
 *  on the host-identifier instead of VM-identifier)
 * @host Host name
 * @plugin Plugin name
 * @pinst Plugin instance
 * @type Type name
 * @tinst Type instance
 * @return Identifier that collectd's FLUSH command understands
 */
function collectd_identifier($host, $plugin, $pinst, $type, $tinst) {
	global $config;
	$rrd_realpath    = null;
	$orig_identifier = sprintf('%s/%s%s%s/%s%s%s', $host, $plugin, strlen($pinst) ? '-' : '', $pinst, $type, strlen($tinst) ? '-' : '', $tinst);
	$identifier      = null;
	foreach ($config['datadirs'] as $datadir)
	{
		if (is_file($datadir . '/' . $orig_identifier . '.rrd'))
		{
			$rrd_realpath = realpath($datadir . '/' . $orig_identifier . '.rrd');
			break;
		}
	}
	if ($rrd_realpath) {
		$identifier   = basename($rrd_realpath);
		$identifier   = substr($identifier, 0, strlen($identifier)-4);
		$rrd_realpath = dirname($rrd_realpath);
		$identifier   = basename($rrd_realpath).'/'.$identifier;
		$rrd_realpath = dirname($rrd_realpath);
		$identifier   = basename($rrd_realpath).'/'.$identifier;
	}

	if (is_null($identifier))
		return $orig_identifier;
	else
		return $identifier;
}

/**
 * Tell collectd that it should FLUSH all data it has regarding the
 * graph we are about to generate.
 * @host Host name
 * @plugin Plugin name
 * @pinst Plugin instance
 * @type Type name
 * @tinst Type instance
 */
function collectd_flush($identifier) {
	global $config;

	if (!$config['collectd_sock'])
		return false;
	if (is_null($identifier) || (is_array($identifier) && count($identifier) == 0) || !(is_string($identifier) || is_array($identifier)))
		return false;

	if (is_null($host) || !is_string($host) || strlen($host) == 0)
		return false;
	if (is_null($plugin) || !is_string($plugin) || strlen($plugin) == 0)
		return false;
	if (is_null($pinst) || !is_string($pinst))
		return false;
	if (is_null($type) || !is_string($type) || strlen($type) == 0)
		return false;
	if (is_null($tinst) || (is_array($tinst) && count($tinst) == 0) || !(is_string($tinst) || is_array($tinst)))
		return false;

	$u_errno  = 0;
	$u_errmsg = '';
	if ($socket = @fsockopen($config['collectd_sock'], 0, $u_errno, $u_errmsg)) {
		$cmd = 'FLUSH plugin=rrdtool';
		if (is_array($identifier)) {
			foreach ($identifier as $val)
				$cmd .= sprintf(' identifier="%s"', $val);
		} else
			$cmd .= sprintf(' identifier="%s"', $identifier);
		$cmd .= "\n";

		$r = fwrite($socket, $cmd, strlen($cmd));
		if ($r === false || $r != strlen($cmd))
			error_log(sprintf("graph.php: Failed to write whole command to unix-socket: %d out of %d written", $r === false ? -1 : $r, strlen($cmd)));

		$resp = fgets($socket);
		if ($resp === false)
			error_log(sprintf("graph.php: Failed to read response from collectd for command: %s", trim($cmd)));

		$n = (int)$resp;
		while ($n-- > 0)
			fgets($socket);

		fclose($socket);
	} else
		error_log(sprintf("graph.php: Failed to open unix-socket to collectd: %d: %s", $u_errno, $u_errmsg));
}

class CollectdColor {
	private $r = 0;
	private $g = 0;
	private $b = 0;

	function __construct($value = null) {
		if (is_null($value)) {
		} else if (is_array($value)) {
			if (isset($value['r']))
				$this->r = $value['r'] > 0 ? ($value['r'] > 1 ? 1 : $value['r']) : 0;
			if (isset($value['g']))
				$this->g = $value['g'] > 0 ? ($value['g'] > 1 ? 1 : $value['g']) : 0;
			if (isset($value['b']))
				$this->b = $value['b'] > 0 ? ($value['b'] > 1 ? 1 : $value['b']) : 0;
		} else if (is_string($value)) {
			$matches = array();
			if ($value == 'random') {
				$this->randomize();
			} else if (preg_match('/([0-9A-Fa-f][0-9A-Fa-f])([0-9A-Fa-f][0-9A-Fa-f])([0-9A-Fa-f][0-9A-Fa-f])/', $value, $matches)) {
				$this->r = hexdec($matches[1]) / 255.0;
				$this->g = hexdec($matches[2]) / 255.0;
				$this->b = hexdec($matches[3]) / 255.0;
			}
		} else if (is_a($value, 'CollectdColor')) {
			$this->r = $value->r;
			$this->g = $value->g;
			$this->b = $value->b;
		}
	}

	function randomize() {
		$this->r = mt_rand(0, 255) / 255.0;
		$this->g = mt_rand(0, 255) / 255.0;
		$this->b = 0.0;
		$min = 0.0;
		$max = 1.0;

		if (($this->r + $this->g) < 1.0) {
			$min = 1.0 - ($this->r + $this->g);
		} else {
			$max = 2.0 - ($this->r + $this->g);
		}
		$this->b = $min + ((mt_rand(0, 255) / 255.0) * ($max - $min));
	}

	function fade($bkgnd = null, $alpha = 0.25) {
		if (is_null($bkgnd) || !is_a($bkgnd, 'CollectdColor')) {
			$bg_r = 1.0;
			$bg_g = 1.0;
			$bg_b = 1.0;
		} else {
			$bg_r = $bkgnd->r;
			$bg_g = $bkgnd->g;
			$bg_b = $bkgnd->b;
		}

		$this->r = $alpha * $this->r + ((1.0 - $alpha) * $bg_r);
		$this->g = $alpha * $this->g + ((1.0 - $alpha) * $bg_g);
		$this->b = $alpha * $this->b + ((1.0 - $alpha) * $bg_b);
	}

	function as_array() {
		return array('r'=>$this->r, 'g'=>$this->g, 'b'=>$this->b);
	}

	function as_string() {
		$r = (int)($this->r*255);
		$g = (int)($this->g*255);
		$b = (int)($this->b*255);
		return sprintf('%02x%02x%02x', $r > 255 ? 255 : $r, $g > 255 ? 255 : $g, $b > 255 ? 255 : $b);
	}
}

function rrd_get_color($code, $line = true) {
	global $config;
	$name = ($line ? 'f_' : 'h_').$code;
	if (!isset($config['rrd_colors'][$name])) {
		$c_f = new CollectdColor('random');
		$c_h = new CollectdColor($c_f);
		$c_h->fade();
		$config['rrd_colors']['f_'.$code] = $c_f->as_string();
		$config['rrd_colors']['h_'.$code] = $c_h->as_string();
	}
	return $config['rrd_colors'][$name];
}

/**
 * Draw RRD file based on it's structure
 * @host
 * @plugin
 * @pinst
 * @type
 * @tinst
 * @opts
 * @return Commandline to call RRDGraph in order to generate the final graph
 */
function collectd_draw_rrd($host, $plugin, $pinst = null, $type, $tinst = null, $opts = array()) {
	global $config;
	$timespan_def = null;
	if (!isset($opts['timespan']))
		$timespan_def = reset($config['timespan']);
	else foreach ($config['timespan'] as &$ts)
		if ($ts['name'] == $opts['timespan'])
			$timespan_def = $ts;
	if (!isset($opts['rrd_opts']))
		$opts['rrd_opts'] = array();
	if (isset($opts['logarithmic']) && $opts['logarithmic'])
		array_unshift($opts['rrd_opts'], '-o');

	$rrdinfo = null;
	$rrdfile = sprintf('%s/%s%s%s/%s%s%s', $host, $plugin, is_null($pinst) ? '' : '-', $pinst, $type, is_null($tinst) ? '' : '-', $tinst);
	foreach ($config['datadirs'] as $datadir) {
		if (is_file($datadir.'/'.$rrdfile.'.rrd')) {
			$rrdinfo = rrdtool_file_info($datadir.'/'.$rrdfile.'.rrd');
			if (isset($rrdinfo['RRA']) && is_array($rrdinfo['RRA']))
				break;
			else
				$rrdinfo = null;
		}
	}

	if (is_null($rrdinfo))
	{
		return FALSE;
	}

	$graph = array();
	$has_avg = false;
	$has_max = false;
	$has_min = false;
	reset($rrdinfo['RRA']);
	$l_max = 0;
	foreach ($rrdinfo['RRA'] as $k => $v) {
		if ($v['cf'] == 'MAX')
			$has_max = true;
		else if ($v['cf'] == 'AVERAGE')
			$has_avg = true;
		else if ($v['cf'] == 'MIN')
			$has_min = true;
	}

        // Build legend. This may not work for all RRDs, i don't know :)
        if ($has_avg)
                $graph[] = "COMMENT:           Last";
        if ($has_min)
                $graph[] = "COMMENT:   Min";
        if ($has_max)
                $graph[] = "COMMENT:   Max";
        if ($has_avg)
                $graph[] = "COMMENT:   Avg\\n";


	reset($rrdinfo['DS']);
	foreach ($rrdinfo['DS'] as $k => $v) {
		if (strlen($k) > $l_max)
			$l_max = strlen($k);
		if ($has_min)
			$graph[] = sprintf('DEF:%s_min=%s:%s:MIN', $k, $rrdinfo['filename'], $k);
		if ($has_avg)
			$graph[] = sprintf('DEF:%s_avg=%s:%s:AVERAGE', $k, $rrdinfo['filename'], $k);
		if ($has_max)
			$graph[] = sprintf('DEF:%s_max=%s:%s:MAX', $k, $rrdinfo['filename'], $k);
	}
	if ($has_min && $has_max || $has_min && $has_avg || $has_avg && $has_max) {
		$n = 1;
		reset($rrdinfo['DS']);
		foreach ($rrdinfo['DS'] as $k => $v) {
			$graph[] = sprintf('LINE:%s_%s', $k, $has_min ? 'min' : 'avg');
			$graph[] = sprintf('CDEF:%s_var=%s_%s,%s_%s,-', $k, $k, $has_max ? 'max' : 'avg', $k, $has_min ? 'min' : 'avg');
			$graph[] = sprintf('AREA:%s_var#%s::STACK', $k, rrd_get_color($n++, false));
		}
	}

	reset($rrdinfo['DS']);
	$n = 1;
	foreach ($rrdinfo['DS'] as $k => $v) {
		$graph[] = sprintf('LINE1:%s_avg#%s:%s ', $k, rrd_get_color($n++, true), $k.substr('                  ', 0, $l_max-strlen($k)));
		if (isset($opts['tinylegend']) && $opts['tinylegend'])
			continue;
		if ($has_avg)
			$graph[] = sprintf('GPRINT:%s_avg:AVERAGE:%%5.1lf%%s', $k, $has_max || $has_min || $has_avg ? ',' : "\\l");
		if ($has_min)
			$graph[] = sprintf('GPRINT:%s_min:MIN:%%5.1lf%%s', $k, $has_max || $has_avg ? ',' : "\\l");
		if ($has_max)
			$graph[] = sprintf('GPRINT:%s_max:MAX:%%5.1lf%%s', $k, $has_avg ? ',' : "\\l");
		if ($has_avg)
			$graph[] = sprintf('GPRINT:%s_avg:LAST:%%5.1lf%%s\\l', $k);
	}

	#$rrd_cmd = array(RRDTOOL, 'graph', '-', '-E', '-a', 'PNG', '-w', $config['rrd_width'], '-h', $config['rrd_height'], '-t', $rrdfile);
        #$rrd_cmd = array(RRDTOOL, 'graph', '-', '-E', '-a', 'PNG', '-w', $config['rrd_width'], '-h', $config['rrd_height']);
        $rrd_cmd = array('-', '-E', '-a', 'PNG', '-w', $config['rrd_width'], '-h', $config['rrd_height']);

        if($config['rrd_width'] <= "300") {
          $small_opts = array ('--font', "LEGEND:7:mono", '--font', "AXIS:6:mono", "--font-render-mode", "normal");
          $rrd_cmd = array_merge($rrd_cmd, $small_opts);
        }

	//$rrd_cmd = array_merge($rrd_cmd, $config['rrd_opts_array'], $opts['rrd_opts'], $graph);
	$rrd_cmd = collectd_rrdcmd($rrd_cmd, $opts['rrd_opts'], $graph);

	$cmd = RRDTOOL; $cmd = '';
	for ($i = 1; $i < count($rrd_cmd); $i++)
		$cmd .= ' '.escapeshellarg($rrd_cmd[$i]);

	return $cmd;
}

/**
 * Draw RRD file based on it's structure
 * @timespan
 * @host
 * @plugin
 * @pinst
 * @type
 * @tinst
 * @opts
 * @return Commandline to call RRDGraph in order to generate the final graph
 */
function collectd_draw_generic($timespan, $host, $plugin, $pinst = null, $type, $tinst = null) {
	global $config, $GraphDefs;
	$timespan_def = NULL;
	foreach ($config['timespan'] as &$ts)
		if ($ts['name'] == $timespan)
			$timespan_def = $ts;
	if (is_null($timespan_def))
		$timespan_def = reset($config['timespan']);

	if (!isset($GraphDefs[$type]))
		return false;

	$rrd_file = sprintf('%s/%s%s%s/%s%s%s', $host, $plugin, is_null($pinst) ? '' : '-', $pinst, $type, is_null($tinst) ? '' : '-', $tinst);
	#$rrd_cmd  = array(RRDTOOL, 'graph', '-', '-E', '-a', 'PNG', '-w', $config['rrd_width'], '-h', $config['rrd_height'], '-t', $rrd_file);
        #$rrd_cmd = array(RRDTOOL, 'graph', '-', '-E', '-a', 'PNG', '-w', $config['rrd_width'], '-h', $config['rrd_height']);
        $rrd_cmd = array('-', '-E', '-a', 'PNG', '-w', $config['rrd_width'], '-h', $config['rrd_height']);

        if($config['rrd_width'] <= "300") {
          $small_opts = array ('--font', 'LEGEND:7:mono', '--font', 'AXIS:6:mono', '--font-render-mode', 'normal');
          $rrd_cmd = array_merge($rrd_cmd, $small_opts);
        }

	//$rrd_cmd  = array_merge($rrd_cmd, $config['rrd_opts_array']);
  $rrd_cmd = collectd_rrdcmd($rrd_cmd);

	$rrd_args = $GraphDefs[$type];

	foreach ($config['datadirs'] as $datadir) {
		$file = $datadir.'/'.$rrd_file.'.rrd';
		if (!is_file($file))
			continue;

		$file = str_replace(":", "\\:", $file);
		$rrd_args = str_replace('{file}', $file, $rrd_args);

		$rrdgraph = array_merge($rrd_cmd, $rrd_args);
		#$cmd = RRDTOOL;
                $cmd = '';
		for ($i = 1; $i < count($rrdgraph); $i++)
			$cmd .= ' '.escapeshellarg($rrdgraph[$i]);

		return $cmd;
	}
	return false;
}

/**
 * Draw stack-graph for set of RRD files
 * @opts Graph options like colors
 * @sources List of array(name, file, ds)
 * @return Commandline to call RRDGraph in order to generate the final graph
 */
function collectd_draw_meta_stack(&$opts, &$sources) {
	global $config;
	$timespan_def = null;
	if (!isset($opts['timespan']))
		$timespan_def = reset($config['timespan']);
	else foreach ($config['timespan'] as &$ts)
		if ($ts['name'] == $opts['timespan'])
			$timespan_def = $ts;

	if (!isset($opts['title']))
		$opts['title'] = 'Unknown title';
	if (!isset($opts['rrd_opts']))
		$opts['rrd_opts'] = array();
	if (!isset($opts['colors']))
		$opts['colors'] = array();
	if (isset($opts['logarithmic']) && $opts['logarithmic'])
		array_unshift($opts['rrd_opts'], '-o');

#	$cmd = array(RRDTOOL, 'graph', '-', '-E', '-a', 'PNG', '-w', $config['rrd_width'], '-h', $config['rrd_height'],
#                    '-t', $opts['title']);

        #$cmd = array(RRDTOOL, 'graph', '-', '-E', '-a', 'PNG', '-w', $config['rrd_width'], '-h', $config['rrd_height']);

        $cmd = array('-', '-E', '-a', 'PNG', '-w', $config['rrd_width'], '-h', $config['rrd_height']);


        if($config['rrd_width'] <= "300") {
          $small_opts = array ('--font', 'LEGEND:7:mono', '--font', 'AXIS:6:mono', '--font-render-mode', 'normal');
          $cmd = array_merge($cmd, $small_opts);
        }

	//$cmd = array_merge($cmd, $config['rrd_opts_array'], $opts['rrd_opts']);
  $cmd = collectd_rrdcmd($cmd, $opts['rrd_opts']);

  $max_inst_name = 0;

	foreach($sources as &$inst_data) {
		$inst_name = $inst_data['name'];
		$file      = $inst_data['file'];
		$ds        = isset($inst_data['ds']) ? $inst_data['ds'] : 'value';

		if (strlen($inst_name) > $max_inst_name)
			$max_inst_name = strlen($inst_name);

		if (!is_file($file))
			continue;

		$cmd[] = 'DEF:'.$inst_name.'_min='.$file.':'.$ds.':MIN';
		$cmd[] = 'DEF:'.$inst_name.'_avg='.$file.':'.$ds.':AVERAGE';
		$cmd[] = 'DEF:'.$inst_name.'_max='.$file.':'.$ds.':MAX';
		$cmd[] = 'CDEF:'.$inst_name.'_nnl='.$inst_name.'_avg,UN,0,'.$inst_name.'_avg,IF';
	}
	$inst_data = end($sources);
	$inst_name = $inst_data['name'];
	$cmd[] = 'CDEF:'.$inst_name.'_stk='.$inst_name.'_nnl';

	$inst_data1 = end($sources);
	while (($inst_data0 = prev($sources)) !== false) {
		$inst_name0 = $inst_data0['name'];
		$inst_name1 = $inst_data1['name'];

		$cmd[] = 'CDEF:'.$inst_name0.'_stk='.$inst_name0.'_nnl,'.$inst_name1.'_stk,+';
		$inst_data1 = $inst_data0;
	}

	foreach($sources as &$inst_data) {
		$inst_name = $inst_data['name'];
#		$legend = sprintf('%s', $inst_name);
		$legend = $inst_name;
		while (strlen($legend) < $max_inst_name)
			$legend .= ' ';
		$number_format = isset($opts['number_format']) ? $opts['number_format'] : '%6.1lf';

		if (isset($opts['colors'][$inst_name]))
			$line_color = new CollectdColor($opts['colors'][$inst_name]);
		else
			$line_color = new CollectdColor('random');
		$area_color = new CollectdColor($line_color);
		$area_color->fade();

		$cmd[] = 'AREA:'.$inst_name.'_stk#'.$area_color->as_string();
		$cmd[] = 'LINE1:'.$inst_name.'_stk#'.$line_color->as_string().':'.$legend;
		if (!(isset($opts['tinylegend']) && $opts['tinylegend'])) {
			$cmd[] = 'GPRINT:'.$inst_name.'_avg:LAST:'.$number_format.'';
                        $cmd[] = 'GPRINT:'.$inst_name.'_avg:AVERAGE:'.$number_format.'';
			$cmd[] = 'GPRINT:'.$inst_name.'_min:MIN:'.$number_format.'';
			$cmd[] = 'GPRINT:'.$inst_name.'_max:MAX:'.$number_format.'\\l';
		}
	}

	$rrdargs = "";
	for ($i = 1; $i < count($cmd); $i++)
		$rrdargs .= ' '.escapeshellarg($cmd[$i]);
	return $rrdargs;
}

/**
 * Draw stack-graph for set of RRD files
 * @opts Graph options like colors
 * @sources List of array(name, file, ds)
 * @return Commandline to call RRDGraph in order to generate the final graph
 */
function collectd_draw_meta_line(&$opts, &$sources) {
	global $config;
	$timespan_def = null;
	if (!isset($opts['timespan']))
		$timespan_def = reset($config['timespan']);
	else foreach ($config['timespan'] as &$ts)
		if ($ts['name'] == $opts['timespan'])
			$timespan_def = $ts;

	if (!isset($opts['title']))
		$opts['title'] = 'Unknown title';
	if (!isset($opts['rrd_opts']))
		$opts['rrd_opts'] = array();
	if (!isset($opts['colors']))
		$opts['colors'] = array();
	if (isset($opts['logarithmic']) && $opts['logarithmic'])
		array_unshift($opts['rrd_opts'], '-o');

#	$cmd = array(RRDTOOL, 'graph', '-', '-E', '-a', 'PNG', '-w', $config['rrd_width'], '-h', $config['rrd_height'], '-t', $opts['title']);
#	$cmd = array_merge($cmd, $config['rrd_opts_array'], $opts['rrd_opts']);

        $cmd = array('-', '-E', '-a', 'PNG', '-w', $config['rrd_width'], '-h', $config['rrd_height']);


        if($config['rrd_width'] <= "300") {
          $small_opts = array ('--font', 'LEGEND:7:mono', '--font', 'AXIS:6:mono', '--font-render-mode', 'normal');
          $cmd = array_merge($cmd, $small_opts);
        }


	//$cmd = array_merge($cmd, $config['rrd_opts_array'], $opts['rrd_opts']);
  $cmd = collectd_rrdcmd($cmd, $opts['rrd_opts']);
	$max_inst_name = 0;

	foreach ($sources as &$inst_data) {
		$inst_name = $inst_data['name'];
		$file      = $inst_data['file'];
		$ds        = isset($inst_data['ds']) ? $inst_data['ds'] : 'value';

		if (strlen($inst_name) > $max_inst_name)
			$max_inst_name = strlen($inst_name);

		if (!is_file($file))
			continue;

		$cmd[] = 'DEF:'.$inst_name.'_min='.$file.':'.$ds.':MIN';
		$cmd[] = 'DEF:'.$inst_name.'_avg='.$file.':'.$ds.':AVERAGE';
		$cmd[] = 'DEF:'.$inst_name.'_max='.$file.':'.$ds.':MAX';
	}

	foreach ($sources as &$inst_data) {
		$inst_name = $inst_data['name'];
		$legend = sprintf('%s', $inst_name);
		while (strlen($legend) < $max_inst_name)
			$legend .= ' ';
		$number_format = isset($opts['number_format']) ? $opts['number_format'] : '%6.1lf';

		if (isset($opts['colors'][$inst_name]))
			$line_color = new CollectdColor($opts['colors'][$inst_name]);
		else
			$line_color = new CollectdColor('random');

		$cmd[] = 'LINE1:'.$inst_name.'_avg#'.$line_color->as_string().':'.$legend;
		if (!(isset($opts['tinylegend']) && $opts['tinylegend'])) {
			$cmd[] = 'GPRINT:'.$inst_name.'_min:MIN:'.$number_format.'';
			$cmd[] = 'GPRINT:'.$inst_name.'_avg:AVERAGE:'.$number_format.'';
			$cmd[] = 'GPRINT:'.$inst_name.'_max:MAX:'.$number_format.'';
			$cmd[] = 'GPRINT:'.$inst_name.'_avg:LAST:'.$number_format.'\\l';
		}
	}

	$rrdargs = "";
	for ($i = 1; $i < count($cmd); $i++)
		$rrdargs .= ' '.escapeshellarg($cmd[$i]);
	return $rrdargs;
}

// DERP compatibility
function collectd_rrdcmd($rrd_cmd, $rrd_opts = [], $graph = [])
{

	if ($GLOBALS['config']['themes'][$_SESSION['theme']]['type'] == 'dark')
	{
		$rrd_opts_array = str_replace("  ", " ", $GLOBALS['config']['rrdgraph']['dark']);
	} else {
		$rrd_opts_array = str_replace("  ", " ", $GLOBALS['config']['rrdgraph']['light']);
	}
	$rrd_opts_array = explode(" ", trim($rrd_opts_array));

  return array_merge($rrd_cmd, $rrd_opts_array, $rrd_opts, $graph);
}

// EOF
