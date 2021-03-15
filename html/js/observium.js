/*
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage js
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

// Workaround jQuery XSS
jQuery.htmlPrefilter = function( html ) {
	return html;
};

function url_from_form(form_id) {
    var url = document.getElementById(form_id).action;
    var partFields = document.getElementById(form_id).elements;

    var seen = {};
    for (var el, i = 0, n = partFields.length; i < n; i++) {
        el = partFields[i];
        if (el.value != '' && el.name != '') {
            var val;
            if (el.multiple) {
                var multi = [];
                for (var part, ii = 0, nn = el.length; ii < nn; ii++) {
                    part = el[ii];
                    if (part.selected) {
                        val = part.value.replace(/\//g, '%7F'); // %7F (DEL, delete) - not defined in HTML 4 standard
                        val = val.replace(/,/g, '%1F');         // %1F (US, unit separator) - not defined in HTML 4 standard
                        val = encodeURIComponent(val);
                        // add quotes for empty or multiword strings
                        if ((val !== part.value || val === '') && !is_base64(part.value)) {
                            //console.log(part.value + " != " + val + " : " + atob(val));
                            val = '"' + val + '"'
                        }
                        multi.push(val);
                        //console.log(part.value);
                    }
                }
                el.name = el.name.replace('[]', '');
                if (multi.length && seen[el.name] !== 1) {
                    seen[el.name] = 1;
                    url = url.replace(new RegExp(encodeURIComponent(el.name) + '=[^\/]+\/', 'g'), ''); // remove old var from url
                    url += encodeURIComponent(el.name) + '=' + multi.join(',') + '/';
                }
            } else if (el.checked || el.type !== "checkbox") {
                val = el.value.replace(/\//g, '%7F'); // %7F (DEL, delete) - not defined in HTML 4 standard
                val = val.replace(/,/g, '%1F');       // %1F (US, unit separator) - not defined in HTML 4 standard
                if (seen[el.name] !== 1) {            // Skip duplicate vars
                    seen[el.name] = 1;
                    url = url.replace(new RegExp(encodeURIComponent(el.name) + '=[^\/]+\/', 'g'), ''); // remove old var from url
                    url += encodeURIComponent(el.name) + '=' + encodeURIComponent(val) + '/';
                }
            }
        }
    }

    return url;
}

function form_to_path(form_id) {
    url = url_from_form(form_id);
    window.location.href = url;
}

function is_base64(value) {
    try {
        // Detect if value is base64 encoded string
        atob(value);
        return true;
    } catch(e) {
        // if you want to be specific and only catch the error which means
        // the base 64 was invalid, then check for 'e.code === 5'.
        // (because 'DOMException.INVALID_CHARACTER_ERR === 5')

        return false;
    }
}

function submitURL(form_id) {
    url = url_from_form(form_id);
    $(document.getElementById(form_id)).attr('action', url);
}

// toggle attributes readonly,disabled by form id
function toggleAttrib(attrib, form_id) {
    //console.log('attrib: '+attrib+', id: '+form_id);
    //console.log(Array.isArray(form_id));
    if (Array.isArray(form_id))
    {
        form_id.forEach(function(entry) {
            toggleAttrib(attrib, entry);
            //console.log(entry);
        });
        return;
    }
    var toggle = document.getElementById(form_id); // js object
    var element = $('#' + form_id);                // jQuery object
    //console.log('prop: '+element.prop(attrib));
    //console.log('attr: '+element.attr(attrib));
    //console.log('Attribute: '+toggle.getAttribute(attrib));
    //var set   = !toggle.getAttribute(attrib);
    var set = !(toggle.getAttribute(attrib) || element.prop(attrib));
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
        }
        else if (toggle.hasAttribute('data-toggle') && toggle.getAttribute('data-toggle') === 'tagsinput') {
            // bootstrap tagsinput
            element.tagsinput('refresh'); // re-render tagsinput
            //console.log('bootstrap-tagsinput');
        }
    } else if (toggle.hasAttribute('data-toggle') && toggle.getAttribute('data-toggle').substr(0, 6) === 'switch') {
        // bootstrap switch
        element.bootstrapSwitch("toggle" + attrib.charAt(0).toUpperCase() + attrib.slice(1));
        //console.log('bootstrap-switch');
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
        set ? $('#' + form_id + '_div').datetimepicker('disable') : $('#' + form_id + '_div').datetimepicker('enable');
        //console.log('bootstrap-datetime');
        //console.log($('#'+form_id+'_div'));
    } else if (element.prop('type') === 'submit') {
        // submit buttons
        //if (attrib == 'disabled') {
        set ? element.addClass('disabled') : element.removeClass('disabled');
        //}
    }
    //console.log(toggle);
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

function entity_popup(element)
{

    //console.log(element);

    $(element).qtip({

        content: {
            //text: '<img class="" style"margin: 0 auto;" src="images/loading.gif" alt="Loading..." />',
            text: '<big><i class="icon-spinner icon-spin text-center vertical-align" style="width: 100%;"></i></big>',
            ajax: {
                url: 'ajax/entity_popup.php',
                type: 'POST',
                loading: false,
                data: {entity_type: $(element).data('etype'), entity_id: $(element).data('eid')},
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
            //target: false, // Defaults to target element
            //event: 'click mouseleave', // Hide on mouse out by default
            //effect: true,       // true - Use default 90ms fade effect
            //delay: 0, // No hide delay by default
            fixed: true, // Non-hoverable by default
            //inactive: false, // Do not hide when inactive
            //leave: 'window', // Hide when we leave the window
            //distance: false // Don't hide after a set distance
        }
    });

}


function entity_popups() {

    $('.entity-popup').each(function () {

        entity_popup(this);

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
    if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
    }
}

// jQuery specific observium additions
jQuery(document).ready(function () {
    // Enable bootstrap-switch by default for data-toggle="switch" attribute
    // See options here: http://www.bootstrap-switch.org/documentation-3.html
    $('[data-toggle="switch"]').bootstrapSwitch();

    // Preconfigured switch-mini
    $('[data-toggle="switch-mini"]').bootstrapSwitch({
        size: 'mini',
        onText: 'Yes',
        offText: 'No'
    });

    // Preconfigured switch-revert
    $('[data-toggle="switch-revert"]').bootstrapSwitch({
        size: 'mini',
        onText: 'No',
        onColor: 'danger',
        offText: 'Yes',
        offColor: 'primary'
    });

    // Preconfigured switch-bool
    $('[data-toggle="switch-bool"]').bootstrapSwitch({
        size: 'mini',
        onText: 'True',
        onColor: 'primary',
        offText: 'False',
        offColor: 'danger'
    });

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
        ;
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
function ajax_action (action, value = '')
{

  var params = {
     action: action,
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

};


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

$(document).on('change', 'input[name="widget-config-input"]', function(event) {

    $(this).style = 'background-color: pink;';

});

    $(document).on('blur', 'input[name="widget-config-input"]', function(event) {
        event.preventDefault();
        var $this = $(this);
        var field = $this.data("field");
        var id    = $this.data("id");
        var value = $this.val();
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
