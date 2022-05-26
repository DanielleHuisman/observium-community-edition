<?php

$base_dir = realpath(__DIR__ . '/..');
$config['install_dir'] = $base_dir;

include(__DIR__ . '/../includes/defaults.inc.php');
//include(dirname(__FILE__) . '/../config.php');
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
      <th >Family</th>
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
      <th style="width: 50px;"><a href="routing/protocol=bgp/type=all/sort=type/sort_order=desc/">Type&nbsp;&nbsp;<i class="small glyphicon glyphicon-triangle-bottom"></i></a></th>
      <th >Family</th>
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
      <th >Family</th>
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
    <input type="checkbox"  name="remember" id="remember"  value="1" />      <label for="remember" class="help-inline" style="margin-top: 4px;"><span style="min-width: 150px;">Remember my login</span></label>
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