/*
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage js
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Workaround jQuery XSS
jQuery.htmlPrefilter = function (html) {
    return html;
};

function time_refresh(element_id, start_time, append_sec = -1) {
    // set initial seconds left we're counting down to
    let seconds_left = start_time;
    // get tag element
    const countrefresh = document.getElementById(element_id);
    // time interval
    const interval = Math.abs(append_sec) * 1000;

    let timestring = function (seconds_left) {
        // do some time calculations
        let hours   = parseInt(seconds_left / 3600);
        let minutes = parseInt(seconds_left / 60);
        let seconds = parseInt(seconds_left % 60);

        // format countdown string + set tag value
        if (hours > 0) {
            minutes = minutes - (hours * 60)
            hours = hours + 'h&nbsp;'
        } else {
            hours = '';
        }
        if (minutes > 0) {
            minutes = minutes + 'min&nbsp;';
            seconds = seconds + 'sec';
        } else {
            minutes = '';
            if (seconds > 0) {
                seconds = seconds + 'sec';
            } else {
                seconds = '0sec';
            }
        }

        return '<div class="label">' + hours + minutes + seconds + '</div>'
    }

    // initial string
    countrefresh.innerHTML = timestring(seconds_left);

    // update the tag with id "countdown" every 'append_sec' second
    let loop = setInterval(function () {
        seconds_left = seconds_left + append_sec;
        countrefresh.innerHTML = timestring(seconds_left);

        if (seconds_left <= 0) {
            clearInterval(loop);
        }
    }, interval);
}

function url_from_form(form_id) {
    let url = document.getElementById(form_id).action;
    const partFields = document.getElementById(form_id).elements;

    let seen = {};
    for (let el, i = 0, n = partFields.length; i < n; i++) {
        el = partFields[i];
        if (el.name === 'requesttoken' || el.name === '') {
            // Ignore request token from forms, this value must be only as POST request
            continue;
        }
        if (el.type === "checkbox") {
            if (seen[el.name] !== 1) { // Skip duplicate vars
                seen[el.name] = 1;
                url = url.replace(new RegExp(encodeURIComponent(el.name) + '=[^\/]+\/', 'g'), ''); // remove old var from url
                if (el.checked) {
                    url += encodeURIComponent(el.name) + '=' + encode_var(el.value) + '/';
                } else {
                    // default checkbox to 0
                    url += encodeURIComponent(el.name) + '=0/';
                }
            }
            //console.log(el.name + " = '" + el.value + "', checked = ", el.checked);
        } else if (el.value !== '') {
            if (el.multiple) {
                let multi = [];
                for (let part, ii = 0, nn = el.length; ii < nn; ii++) {
                    part = el[ii];
                    if (part.selected) {
                        multi.push(encode_var(part.value));
                    }
                }
                el.name = el.name.replace('[]', '');
                if (multi.length && seen[el.name] !== 1) {
                    seen[el.name] = 1;
                    url = url.replace(new RegExp(encodeURIComponent(el.name) + '=[^\/]+\/', 'g'), ''); // remove old var from url
                    url += encodeURIComponent(el.name) + '=' + multi.join(',') + '/';
                }
            } else if (el.checked || el.type !== "checkbox") {
                if (seen[el.name] !== 1) {  // Skip duplicate vars
                    seen[el.name] = 1;
                    url = url.replace(new RegExp(encodeURIComponent(el.name) + '=[^\/]+\/', 'g'), ''); // remove old var from url
                    url += encodeURIComponent(el.name) + '=' + encode_var(el.value) + '/';
                }
            }
        }
    }

    return url;
}

function form_to_path(form_id) {
    url = url_from_form(form_id);
    //console.log(url);
    window.location.href = url;
}

function encode_var(value) {
    // Control characters have nothing to do inside a URL.
    var val = value.replace(/%/g, '%05')  // %05 (ENQ, enquiry) - not defined in HTML 4 standard
        .replace(/\//g, '%7F') // %7F (DEL, delete) - not defined in HTML 4 standard
        .replace(/,/g, '%1F'); // %1F (US, unit separator) - not defined in HTML 4 standard
    val = encodeURIComponent(val);
    // add quotes for empty or multiword strings
    if ((val !== value || val === '') && !is_base64(value)) {
        //console.log(value + " != " + val + " : " + atob(val));
        val = '"' + val + '"'
    }

    return val;
}

function is_base64(value) {
    try {
        // Detect if value is base64 encoded string
        atob(value);
        //console.log("Value '" + value + "' is base64 encoded.");
        return true;
    } catch (e) {
        // if you want to be specific and only catch the error which means
        // the base 64 was invalid, then check for 'e.code === 5'.
        // (because 'DOMException.INVALID_CHARACTER_ERR === 5')

        //console.log("Value '" + value + "' is not base64.");
        return false;
    }
}

function submitURL(form_id) {
    url = url_from_form(form_id);
    $(document.getElementById(form_id)).attr('action', url);
}

// toggle attributes readonly,disabled by form id
function toggleAttrib(attribute, form_id) {
    //console.log('attrib: '+attrib+', id: '+form_id);
    //console.log(Array.isArray(form_id));
    if (Array.isArray(form_id)) {
        form_id.forEach(function (entry) {
            toggleAttrib(attribute, entry);
            //console.log(entry);
        });
        return;
    }

    //let toggle = document.getElementById(form_id); // js object
    let dom;
    if (form_id.indexOf('[') > -1) {
        dom = '[id^="' + form_id + '"]'; // array(s)
    } else {
        dom = '[id="' + form_id + '"]'; // element
    }
    let toggles = document.querySelectorAll(dom); // js objects
    //console.log(toggles);

    $(dom).each(function (i) {
        let element = $(this);   // jQuery object
        let toggle = toggles[i]; // js object
        //console.log(toggle);
        //console.log(element);

        let attrib;
        if (attribute === 'readonly' && (element.prop('type') === 'submit' || element.prop('type') === 'button')) {
            // buttons not know readonly
            attrib = 'disabled';
        } else {
            attrib = attribute;
        }
        //console.log('prop: '+element.prop(attrib));
        //console.log('attr: '+element.attr(attrib));
        //console.log('Attribute: '+toggle.getAttribute(attrib));

        //var set   = !toggle.getAttribute(attrib);
        let set = !(toggle.getAttribute(attrib) || element.prop(attrib));
        //console.log(set);

        set ? toggle.setAttribute(attrib, 1) : toggle.removeAttribute(attrib);
        if (element.prop('localName') === 'select') {
            if (attrib === 'readonly') {
                // readonly attr not supported by bootstrap-select
                set ? toggle.setAttribute('disabled', 1) : toggle.removeAttribute('disabled');
            }
            if (element.hasClass('selectpicker')) {
                // bootstrap select
                element.selectpicker('refresh'); // re-render selectpicker
                //console.log('bootstrap-select');
            } else if (toggle.hasAttribute('data-toggle') && toggle.getAttribute('data-toggle') === 'tagsinput') {
                // bootstrap tagsinput
                element.tagsinput('refresh'); // re-render tagsinput
                //console.log('bootstrap-tagsinput');
            }

        } else if (toggle.hasAttribute('data-toggle') && toggle.hasAttribute('data-selector') && toggle.getAttribute('data-selector').substr(0, 11) === 'tiny-toggle') {
            // tiny toggle
            if (attrib === 'readonly') {
                // readonly attr not supported by tiny-toggle
                !set ? toggle.setAttribute(attrib, 1) : toggle.removeAttribute(attrib); // revert
                attrib = 'disabled';
                set = !(toggle.getAttribute('disabled') || element.prop('disabled'));
            }
            if (attrib === 'disabled') {
                //console.log('disabled: '+set);
                if (set) {
                    toggle.setAttribute('disabled', 1);
                    element.tinyToggle("disable");
                } else {
                    toggle.removeAttribute('disabled');
                    element.tinyToggle("enable");
                }
            } else {
                element.tinyToggle("toggle");
            }
            //console.log('tiny-toggle: '+attrib);
        } else if (toggle.hasAttribute('data-toggle') && toggle.hasAttribute('data-selector') && toggle.getAttribute('data-selector') === 'bootstrap-toggle') {
            // bootstrap toggle
            element.bootstrapToggle('toggle');
            //console.log('bootstrap-toggle');
        } else if (toggle.hasAttribute('data-format')) {
            set ? $('[id="' + form_id + '_div"]').datetimepicker('disable') : $('[id="' + form_id + '_div"]').datetimepicker('enable');
            //console.log('bootstrap-datetime');
            //console.log($('#'+form_id+'_div'));
        } else if (element.prop('type') === 'submit' || element.prop('type') === 'button') {
            // submit buttons
            set ? element.addClass('disabled') : element.removeClass('disabled');
            //}
        }

    });
}

function toggleOn(target_id) {
    if (Array.isArray(target_id)) {
        target_id.forEach(function (entry) {
            toggleOn(entry);
            //console.log(entry);
        });
        return;
    }

    //let toggle = document.getElementById(target_id); // js object
    let dom;
    if (form_id.indexOf('[') > -1) {
        dom = '[id^="' + target_id + '"]'; // array(s)
    } else {
        dom = '[id="' + target_id + '"]'; // element
    }
    let toggles = document.querySelectorAll(dom); // js objects

    $(dom).each(function (i) {
        let element = $(this);   // jQuery object
        let toggle = toggles[i]; // js object

        if (toggle.hasAttribute('data-toggle') && toggle.hasAttribute('data-selector') && toggle.getAttribute('data-selector') === 'bootstrap-toggle') {
            // bootstrap toggle
            element.bootstrapToggle('on');
            //console.log('bootstrap-toggle');
        } else if (toggle.hasAttribute('data-toggle') && toggle.hasAttribute('data-selector') && toggle.getAttribute('data-selector').substr(0, 11) === 'tiny-toggle') {
            // tiny toggle
            element.tinyToggle("check");
        } else if (element.prop('type') === 'checkbox') {
            // common checkbox
            element.prop('checked', true);
        } else if (element.prop('type') === 'submit' || element.prop('type') === 'button') {
            // submit buttons
            element.removeClass('disabled');
            toggle.removeAttribute('readonly');
            toggle.removeAttribute('disabled');
        }
    });
}

function toggleOff(target_id) {
    if (Array.isArray(target_id)) {
        target_id.forEach(function (entry) {
            toggleOff(entry);
            //console.log(entry);
        });
        return;
    }

    //let toggle = document.getElementById(target_id); // js object
    let dom;
    if (form_id.indexOf('[') > -1) {
        dom = '[id^="' + target_id + '"]'; // array(s)
    } else {
        dom = '[id="' + target_id + '"]'; // element
    }
    let toggles = document.querySelectorAll(dom); // js objects

    $(dom).each(function (i) {
        let element = $(this);   // jQuery object
        let toggle = toggles[i]; // js object

        if (toggle.hasAttribute('data-toggle') && toggle.hasAttribute('data-selector') && toggle.getAttribute('data-selector') === 'bootstrap-toggle') {
            // bootstrap toggle
            element.bootstrapToggle('off');
            //console.log('bootstrap-toggle');
        } else if (toggle.hasAttribute('data-toggle') && toggle.hasAttribute('data-selector') && toggle.getAttribute('data-selector').substr(0, 11) === 'tiny-toggle') {
            // tiny toggle
            element.tinyToggle("uncheck");
        } else if (element.prop('type') === 'checkbox') {
            // common checkbox
            element.prop('checked', false);
        } else if (element.prop('type') === 'submit' || element.prop('type') === 'button') {
            // submit buttons
            element.addClass('disabled');
            toggle.setAttribute('readonly', 1);
            toggle.setAttribute('disabled', 1);
        }
    });
}

// Hide/show div by id or alert class (default)
function showDiv(checked, id) {
    id = typeof id !== 'undefined' ? '[id=' + id + ']' : '.alert';
    //console.log($(id));
    if (checked) {
        $(id).hide();
    } else {
        $(id).show();
    }
}

function revealHiddenOverflow(d) {
    if (d.style.overflow == "hidden") {
        d.style.overflow = "visible";
    } else {
        d.style.overflow = "hidden";
    }
}

// This function open links by onclick event and prevent duplicate windows when Meta/Ctrl key pressed
function openLink(url) {
    document.onclick = function (event) {
        //console.log(link);
        //console.log(event);
        //console.log(event.target, event.target.nodeName);
        //console.log("Meta: " + event.metaKey, "Ctrl: " + event.ctrlKey);

        if (event.metaKey || event.ctrlKey) {
            // Open link in new tab (except on A tags)
            if (event.target.nodeName != "A") {
                window.open(url, '_blank');
            }
        } else {
            // open link in current window/tab
            location.href = url;
        }
    }
}

function entity_popup(element) {
    var url = $(element).data('url') || 'ajax/entity_popup.php'; // Use URL from data tag if available, otherwise default to 'ajax/entity_popup.php'

    $(element).qtip({
        content: {
            text: '<i class="icon-spinner icon-spin text-center vertical-align" style="width: 100%;"></i>',
            ajax: {
                url: url,
                type: 'POST',
                loading: false,
                data: function() {
                    // If a full URL is provided, don't send the entity type and ID
                    if ($(element).data('url')) {
                        return {};
                    } else {
                        return {
                            entity_type: $(element).data('etype'),
                            entity_id: $(element).data('eid'),
                        };
                    }
                }(),
            }
        },
        style: {
            classes: 'qtip-bootstrap',
        },
        position: {
            target: 'mouse',
            viewport: $(window),
            adjust: {
                x: 7, y: 15,
                mouse: false, // don't follow the mouse
                method: 'shift'
            }
        },
        hide: {
            fixed: true, // Non-hoverable by default
        }
    });
}


function entity_popups() {
    $('body').on('mouseover', '.entity-popup', function () {
        // Check if the tooltip is already initialized
        if (!$(this).data('qtip')) {
            entity_popup(this);
            // Trigger the 'mouseover' event to immediately show the tooltip
            $(this).trigger('mouseover');
        }
    });
}

function popups_from_data() {

    // Qtip tooltips
    $(".tooltip-from-element").each(function () {
        var selector = '#' + $(this).data('tooltip-id');
        //console.log(selector);
        $(this).qtip({
            content: {
                text: $(selector)
            },
            style: {
                classes: 'qtip-bootstrap',
            },
            position: {
                target: 'mouse',
                viewport: $(window),
                adjust: {
                    x: 7, y: 15,
                    mouse: false, // don't follow the mouse
                    method: 'shift'
                }
            },
            hide: {
                fixed: true // Non-hoverable by default
            }
        });
    });

    $("[data-rel='tooltip']").qtip({
        content: {
            attr: 'data-tooltip'
        },
        style: {
            classes: 'qtip-bootstrap',
        },
        position: {
            //target: 'mouse',
            viewport: $(window),
            adjust: {
                x: 7, y: 15,
                //mouse: false, // don't follow the mouse
                method: 'shift'
            }
        }
    });

    $("[data-toggle='toggle']").qtip({
        content: {
            attr: 'title'
        },
        style: {
            classes: 'qtip-bootstrap',
        },
        position: {
            target: 'mouse',
            viewport: $(window),
            adjust: {
                x: 7, y: 15,
                //mouse: false, // don't follow the mouse
                method: 'shift'
            }
        }
    });

    $('.tooltip-from-data').qtip({
        content: {
            attr: 'data-tooltip'
        },
        style: {
            classes: 'qtip-bootstrap',
        },
        position: {
            target: 'mouse',
            viewport: $(window),
            adjust: {
                x: 7, y: 15,
                mouse: false, // don't follow the mouse
                method: 'shift'
            }
        },
        hide: {
            fixed: true // Non-hoverable by default
        }
    });
}

// prevent a resubmit POST on refresh and back button
function clear_post_query() {
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
}

// jQuery specific observium additions
jQuery(document).ready(function () {
    // Bootstrap classic tooltips
    $('[data-toggle="tooltip"]').tooltip();
});


var toggle_visibility = (function () {
    function toggle(cl) {
        var els = document.getElementsByClassName(cl);
        for (var i = 0; i < els.length; ++i) {
            var s = els[i].style;
            s.display = s.display === 'none' ? 'block' : 'none';
        }

    }

    return function (cl) {
        if (cl instanceof Array) {
            for (var i = 0; i < cl.length; ++i) {
                toggle(cl[i]);
            }
        } else {
            toggle(cl);
        }
    };
})();

// Used to set session variables and then reload page.
function ajax_action(action, value = '') {

    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    var params = {
        action: action,
        value: value,
        requesttoken: csrfToken
    };

    $.ajax({
        type: "POST",
        url: "ajax/actions.php",
        async: true,
        cache: false,
        data: jQuery.param(params),
        success: function (response) {
            if (response.status === 'ok') {
                location.reload(true);
                //console.log(response);
            } else {
                console.log(response);
            }
        }
    });

    event.preventDefault();

    return false;
}

function confirmAction(action, element, event) {
    event.stopPropagation();  // Stop the event from propagating to parent <td>

    var value = $(element).data('value');

    $(event.target).confirmation({
        rootSelector: '[data-toggle=confirmation]',
        onConfirm: function() {
            ajax_action(action, value);
        },
        onCancel: function() {
            // Handle cancelation if necessary
        }
    });
    $(event.target).confirmation('show');
}

function ajax_settings(setting, value = '') {

    var params = {
        action: 'settings_user',
        setting: setting,
        value: value
    };

    $.ajax({
        type: "POST",
        url: "ajax/actions.php",
        async: true,
        cache: false,
        data: jQuery.param(params),
        success: function (response) {
            if (response.status === 'ok') {
                location.reload(true);
                //console.log(response);
            } else {
                console.log(response);
            }
        }
    });

    event.preventDefault();

    return false;
}

function processAjaxForm(event) {

    let id = event.target.id;
    let div = $('div#message-' + id);
    let serialize = {checkboxUncheckedValue: "0"};
    let data = JSON.stringify($('#' + id).serializeJSON(serialize));
    //console.log(data);

    let messageClass = 'danger';
    let messageTimeout = 60000; // 60s
    let html = '<div id="message-' + id + '" class="alert">' +
        '  <button type="button" class="close" data-dismiss="alert">×</button>' +
        '  <span></span>' +
        '</div>';
    var dom = $(html);
    //console.log(dom);
    // Remove previous message if too fast save request...
    div.remove();

    $.ajax({
        url: 'ajax/actions.php',
        dataType: 'json',
        type: 'POST',
        contentType: 'application/json',
        async: true,
        cache: false,
        data: data,
        success: function (json) {
            if (json.status === 'ok') {
                messageClass = 'success';
                messageTimeout = 10000; // 10s
                //location.reload();
                //console.log(json);
            } else if (json.status === 'warning') {
                messageClass = 'warning';
                messageTimeout = 10000; // 10s
            }
            if (json.message) {
                if (json.class) {
                    messageClass = json.class;
                }
                //var form_selector = $('form#' + id);
                // use top level div (from form generator)
                var form_selector = $('div#box-' + id);
                if ($("div#ajax-form-message").length) {
                    // check if custom message placeholder exist
                    form_selector = $("div#ajax-form-message");
                } else if (form_selector.length === 0) {
                    // custom forms (without generator)
                    form_selector = $('form#' + id);
                }
                //console.log(form_selector);
                form_selector.after(dom.addClass('alert-' + messageClass).children("span").text(json.message).end());
                delay_remove(div, messageTimeout);
            }

            if (json.reload) {
                reload_timeout(messageTimeout);
            }
            // DEBUG
            // if (json.update_array) {
            //     console.log(json.update_array);
            // }
        },
        error: function (json) {
            //console.log(json);
        }
    });

    event.preventDefault();
    return false;
}

function delay_remove(element, timeout = 60000) {
    element.delay(timeout).fadeOut("normal", function () {
        $(this).remove();
    });
}

function delay_empty(element, timeout = 60000) {
    element.delay(timeout).fadeOut("normal", function () {
        $(this).empty().removeAttr("style");
    });
}

function reload_timeout(timeout, redirect = window.location.href) {
    setTimeout(function () {
        // Clean back button history for prevent resubmit the post
        if (window.history.replaceState) {
            window.history.replaceState(null, null, redirect);
        }
        // reload without resubmit data warning
        window.location = redirect;
        //location.reload(1);
    }, timeout);
}

delete_ap = function (id) {

    var params = {
        action: 'delete_ap',
        id: id
    };

    // Run AJAX query and update div HTML with response.
    $.ajax({
        type: "POST",
        url: "ajax/actions.php",
        data: jQuery.param(params),
        cache: false,
        success: function (response) {
            if (response.status === 'ok') {
                console.log(response);
            } else {
                console.log(response);
            }
        }
    });

    return false;

};

$(document).on('blur', 'input[name^="widget-config-"]', function (event) {
    event.preventDefault();
    var $this = $(this);
    var field = $this.data("field");
    var id = $this.data("id");
    // console.log($this);
    if ($this.data("type") === "checkbox") {
        var value = $this.is(":checked") ? "yes" : "no";
    } else {
        var value = $this.val();
    }
    if ($this[0].checkValidity()) {
        $.ajax({
            type: 'POST',
            url: 'ajax/actions.php',
            data: {action: "update_widget_config", widget_id: id, config_field: field, config_value: value},
            dataType: "json",
            success: function (data) {
                if (data.status == 'ok') {
                } else {
                }
            },
            error: function (data) {
                console.log(data);
            }
        });
    }
});

// Run confirmation popovers if the function is loaded.
window.onload = function () {
    if (typeof $().confirmation === 'function') {
        $("[data-toggle='confirm']").confirmation(
            {
                rootSelector: '[data-toggle=confirm]',
                //btnOkIcon: 'icon-ok',
                //btnOkClass: 'btn btn-sm btn-primary',
                //btnCancelIcon: 'icon-remove',
                //btnCancelClass: 'btn btn-sm btn-danger',
            }
        );
    }
}