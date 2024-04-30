<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

?>

    <footer class="navbar navbar-fixed-bottom">
        <div class="navbar-inner">
            <div class="container">
                <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                    <span class="oicon-bar"></span>
                    <span class="oicon-bar"></span>
                    <span class="oicon-bar"></span>
                </a>
                <div class="nav-collapse">
                    <ul class="nav">
                        <li class="dropdown"><?php

                            if (isset($config['web']['logo'])) {
                                echo '    <a class="brand brand-observium" href="/" class="dropdown-toggle" data-hover="dropdown" data-toggle="dropdown">&nbsp;</a> ' .
                                     OBSERVIUM_VERSION_LONG;
                            } else {
                                echo '<a href="' . OBSERVIUM_URL . '" class="dropdown-toggle" data-hover="dropdown" data-toggle="dropdown">';
                                echo OBSERVIUM_PRODUCT . ' ' . OBSERVIUM_VERSION_LONG;
                                echo '</a>';
                            }
                            ?>
                            <div class="dropdown-menu" style="padding: 10px;">
                                <div style="max-width: 145px;"><img src="images/hamster-login.png" alt=""/></div>

                            </div>
                        </li>
                    </ul>

                    <ul class="nav pull-right">
                        <!--<li><a id="poller_status"></a></li>-->

                        <?php if (isset($footer_entries)) {
                            echo implode(PHP_EOL, $footer_entries);
                        } ?>

                        <li class="dropdown">
                            <?php
                            $notification_count = safe_count($notifications);
                            if ($notification_count) // FIXME level 10 only, maybe? (answer: just do not add notifications for this users. --mike)
                            {
                                $div_class = 'dropdown-menu';
                                if ($notification_count > 5) {
                                    $div_class .= ' pre-scrollable';
                                }
                                ?>
                                <a href="<?php echo(generate_url(['page' => 'overview'])); ?>"
                                   class="dropdown-toggle" data-hover="dropdown" data-toggle="dropdown">
                                    <?php echo(get_icon('alert')); ?> <b class="caret"></b></a>
                                <div class="<?php echo($div_class); ?>" style="width: 700px; max-height: 500px; z-index: 2000; padding: 10px 10px 0px;">

                                    <h3>Notifications</h3>
                                    <?php
                                    //r($notifications);
                                    foreach ($notifications as $notification) {
                                        if (isset($notification['markdown']) && $notification['markdown']) {
                                            $notification['text'] = get_markdown($notification['text'], TRUE, TRUE);
                                            if (isset($notification['title'])) {
                                                $notification['title'] = get_markdown($notification['title'], TRUE, TRUE);
                                            }
                                        }
                                        // FIXME handle severity parameter with colour or icon?
                                        if (isset($config['syslog']['priorities'][$notification['severity']])) {
                                            // Numeric severity to string
                                            $notification['severity'] = $config['syslog']['priorities'][$notification['severity']]['label-class'];
                                        }
                                        echo('<div width="100%" class="alert alert-' . $notification['severity'] . '">');
                                        $notification_title = '';
                                        if (isset($notification['unixtime'])) {
                                            $timediff           = get_time() - $notification['unixtime'];
                                            $notification_title .= format_uptime($timediff, "short-3") . ' ago: ';
                                        }
                                        if (isset($notification['title'])) {
                                            $notification_title .= $notification['title'];
                                        }
                                        if ($notification_title) {
                                            echo('<h4>' . $notification_title . '</h4>');
                                        }
                                        echo($notification['text'] . '</div>');
                                    }
                                    ?>
                                </div>
                                <?php
                            } else {
                                // Dim the icon to 20% opacity, makes the red pretty much blend in to the navbar
                                ?>
                                <a href="<?php echo(generate_url(['page' => 'overview'])); ?>" data-alt="Notification center" class="dropdown-toggle"
                                   data-hover="dropdown" data-toggle="dropdown">
                                    <i style="filter: opacity(30%);" class="sprite-checked"></i></a>
                                <?php
                            }
                            ?>
                        </li>

                        <?php

                        print_navbar_stats_debug();

                        print_navbar_stats();

                        ?>


                    </ul>
                </div>
            </div>
        </div>
    </footer>

<?php


// EOF
