<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

?>

    <div id="main_container" class="container">

    <div class="row" style="margin-top: 50px;">
        <div class="col-sm-12 col-md-10 col-md-offset-1 col-lg-8 col-lg-offset-2 col-xl-6 col-xl-offset-3">
            <div class="box box-solid" <?php if (TRUE) {
                // FIXME. Need reduce image on small screen
                echo 'style="background-image: url(\'images/hamster-login.png\');  background-position: left 10px top -65px; background-repeat: no-repeat;"';
            } ?> >
                <div class="login-box" <?php if (isset($config['web']['logo'])) {
                    echo 'style="background: url(../images/' . escape_html($config['web']['logo']) . ') no-repeat; background-size: auto 30px; background-position: right 10px bottom 10px;"';
                } ?> >
                    <div class="row">
                        <div class="col-xs-4 col-sm-4 col-md-4">
                        </div>
                        <div class="col-xs-8 col-sm-8 col-md-8">
                            <?php
                            $form = [
                                'type'     => 'horizontal',
                                'id'       => 'logonform',
                                //'space'   => '20px',
                                //'title'   => 'Logon',
                                //'icon'    => 'oicon-key',
                                'class'    => NULL, // Use empty class here, to not add additional divs
                                'fieldset' => ['logon' => 'Please log in:'],
                            ];

                            // Reset form url without logon message
                            if (isset($_GET['lm'])) {
                                $form['url'] = $config['base_url'];
                            }

                            $form['row'][0]['username'] = [
                                'type'         => 'text',
                                'fieldset'     => 'logon',
                                'name'         => 'Username',
                                'placeholder'  => '',
                                'class'        => 'input-xlarge',
                                'style'        => 'max-width: 110%;',
                                'value'        => ''
                            ];
                            $form['row'][1]['password'] = [
                                'type'         => 'password',
                                'fieldset'     => 'logon',
                                'name'         => 'Password',
                                'autocomplete' => TRUE,
                                'placeholder'  => '',
                                'class'        => 'input-xlarge',
                                'style'        => 'max-width: 110%;',
                                'value'        => ''
                            ];
                            if ($config['login_remember_me'] && OBS_ENCRYPT) {
                                $form['row'][2]['remember'] = [
                                    'type'        => 'checkbox',
                                    'fieldset'    => 'logon',
                                    'placeholder' => 'Remember my login'
                                ];
                            }
                            $form['row'][3]['submit'] = [
                                'type'      => 'submit',
                                'name'      => 'Log in',
                                'icon'      => 'icon-lock',
                                //'right'       => TRUE,
                                'div_class' => 'controls',
                                'class'     => 'btn-large'
                            ];

                            print_form($form);
                            unset($form);

                            // Get and decrypt a logout message
                            if (isset($_GET['lm']) && $auth_message = decrypt($_GET['lm'], OBSERVIUM_PRODUCT . OBSERVIUM_VERSION)) {
                                //print_vars($_GET['lm']);
                                //print_vars($auth_message);
                                //print_vars(decrypt($_GET['lm'], OBSERVIUM_PRODUCT));
                                $_SESSION['auth_message'] = $auth_message;
                            }
                            if (isset($_SESSION['auth_message'])) {
                                echo('<div class="controls" style="text-align: center; font-weight: bold; color: #cc0000; margin-top: 15px;">' . escape_html($_SESSION['auth_message']) . '</div');
                                session_unset_var('auth_message');
                                //unset($_SESSION['auth_message']);
                            }

                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php

if (isset($config['login_message'])) {
    echo '<div class=row><div class="col-md-6 col-md-offset-3"><div style="margin-top: 10px;text-align: center; font-weight: bold; color: #cc0000;">' . escape_html($config['login_message']) . '</div></div></div>';
}

// Check if the filesystems are full
if (is_filesystem_full(__FILE__) || is_filesystem_full(session_save_path())) {
    echo '<div class=row><div class="col-md-6 col-md-offset-3"><div style="margin-top: 10px;text-align: center; font-weight: bold; color: #cc0000;">';
    print_error('Server storage seems to be full. This may affect login ability.');
    echo '</div>';
}

?>
    <script type="text/javascript">
        <!--
        document.logonform.username.focus();
        // -->
    </script>

    </div>
<?php

// EOF
