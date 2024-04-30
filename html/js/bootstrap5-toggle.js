/* Copyright Notice
 * bootstrap5-toggle v3.7.4
 * https://palcarazm.github.io/bootstrap5-toggle/
 * @author 2011-2014 Min Hur (https://github.com/minhur)
 * @author 2018-2019 Brent Ely (https://github.com/gitbrent)
 * @author 2022 Pablo Alcaraz Martínez (https://github.com/palcarazm)
 * @funding GitHub Sponsors
 * @see https://github.com/sponsors/palcarazm
 * @license MIT
 * @see https://github.com/palcarazm/bootstrap5-toggle/blob/master/LICENSE
 */


+(function ($) {
  "use strict";

  // TOGGLE PUBLIC CLASS DEFINITION
  // ==============================

  let Toggle = function (element, options) {
    // A: Capture ref to HMTL element
    this.$element = $(element);
    // B: Set options
    this.options = $.extend({}, this.defaults(), options);
    // LAST: Render Toggle
    this.render();
  };

  Toggle.DEFAULTS = {
    on: "On",
    off: "Off",
    onstyle: "primary",
    offstyle: "default",
    onvalue: null,
    offvalue: null,
    size: "normal",
    style: "",
    width: null,
    height: null,
    tabindex: 0,
    tristate: false,
    name: null,
  };

  Toggle.prototype.defaults = function () {
    return {
      on: this.$element.attr("data-on") || Toggle.DEFAULTS.on,
      off: this.$element.attr("data-off") || Toggle.DEFAULTS.off,
      onstyle: this.$element.attr("data-onstyle") || Toggle.DEFAULTS.onstyle,
      offstyle: this.$element.attr("data-offstyle") || Toggle.DEFAULTS.offstyle,
      onvalue:
        this.$element.attr("value") ||
        this.$element.attr("data-onvalue") ||
        Toggle.DEFAULTS.onvalue,
      offvalue: this.$element.attr("data-offvalue") || Toggle.DEFAULTS.offvalue,
      size: this.$element.attr("data-size") || Toggle.DEFAULTS.size,
      style: this.$element.attr("data-style") || Toggle.DEFAULTS.style,
      width: this.$element.attr("data-width") || Toggle.DEFAULTS.width,
      height: this.$element.attr("data-height") || Toggle.DEFAULTS.height,
      tabindex: this.$element.attr("tabindex") || Toggle.DEFAULTS.tabindex,
      tristate: this.$element.is("[tristate]") || Toggle.DEFAULTS.tristate,
      name: this.$element.attr("name") || Toggle.DEFAULTS.name,
    };
  };

  Toggle.prototype.render = function () {
    // 0: Parse size
    let size;
    switch (this.options.size) {
      case "large":
      case "lg":
        size = "btn-lg";
        break;
      case "small":
      case "sm":
        size = "btn-sm";
        break;
      case "mini":
      case "xs":
        size = "btn-xs";
        break;
      default:
        size = "";
        break;
    }

    // 1: On
    let $toggleOn = $('<label class="btn">')
      .html(this.options.on)
      .addClass("btn-" + this.options.onstyle + " " + size);
    if (this.$element.prop("id")) {
      $toggleOn.prop("for", this.$element.prop("id"));
    }

    // 2: Off
    let $toggleOff = $('<label class="btn">')
      .html(this.options.off)
      .addClass("btn-" + this.options.offstyle + " " + size);
    if (this.$element.prop("id")) {
      $toggleOff.prop("for", this.$element.prop("id"));
    }

    // 3: Handle
    let $toggleHandle = $('<span class="toggle-handle btn">').addClass(size);

    // 4: Toggle Group
    let $toggleGroup = $('<div class="toggle-group">').append(
      $toggleOn,
      $toggleOff,
      $toggleHandle
    );

    // 5: Toggle
    let $toggle = $(
      '<div class="toggle btn" data-toggle="toggle" role="button">'
    )
      .addClass(
        this.$element.prop("checked")
          ? "btn-" + this.options.onstyle
          : "btn-" + this.options.offstyle + " off"
      )
      .addClass(size)
      .addClass(this.options.style)
      .attr('title', this.$element.prop('title'))
      .attr("tabindex", this.options.tabindex);
    if (this.$element.prop("disabled") || this.$element.prop("readonly")) {
      $toggle.addClass("disabled");
      $toggle.attr("disabled", "disabled");
    }

    // 6: Set form values
    if (this.options.onvalue) this.$element.val(this.options.onvalue);
    let $invElement = null;
    if (this.options.offvalue) {
      $invElement = this.$element.clone();
      $invElement.val(this.options.offvalue);
      $invElement.attr("data-toggle", "invert-toggle");
      $invElement.removeAttr("id");
      $invElement.prop("checked", !this.$element.prop("checked"));
    }

    // 7: Replace HTML checkbox with Toggle-Button
    this.$element.wrap($toggle);
    $.extend(this, {
      $toggle: this.$element.parent(),
      $toggleOn: $toggleOn,
      $toggleOff: $toggleOff,
      $toggleGroup: $toggleGroup,
      $invElement: $invElement,
    });
    this.$toggle.append($invElement, $toggleGroup);

    // 8: Set button W/H, lineHeight
    {
      // -: Get sizes with hidden elemnts
      let sizeOn = getOuterSize($toggleOn);
      let sizeOff = getOuterSize($toggleOff);
      let sizeOHandle = getOuterSize($toggleHandle);

      // A: Set style W/H
      let width = this.options.width || Math.max(sizeOn.width, sizeOff.width) + sizeOHandle.width / 2;
      let height = this.options.height || Math.max(sizeOn.height, sizeOff.height);
      //console.log('on: ' + $toggleOn.outerWidth() + ', off: ' + $toggleOff.outerWidth() + ', toggle: ' + $toggleHandle.outerWidth());
      //console.log('on: ' + getOuterSize($toggleOn).width + ', off: ' + getOuterSize($toggleOff).width + ', toggle: ' + getOuterSize($toggleHandle).width);
      //console.log('width: ' + width + ', height: ' + height);
      this.$toggle.css({ width: width, height: height });

      // B: Apply on/off class
      $toggleOn.addClass("toggle-on");
      $toggleOff.addClass("toggle-off");

      // C: Finally, set lineHeight if needed
      if (this.options.height) {
        $toggleOn.css("line-height", $toggleOn.height() + "px");
        $toggleOff.css("line-height", $toggleOff.height() + "px");
      }
    }

    // 9: Add listeners
    this.$toggle.on("touchstart", (e) => {
      toggleActionPerformed(e, this);
    });
    this.$toggle.on("click", (e) => {
      toggleActionPerformed(e, this);
    });
    this.$toggle.on("keypress", (e) => {
      if (e.key == " ") {
        toggleActionPerformed(e, this);
      }
    });
    // 10: Set elements to bootstrap object (NOT NEEDED)
    // 11: Keep reference to this instance for subsequent calls via `getElementById().bootstrapToggle()` (NOT NEEDED)
  };

  /**
   * Trigger actions
   * @param {Event} e event
   * @param {Toggle} target Toggle
   */
  function toggleActionPerformed(e, target) {
    if (target.options.tristate) {
      if (target.$toggle.hasClass("indeterminate")) {
        target.determinate(true);
        target.toggle();
      } else {
        target.indeterminate();
      }
    } else {
      target.toggle();
    }
    e.preventDefault();
  }

  function getOuterSize(obj) {
    if ($(obj).length === 0) {
      return false;
    }

    let clone = obj.clone();
    clone.css({
      visibility: 'hidden',
      width: '',
      height: '',
      maxWidth: '',
      maxHeight: ''
    });
    $('body').append(clone);
    let width = clone.outerWidth();
    let height = clone.outerHeight();
    clone.remove();
    return {width: width, height: height};
  }

  Toggle.prototype.toggle = function (silent = false) {
    if (this.$element.prop("checked")) this.off(silent);
    else this.on(silent);
  };

  Toggle.prototype.on = function (silent = false) {
    if (this.$element.prop("disabled") || this.$element.prop("readonly"))
      return false;
    this.$toggle
      .removeClass("btn-" + this.options.offstyle + " off")
      .addClass("btn-" + this.options.onstyle);
    this.$element.prop("checked", true);
    if (this.$invElement) this.$invElement.prop("checked", false);
    if (!silent) this.trigger();
  };

  Toggle.prototype.off = function (silent = false) {
    if (this.$element.prop("disabled") || this.$element.prop("readonly"))
      return false;
    this.$toggle
      .removeClass("btn-" + this.options.onstyle)
      .addClass("btn-" + this.options.offstyle + " off");
    this.$element.prop("checked", false);
    if (this.$invElement) this.$invElement.prop("checked", true);
    if (!silent) this.trigger();
  };

  Toggle.prototype.indeterminate = function (silent = false) {
    if (
      !this.options.tristate ||
      this.$element.prop("disabled") ||
      this.$element.prop("readonly")
    )
      return false;
    this.$toggle.addClass("indeterminate");
    this.$element.prop("indeterminate", true);
    this.$element.removeAttr("name");
    if (this.$invElement) this.$invElement.prop("indeterminate", true);
    if (this.$invElement) this.$invElement.removeAttr("name");
    if (!silent) this.trigger();
  };

  Toggle.prototype.determinate = function (silent = false) {
    if (
      !this.options.tristate ||
      this.$element.prop("disabled") ||
      this.$element.prop("readonly")
    )
      return false;
    this.$toggle.removeClass("indeterminate");
    this.$element.prop("indeterminate", false);
    if (this.options.name) this.$element.attr("name", this.options.name);
    if (this.$invElement) this.$invElement.prop("indeterminate", false);
    if (this.$invElement && this.options.name)
      this.$invElement.attr("name", this.options.name);
    if (!silent) this.trigger();
  };

  Toggle.prototype.enable = function () {
    this.$toggle.removeClass("disabled");
    this.$toggle.removeAttr("disabled");
    this.$element.prop("disabled", false);
    this.$element.prop("readonly", false);
    if (this.$invElement) {
      this.$invElement.prop("disabled", false);
      this.$invElement.prop("readonly", false);
    }
  };

  Toggle.prototype.disable = function () {
    this.$toggle.addClass("disabled");
    this.$toggle.attr("disabled", "disabled");
    this.$element.prop("disabled", true);
    this.$element.prop("readonly", false);
    if (this.$invElement) {
      this.$invElement.prop("disabled", true);
      this.$invElement.prop("readonly", false);
    }
  };

  Toggle.prototype.readonly = function () {
    this.$toggle.addClass("disabled");
    this.$toggle.attr("disabled", "disabled");
    this.$element.prop("disabled", false);
    this.$element.prop("readonly", true);
    if (this.$invElement) {
      this.$invElement.prop("disabled", false);
      this.$invElement.prop("readonly", true);
    }
  };

  Toggle.prototype.update = function (silent) {
    if (this.$element.prop("disabled")) this.disable();
    else if (this.$element.prop("readonly")) this.readonly();
    else this.enable();
    if (this.$element.prop("checked")) this.on(silent);
    else this.off(silent);
  };

  Toggle.prototype.trigger = function (silent) {
    this.$element.off("change.bs.toggle");
    if (!silent) this.$element.change();
    this.$element.on(
      "change.bs.toggle",
      $.proxy(function () {
        this.update();
      }, this)
    );
  };

  Toggle.prototype.destroy = function () {
    // A: Remove button-group from UI, replace checkbox element
    this.$element.off("change.bs.toggle");
    this.$toggleGroup.remove();
    if (this.$invElement) this.$invElement.remove();

    // B: Delete internal refs
    this.$element.removeData("bs.toggle");
    this.$element.unwrap();
  };

  // TOGGLE PLUGIN DEFINITION
  // ========================

  function Plugin(option) {
    let optArg = Array.prototype.slice.call(arguments, 1)[0];

    return this.each(function () {
      let $this = $(this);
      let data = $this.data("bs.toggle");
      let options = typeof option == "object" && option;

      if (!data) {
        data = new Toggle(this, options);
        $this.data("bs.toggle", data);
      }
      if (
        typeof option === "string" &&
        data[option] &&
        typeof optArg === "boolean"
      )
        data[option](optArg);
      else if (typeof option === "string" && data[option]) data[option]();
      //else if (option && !data[option]) console.log('bootstrap-toggle: error: method `'+ option +'` does not exist!');
    });
  }

  let old = $.fn.bootstrapToggle;

  $.fn.bootstrapToggle = Plugin;
  $.fn.bootstrapToggle.Constructor = Toggle;

  // TOGGLE NO CONFLICT
  // ==================

  $.fn.toggle.noConflict = function () {
    $.fn.bootstrapToggle = old;
    return this;
  };

  /**
   * Replace all `input[type=checkbox][data-toggle="toggle"]` inputs with "Bootstrap-Toggle"
   * Executes once page elements have rendered enabling script to be placed in `<head>`
   */
  $(function () {
    $('input[type=checkbox][data-toggle^=toggle]:not(\'.tiny-toggle\')').bootstrapToggle()
  });
})(jQuery);
