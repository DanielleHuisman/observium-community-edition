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

include_once("../../includes/observium.inc.php");

include($config['html_dir'] . "/includes/authenticate.inc.php");

if (!$_SESSION['authenticated']) {
    echo('<li class="nav-header">Session expired, please log in again!</li>');
    exit;
}

include($config['html_dir'] . "/includes/cache-data.inc.php");

$query_limit = 8; // Limit per query

$vars = get_vars(['POST', 'GET']);

// Is there a POST/GET query string?
if (isset($vars['queryString'])) {
    $queryString = trim($vars['queryString']);

    // Is the string length greater than 0?
    if (strlen($queryString) > 0) {
        $query_param = "%$queryString%";

        // Start out with a clean slate
        $search_results = [];

        // Increase query_limit by one, so we can show "+" on result display if there are more than $query_limit entries
        $query_limit++;

        // Run search modules
        foreach ($config['wui']['search_modules'] as $module) {
            if (is_file($config['html_dir'] . "/includes/search/$module.inc.php")) {
                include($config['html_dir'] . "/includes/search/$module.inc.php");
            }
        }

        // Reset query_limit
        $query_limit--;

        foreach ($search_results as $results) {
            $display_count = safe_count($results['results']);

            // If there are more results than query_limit (can happen, as we ++'d above), cut array to desired size and add + to counter
            if ($display_count > $query_limit) {
                $results['results'] = array_slice($results['results'], 0, $query_limit);
                $display_count      .= '+';
            }

            echo('<li class="nav-header">' . $results['descr'] . ': ' . $display_count . '</li>' . PHP_EOL);

            foreach ($results['results'] as $result) {
                $data = [];
                foreach ($result['data'] as $str) {
                    $str    = str_replace('| |', '|', $str);
                    $data[] = rtrim($str, ' |');
                }
                echo('<li class="divider" style="margin: 0px;"></li>' . PHP_EOL);
                echo('<li style="margin: 0px;">' . PHP_EOL . '  <a href="' . $result['url'] . '">' . PHP_EOL);
                echo('    <dl style="border-left: 10px solid ' . $result['colour'] . '; " class="dl-horizontal dl-search">' . PHP_EOL);
                echo('  <dt style="width: 64px; text-align: center; line-height: 41.5px;">' . get_icon($result['icon']) . '</dt>' . PHP_EOL);
                echo('    <dd>' . PHP_EOL);
                echo('      <strong>' . html_highlight(escape_html($result['name']), $queryString) . PHP_EOL);
                echo('        <small>' . implode('<br />', $data) . '</small>' . PHP_EOL);
                echo('      </strong>' . PHP_EOL);
                echo('    </dd>' . PHP_EOL);
                echo('</dl>' . PHP_EOL);
                echo('  </a>' . PHP_EOL);
                echo('</li>' . PHP_EOL);
            }
        }

        if (!safe_count($search_results)) {
            echo('<li class="nav-header">No search results.</li>');
        }
    }
} else {
    // There is no queryString, we shouldn't get here.
    echo('<li class="nav-header">There should be no direct access to this script! Please reload the page.</li>');
}

// EOF
