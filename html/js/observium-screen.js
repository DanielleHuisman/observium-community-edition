/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage js
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

screen_detect();
// FIXME. Need way for detect monitor change (with different ratio), but no simple way for this
// window.addEventListener('resize', function(event){
//     clearTimeout(window.resizedFinished);
//     window.resizedFinished = setTimeout(function(){
//         //console.log('Resized finished.');
//         screen_detect(true);
//     }, 250);
// });

function screen_detect(force = false) {
    if (force) {
        // clear cookies
        del_cookie('observium_screen_ratio');
        del_cookie('observium_screen_resolution');
        //del_cookie('screen_scheme');
    }

    const date = new Date();
    date.setTime(date.getTime() + 3600000); // 1 hour
    let options = ' expires=' + date.toUTCString() + '; path=/; SameSite=Lax;';

    if (document.cookie.indexOf('observium_screen_ratio') === -1) {
        let screen_ratio = 1;
        if ('devicePixelRatio' in window) {
            screen_ratio = window.devicePixelRatio;
        }
        // store to cookie
        document.cookie = 'observium_screen_ratio=' + screen_ratio + ';' + options;
        document.cookie = 'observium_screen_resolution=' + screen.width + 'x' + screen.height + ';' + options;
        //console.log('screen_ratio = ' + screen_ratio);
        //console.log('screen_resolution = ' + screen.width + 'x' + screen.height);
        //console.log('screen_size = ' + window.innerWidth + 'x' + window.innerHeight);
        //if cookies are not blocked, reload the page
        //if(document.cookie.indexOf('observium_screen_ratio') != -1){
        //    window.location.reload();
        //}
    }

    // Calculate screen(window) size on every page load
    //document.cookie = 'observium_screen_size=' + window.innerWidth + 'x' + window.innerHeight + ';' + options;
    //document.cookie = 'observium_screen_size=' + document.documentElement.clientWidth + 'x' + document.documentElement.clientHeight + ';' + options;
    const color_scheme = get_color_scheme();
    if (get_cookie('screen_scheme') !== color_scheme) {
        document.cookie = 'screen_scheme=' + color_scheme + ';' + options;
        //console.log('screen_scheme = ' + color_scheme);
        // reload page on first cookie set, validate if cookie was set
        // if (get_cookie('screen_scheme') === color_scheme) {
        //   location.reload(true);
        // } else {
        //   console.log('Screen scheme not set in cookie by unknown reason.');
        // }
    } else {
        //console.log('screen_scheme (cached) = ' + color_scheme);
    }
}

function get_color_scheme() {
    return (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) ? "dark" : "light";
}

function get_cookie(name) {
    if (document.cookie.indexOf(name) === -1) {
        return;
    }

    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

function del_cookie(name) {
    if (document.cookie.indexOf(name) === -1) {
        return;
    }

    //console.log('cookie delete (' + name + ') = ' + get_cookie(name));
    document.cookie = name+'=; Max-Age=-99999999; path=/; SameSite=Lax;';
}

// EOF

