<?php

$base_dir = realpath(__DIR__ . '/..');
$config['install_dir'] = $base_dir;

include(__DIR__ . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php'); // Do not include user editable config here
include(__DIR__ . "/../includes/polyfill.inc.php");
include(__DIR__ . "/../includes/autoloader.inc.php");
include(__DIR__ . "/../includes/debugging.inc.php");
require_once(__DIR__ ."/../includes/constants.inc.php");
include(__DIR__ . '/../includes/common.inc.php');
include(__DIR__ . '/../includes/definitions.inc.php');
include(__DIR__ . '/../includes/functions.inc.php');
include(__DIR__ . '/../html/includes/functions.inc.php');

class HtmlIncludesPrintTest extends \PHPUnit\Framework\TestCase
{
  /**
   * @dataProvider providerGetTableHeader
   * @group common
   */
  public function testGetTableHeader($vars, $result)
  {
    $cols = array(
                    array(NULL,            'class="state-marker"'),
                    array(NULL,            'style="width: 1px;"'),
      'device'   => array('Local address', 'style="width: 150px;"'),
                    array(NULL,            'style="width: 20px;"'),
      'peer_ip'  => array('Peer address',  'style="width: 150px;"'),
      'type'     => array('Type',          'style="width: 50px;"'),
                    array('Family'),
      'peer_as'  => 'Remote AS',
      'state'    => 'State',
                    'Uptime / Updates',
                    NULL
    );
    $this->assertSame($result, get_table_header($cols, $vars));
  }

  public function providerGetTableHeader()
  {
    return array(
      array( // Sorting enabled
        array('page' => 'routing', 'protocol' => 'bgp', 'type' => 'all'),
        '  <thead>
    <tr>
      <th class="state-marker"></th>
      <th style="width: 1px;"></th>
      <th style="width: 150px;"><a href="routing/protocol=bgp/type=all/sort=device/">Local address</a></th>
      <th style="width: 20px;"></th>
      <th style="width: 150px;"><a href="routing/protocol=bgp/type=all/sort=peer_ip/">Peer address</a></th>
      <th style="width: 50px;"><a href="routing/protocol=bgp/type=all/sort=type/">Type</a></th>
      <th>Family</th>
      <th><a href="routing/protocol=bgp/type=all/sort=peer_as/">Remote AS</a></th>
      <th><a href="routing/protocol=bgp/type=all/sort=state/">State</a></th>
      <th>Uptime / Updates</th>
      <th></th>
    </tr>
  </thead>' . PHP_EOL
      ),
      array( // Sorting enabled, selected type 
        array('page' => 'routing', 'protocol' => 'bgp', 'type' => 'all', 'sort' => 'type'),
        '  <thead>
    <tr>
      <th class="state-marker"></th>
      <th style="width: 1px;"></th>
      <th style="width: 150px;"><a href="routing/protocol=bgp/type=all/sort=device/">Local address</a></th>
      <th style="width: 20px;"></th>
      <th style="width: 150px;"><a href="routing/protocol=bgp/type=all/sort=peer_ip/">Peer address</a></th>
      <th style="width: 50px;"><a href="routing/protocol=bgp/type=all/sort=type/sort_order=desc/"><span class="text-primary" style="font-style: italic">Type</span>&nbsp;<i class="text-primary small glyphicon glyphicon-arrow-up"></i></a></th>
      <th>Family</th>
      <th><a href="routing/protocol=bgp/type=all/sort=peer_as/">Remote AS</a></th>
      <th><a href="routing/protocol=bgp/type=all/sort=state/">State</a></th>
      <th>Uptime / Updates</th>
      <th></th>
    </tr>
  </thead>' . PHP_EOL
      ),
      array( // Sorting disabled
        array(),
        '  <thead>
    <tr>
      <th class="state-marker"></th>
      <th style="width: 1px;"></th>
      <th style="width: 150px;">Local address</th>
      <th style="width: 20px;"></th>
      <th style="width: 150px;">Peer address</th>
      <th style="width: 50px;">Type</th>
      <th>Family</th>
      <th>Remote AS</th>
      <th>State</th>
      <th>Uptime / Updates</th>
      <th></th>
    </tr>
  </thead>' . PHP_EOL
      ),
    );
  }

    /**
     * @dataProvider providerGenerateTableHeader
     * @group common
     */
    public function testGenerateTableHeader($cols, $result, $vars = [])
    {
        $this->assertSame($result, generate_table_header($cols, $vars));
    }

    public function providerGenerateTableHeader()
    {
        $ports_basic = [
            'state-marker' => '',
                              [ NULL, 'style' => "width: 1px;" ],
            'device'       => [ 'device'       => 'Device', 'style' => "min-width: 150px;"],
                              [ 'port'         => 'Port Name', 'descr' => 'Description', 'errors' => 'Errors', 'style' => "min-width: 250px;"],
                              [ 'traffic'      => [ 'Bits', 'subfields' => [ 'traffic_in' => 'In', 'traffic_out' => 'Out' ] ], 'style' => "width: 100px;" ],
                              [ 'traffic_perc' => [ '%', 'subfields' => [ 'traffic_perc_in' => 'In', 'traffic_perc_out' => 'Out' ] ], 'style' => "width: 110px;" ],
                              [ 'packets'      => [ 'Pkts', 'subfields' => [ 'packets_in' => 'In', 'packets_out' => 'Out' ] ], 'style' => "width: 90px;" ],
                              [ 'speed' => 'Speed', 'mtu' => 'MTU', 'style' => "width: 90px;" ],
                              [ 'media' => 'Media', 'mac' => 'MAC', 'style' => "width: 150px;" ]
        ];

        $ports_detail = [
            'state-marker' => '',
                              [ NULL, 'style' => "width: 1px;" ],
            'device'       => [ 'device' => 'Device', 'style' => "min-width: 150px;" ],
                              [ 'port'    => 'Port Name', 'descr' => 'Description', 'errors' => 'Errors', 'style' => "min-width: 250px;" ],
                              [ NULL ],
                              [ 'traffic' => [ 'Bits', 'subfields' => [ 'traffic_in' => 'In', 'traffic_out' => 'Out' ] ],
                                'packets' => [ 'Pkts', 'subfields' => [ 'packets_in' => 'In', 'packets_out' => 'Out' ] ], 'style' => "width: 100px;" ],
                              [ 'media'   => "Media", 'speed' => 'Speed' ],
                              [ 'mac'     => "MAC" ],
        ];

        $ports_detail2 = [
            'state-marker' => '',
            [ NULL, 'style' => "width: 1px;" ],
            [ 'device' => 'Device', 'style' => "min-width: 150px;" ],
            [ 'port'    => 'Port Name', 'descr' => 'Description', 'errors' => 'Errors', 'style' => "min-width: 250px;" ],
            [ NULL ],
            [ 'traffic' => [ 'Bits', 'subfields' => [ 'traffic_in' => 'In', 'traffic_out' => 'Out' ] ],
              'packets' => [ 'Pkts', 'subfields' => [ 'packets_in' => 'In', 'packets_out' => 'Out' ] ], 'style' => "width: 100px;" ],
            [ 'media'   => "Media", 'speed' => 'Speed' ],
            [ 'mac'     => "MAC" ],
        ];

        $mixed = [
            'class' => 'header',
            'style' => 'width: 100%;',
            'state-marker' => '',
            [ 'Date', 'style' => 'width: 150px;' ],
            [ 'user' => 'User' ],
            [ 'from' => 'From' ],
            [ 'User-Agent', 'style' => 'width: 200px;' ],
            [ 'Action', 'Test' ],
        ];

        $array = [];

        $array[] = [
            $ports_basic,
            '  <thead>
    <tr>
      <th class="state-marker"></th>
      <th style="width: 1px;"></th>
      <th style="min-width: 150px;"><a href="ports/format=list/view=basic/sort=device/">Device</a></th>
      <th style="min-width: 250px;"><a href="ports/format=list/view=basic/sort=port/">Port Name</a> / <a href="ports/format=list/view=basic/sort=descr/">Description</a> / <a href="ports/format=list/view=basic/sort=errors/">Errors</a></th>
      <th style="width: 100px;"><a href="ports/format=list/view=basic/sort=traffic/">Bits</a> [<a href="ports/format=list/view=basic/sort=traffic_in/">In</a> / <a href="ports/format=list/view=basic/sort=traffic_out/">Out</a>]</th>
      <th style="width: 110px;"><a href="ports/format=list/view=basic/sort=traffic_perc/">%</a> [<a href="ports/format=list/view=basic/sort=traffic_perc_in/">In</a> / <a href="ports/format=list/view=basic/sort=traffic_perc_out/">Out</a>]</th>
      <th style="width: 90px;"><a href="ports/format=list/view=basic/sort=packets/">Pkts</a> [<a href="ports/format=list/view=basic/sort=packets_in/">In</a> / <a href="ports/format=list/view=basic/sort=packets_out/">Out</a>]</th>
      <th style="width: 90px;"><a href="ports/format=list/view=basic/sort=speed/">Speed</a> / <a href="ports/format=list/view=basic/sort=mtu/">MTU</a></th>
      <th style="width: 150px;"><a href="ports/format=list/view=basic/sort=media/">Media</a> / <a href="ports/format=list/view=basic/sort=mac/">MAC</a></th>
    </tr>
  </thead>
',
            [ 'page' => 'ports', 'format' => 'list', 'view' => 'basic' ]
        ];

        $array[] = [
            $ports_detail,
            '  <thead>
    <tr>
      <th class="state-marker"></th>
      <th style="width: 1px;"></th>
      <th style="min-width: 150px;"><a href="ports/format=list/view=detail/sort=device/">Device</a></th>
      <th style="min-width: 250px;"><a href="ports/format=list/view=detail/sort=port/">Port Name</a> / <a href="ports/format=list/view=detail/sort=descr/">Description</a> / <a href="ports/format=list/view=detail/sort=errors/">Errors</a></th>
      <th></th>
      <th style="width: 100px;"><a href="ports/format=list/view=detail/sort=traffic/">Bits</a> [<a href="ports/format=list/view=detail/sort=traffic_in/">In</a> / <a href="ports/format=list/view=detail/sort=traffic_out/">Out</a>] / <a href="ports/format=list/view=detail/sort=packets/">Pkts</a> [<a href="ports/format=list/view=detail/sort=packets_in/">In</a> / <a href="ports/format=list/view=detail/sort=packets_out/">Out</a>]</th>
      <th><a href="ports/format=list/view=detail/sort=media/">Media</a> / <a href="ports/format=list/view=detail/sort=speed/">Speed</a></th>
      <th><a href="ports/format=list/view=detail/sort=mac/">MAC</a></th>
    </tr>
  </thead>
',
            [ 'page' => 'ports', 'format' => 'list', 'view' => 'detail' ]
        ];

        $array[] = [
            $ports_detail2,
            '  <thead>
    <tr>
      <th class="state-marker"></th>
      <th style="width: 1px;"></th>
      <th style="min-width: 150px;"><a href="ports/format=list/view=detail/sort=device/">Device</a></th>
      <th style="min-width: 250px;"><a href="ports/format=list/view=detail/sort=port/">Port Name</a> / <a href="ports/format=list/view=detail/sort=descr/">Description</a> / <a href="ports/format=list/view=detail/sort=errors/">Errors</a></th>
      <th></th>
      <th style="width: 100px;"><a href="ports/format=list/view=detail/sort=traffic/">Bits</a> [<a href="ports/format=list/view=detail/sort=traffic_in/">In</a> / <a href="ports/format=list/view=detail/sort=traffic_out/">Out</a>] / <a href="ports/format=list/view=detail/sort=packets/">Pkts</a> [<a href="ports/format=list/view=detail/sort=packets_in/">In</a> / <a href="ports/format=list/view=detail/sort=packets_out/">Out</a>]</th>
      <th><a href="ports/format=list/view=detail/sort=media/">Media</a> / <a href="ports/format=list/view=detail/sort=speed/">Speed</a></th>
      <th><a href="ports/format=list/view=detail/sort=mac/">MAC</a></th>
    </tr>
  </thead>
',
            [ 'page' => 'ports', 'format' => 'list', 'view' => 'detail' ]
        ];

        $array[] = [
            $mixed,
            '  <thead class="header" style="width: 100%;">
    <tr>
      <th class="state-marker"></th>
      <th style="width: 150px;">Date</th>
      <th><a href="/sort=user/">User</a></th>
      <th><a href="/sort=from/">From</a></th>
      <th style="width: 200px;">User-Agent</th>
      <th>Action / Test</th>
    </tr>
  </thead>
',
            [ TRUE ] // just not empty vars
        ];

        $array[] = [
            $mixed,
            '  <thead class="header" style="line-height: 0; visibility: collapse;">
    <tr>
      <th class="state-marker"></th>
      <th style="width: 150px;">Date</th>
      <th><a href="/sort=user/">User</a></th>
      <th><a href="/sort=from/">From</a></th>
      <th style="width: 200px;">User-Agent</th>
      <th>Action / Test</th>
    </tr>
  </thead>
',
            [ 'show_header' => 0 ]
        ];

        $array[] = [
            $mixed,
            '  <thead class="header" style="width: 100%;">
    <tr>
      <th class="state-marker"></th>
      <th style="width: 150px;">Date</th>
      <th>User</th>
      <th>From</th>
      <th style="width: 200px;">User-Agent</th>
      <th>Action / Test</th>
    </tr>
  </thead>
',
            [ 'show_sort' => 0 ]
        ];

        return $array;
    }

    /**
     * @dataProvider providerGenerateButtonGroup
     * @group common
     */
    public function testGenerateButtonGroup($buttons, $opts, $result)
    {
        $this->assertSame($result, generate_button_group($buttons, $opts));
    }

    public function providerGenerateButtonGroup()
    {
        return [
            [
                [
                    [ 'title' => 'Edit',   'event' => 'default', 'url' => '#modal-edit_syslog_rule_' . $la['la_id'], 'icon' => 'icon-cog text-muted', 'attribs' => [ 'data-toggle' => 'modal' ] ],
                    [ 'title' => 'Delete', 'event' => 'danger',  'url' => '#modal-delete_syslog_rule_' . $la['la_id'], 'icon' => 'icon-trash', 'attribs' => [ 'data-toggle' => 'modal' ] ],
                ],
                [ 'title' => 'Rule actions' ],
                '    <div class="btn-group btn-group-xs" role="group" aria-label="Rule actions">
      <a role="group" class="btn btn-default" title="Edit" href="#modal-edit_syslog_rule_"data-toggle="modal"><i class="icon-cog text-muted "></i></a>
      <a role="group" class="btn btn-danger" title="Delete" href="#modal-delete_syslog_rule_"data-toggle="modal"><i class="icon-trash "></i></a>
    </div>' . PHP_EOL
            ],
        ];
    }

  /**
   * @dataProvider providerGetForm
   * @group forms
   */
  public function testGetForm($form, $html)
  {
    //echo "\n<<<FORM\n" . generate_form($form) . "\nFORM\n";
    $this->assertSame($html, generate_form($form));
  }

  public function providerGetForm()
  {
    // Temporary use direct array, need switch to json+txt includes

    $array = [];
    $form = ['type'     => 'horizontal',
             'id'       => 'logonform',
             //'space'   => '20px',
             //'title'   => 'Logon',
             //'icon'    => 'oicon-key',
             'class'    => NULL, // Use empty class here, to not add additional divs
             'fieldset' => ['logon' => 'Please log in:'],
    ];

    $form['row'][0]['username'] = [
      'type'        => 'text',
      'fieldset'    => 'logon',
      'name'        => 'Username',
      'placeholder' => '',
      'class'       => 'input-xlarge',
      //'width'       => '95%',
      'value'       => ''];
    $form['row'][1]['password'] = [
      'type'         => 'password',
      'fieldset'     => 'logon',
      'name'         => 'Password',
      'autocomplete' => TRUE,
      'placeholder'  => '',
      'class'        => 'input-xlarge',
      //'width'       => '95%',
      'value'        => ''];

    $form['row'][3]['submit'] = [
      'type'      => 'submit',
      'name'      => 'Log in',
      'icon'      => 'icon-lock',
      //'right'       => TRUE,
      'div_class' => 'controls',
      'class'     => 'btn-large'];

    $html = <<<FORM

<!-- START logonform -->

<form method="POST" id="logonform" name="logonform" action="" class="form form-horizontal" style="margin-bottom: 0px;">

          <fieldset> <!-- START fieldset-logon -->
          <div class="control-group">
              <div class="controls">
                  <h3>Please log in:</h3>
              </div>
          </div>
  <div id="username_div" class="control-group" style="margin-bottom: 5px;"> <!-- START row-0 -->
    <label class="control-label" for="username">Username</label>

    <div id="username_div" class="controls">

    <input type="text" placeholder="" name="username" id="username" class="input input-xlarge"  value="" />

    </div>
  </div> <!-- END row-0 -->
  <div id="password_div" class="control-group" style="margin-bottom: 5px;"> <!-- START row-1 -->
    <label class="control-label" for="password">Password</label>

    <div id="password_div" class="controls">

    <input type="password" placeholder="" name="password" id="password" class="input input-xlarge"  value="" />

    </div>
  </div> <!-- END row-1 -->

          </fieldset>  <!-- END fieldset-logon -->


    <div id="submit_div" class="controls">
      <button id="submit" name="submit" type="submit" class="btn btn-default btn-large text-nowrap" value=""><i style="margin-right: 0px;" class="icon-lock"></i>&nbsp;&nbsp;Log in</button>
    </div>
</form>

<!-- END logonform -->

FORM;
    $array[] = [$form, $html];

    // Force text field with autocomplete OFF
    $form['row'][0]['username']['autocomplete'] = FALSE;
    $html = <<<FORM

<!-- START logonform -->

<form method="POST" id="logonform" name="logonform" action="" class="form form-horizontal" style="margin-bottom: 0px;">

          <fieldset> <!-- START fieldset-logon -->
          <div class="control-group">
              <div class="controls">
                  <h3>Please log in:</h3>
              </div>
          </div>
  <div id="username_div" class="control-group" style="margin-bottom: 5px;"> <!-- START row-0 -->
    <label class="control-label" for="username">Username</label>

    <div id="username_div" class="controls">

    <input type="text"  autocomplete="off" placeholder="" name="username" id="username" class="input input-xlarge"  value="" />

    </div>
  </div> <!-- END row-0 -->
  <div id="password_div" class="control-group" style="margin-bottom: 5px;"> <!-- START row-1 -->
    <label class="control-label" for="password">Password</label>

    <div id="password_div" class="controls">

    <input type="password" placeholder="" name="password" id="password" class="input input-xlarge"  value="" />

    </div>
  </div> <!-- END row-1 -->

          </fieldset>  <!-- END fieldset-logon -->


    <div id="submit_div" class="controls">
      <button id="submit" name="submit" type="submit" class="btn btn-default btn-large text-nowrap" value=""><i style="margin-right: 0px;" class="icon-lock"></i>&nbsp;&nbsp;Log in</button>
    </div>
</form>

<!-- END logonform -->

FORM;
    $array[] = [$form, $html];

    // Removed password autocomplete
    unset($form['row'][1]['password']['autocomplete'], $form['row'][0]['username']['autocomplete']);
    $html = <<<FORM

<!-- START logonform -->

<form method="POST" id="logonform" name="logonform" action="" class="form form-horizontal" style="margin-bottom: 0px;">

          <fieldset> <!-- START fieldset-logon -->
          <div class="control-group">
              <div class="controls">
                  <h3>Please log in:</h3>
              </div>
          </div>
  <div id="username_div" class="control-group" style="margin-bottom: 5px;"> <!-- START row-0 -->
    <label class="control-label" for="username">Username</label>

    <div id="username_div" class="controls">

    <input type="text" placeholder="" name="username" id="username" class="input input-xlarge"  value="" />

    </div>
  </div> <!-- END row-0 -->
  <div id="password_div" class="control-group" style="margin-bottom: 5px;"> <!-- START row-1 -->
    <label class="control-label" for="password">Password</label>

    <div id="password_div" class="controls">

<input type="password" id="disable-pwd-mgr-1" autocomplete="off" style="display:none;" tabindex="-1" value="disable-pwd-mgr-1" /><input type="password" id="disable-pwd-mgr-2" autocomplete="off" style="display:none;" tabindex="-1" value="disable-pwd-mgr-2" /><input type="password" id="disable-pwd-mgr-3" autocomplete="off" style="display:none;" tabindex="-1" value="disable-pwd-mgr-3" />    <input type="password"  autocomplete="new-password" placeholder="" name="password" id="password" class="input input-xlarge"  value="" />

    </div>
  </div> <!-- END row-1 -->

          </fieldset>  <!-- END fieldset-logon -->


    <div id="submit_div" class="controls">
      <button id="submit" name="submit" type="submit" class="btn btn-default btn-large text-nowrap" value=""><i style="margin-right: 0px;" class="icon-lock"></i>&nbsp;&nbsp;Log in</button>
    </div>
</form>

<!-- END logonform -->

FORM;
    $array[] = [$form, $html];

    // Append checkbox
    $form['row'][2]['remember'] = [
      'type'        => 'checkbox',
      'fieldset'    => 'logon',
      'placeholder' => 'Remember my login'];
    $html = <<<FORM

<!-- START logonform -->

<form method="POST" id="logonform" name="logonform" action="" class="form form-horizontal" style="margin-bottom: 0px;">

          <fieldset> <!-- START fieldset-logon -->
          <div class="control-group">
              <div class="controls">
                  <h3>Please log in:</h3>
              </div>
          </div>
  <div id="username_div" class="control-group" style="margin-bottom: 5px;"> <!-- START row-0 -->
    <label class="control-label" for="username">Username</label>

    <div id="username_div" class="controls">

    <input type="text" placeholder="" name="username" id="username" class="input input-xlarge"  value="" />

    </div>
  </div> <!-- END row-0 -->
  <div id="password_div" class="control-group" style="margin-bottom: 5px;"> <!-- START row-1 -->
    <label class="control-label" for="password">Password</label>

    <div id="password_div" class="controls">

<input type="password" id="disable-pwd-mgr-1" autocomplete="off" style="display:none;" tabindex="-1" value="disable-pwd-mgr-1" /><input type="password" id="disable-pwd-mgr-2" autocomplete="off" style="display:none;" tabindex="-1" value="disable-pwd-mgr-2" /><input type="password" id="disable-pwd-mgr-3" autocomplete="off" style="display:none;" tabindex="-1" value="disable-pwd-mgr-3" />    <input type="password"  autocomplete="new-password" placeholder="" name="password" id="password" class="input input-xlarge"  value="" />

    </div>
  </div> <!-- END row-1 -->
  <div id="remember_div" class="control-group" style="margin-bottom: 5px;"> <!-- START row-2 -->

    <div id="remember_div" class="controls">
    <input type="checkbox"  name="remember" id="remember" value="1" />
      <label for="remember" class="help-inline" style="margin-top: 4px;"><span style="min-width: 150px;">Remember my login</span></label>
    </div>
  </div> <!-- END row-2 -->

          </fieldset>  <!-- END fieldset-logon -->


    <div id="submit_div" class="controls">
      <button id="submit" name="submit" type="submit" class="btn btn-default btn-large text-nowrap" value=""><i style="margin-right: 0px;" class="icon-lock"></i>&nbsp;&nbsp;Log in</button>
    </div>
</form>

<!-- END logonform -->

FORM;
    $array[] = [$form, $html];

    return $array;
  }

  /**
   * @dataProvider providerGetMarkdown
   * @group html
   */
  public function testGetMarkdown($text, $result, $extra = FALSE) {
    $this->assertSame($result, get_markdown($text, TRUE, $extra));
  }

  public function providerGetMarkdown() {
    $array = [];

    // Simple
    $array[] = [ 'Hello _Observium_!', '<span style="min-width: 150px;">Hello <em>Observium</em>!</span>' ];
    $array[] = [ 'an autolink http://example.com',
                 '<span style="min-width: 150px;">an autolink <a href="http://example.com">http://example.com</a></span>' ];
    $array[] = [ 'inside of brackets [http://example.com], inside of braces {http://example.com},  inside of parentheses (http://example.com)',
                 '<span style="min-width: 150px;">inside of brackets [<a href="http://example.com">http://example.com</a>], inside of braces {<a href="http://example.com">http://example.com</a>},  inside of parentheses (<a href="http://example.com">http://example.com</a>)</span>' ];
    $array[] = [ 'trailing slash http://example.com/ and http://example.com/path/',
                 '<span style="min-width: 150px;">trailing slash <a href="http://example.com/">http://example.com/</a> and <a href="http://example.com/path/">http://example.com/path/</a></span>' ];

    // Extra
    $array[] = [ 'Please read [FAQ](' . OBSERVIUM_DOCS_URL . '/faq/#snmpv3-strong-authentication-or-encryption){target=_blank}.',
                 '<span style="min-width: 150px;">Please read <a href="' . OBSERVIUM_DOCS_URL . '/faq/#snmpv3-strong-authentication-or-encryption" target="_blank">FAQ</a>.</span>', TRUE ];

    // XSS
    $array[] = [ 'inside of brackets [javascript:alert(1)], inside of braces {javascript:alert(1)}, inside of parentheses (javascript:alert(1)), [click me](javascript:alert(1))',
                 '<span style="min-width: 150px;">inside of brackets [javascript:alert(1)], inside of braces {javascript:alert(1)}, inside of parentheses (javascript:alert(1)), <a href="javascript:void(0)">click me</a></span>' ];

    // Multiline
    $text = <<<TEXT
Welcome to the demo:

1. Write Markdown text on the left
2. Hit the __Parse__ button or `⌘ + Enter`
3. See the result to on the right
TEXT;
    $html = <<<HTML
<div style="min-width: 150px;">
<p>Welcome to the demo:</p>
<ol>
<li>Write Markdown text on the left</li>
<li>Hit the <strong>Parse</strong> button or <code>⌘ + Enter</code></li>
<li>See the result to on the right</li>
</ol>
</div>
HTML;
    $array[] = [ $text, $html ];

    return $array;
  }
}

// EOF