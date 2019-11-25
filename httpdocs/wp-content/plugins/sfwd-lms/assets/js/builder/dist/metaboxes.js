// modules are defined as an array
// [ module function, map of requires ]
//
// map of requires is short require name -> numeric require
//
// anything defined in a previous bundle is accessed via the
// orig method which is the require for previous bundles
parcelRequire = (function (modules, cache, entry, globalName) {
  // Save the require from previous bundle to this closure if any
  var previousRequire = typeof parcelRequire === 'function' && parcelRequire;
  var nodeRequire = typeof require === 'function' && require;

  function newRequire(name, jumped) {
    if (!cache[name]) {
      if (!modules[name]) {
        // if we cannot find the module within our internal map or
        // cache jump to the current global require ie. the last bundle
        // that was added to the page.
        var currentRequire = typeof parcelRequire === 'function' && parcelRequire;
        if (!jumped && currentRequire) {
          return currentRequire(name, true);
        }

        // If there are other bundles on this page the require from the
        // previous one is saved to 'previousRequire'. Repeat this as
        // many times as there are bundles until the module is found or
        // we exhaust the require chain.
        if (previousRequire) {
          return previousRequire(name, true);
        }

        // Try the node require function if it exists.
        if (nodeRequire && typeof name === 'string') {
          return nodeRequire(name);
        }

        var err = new Error('Cannot find module \'' + name + '\'');
        err.code = 'MODULE_NOT_FOUND';
        throw err;
      }

      localRequire.resolve = resolve;
      localRequire.cache = {};

      var module = cache[name] = new newRequire.Module(name);

      modules[name][0].call(module.exports, localRequire, module, module.exports, this);
    }

    return cache[name].exports;

    function localRequire(x){
      return newRequire(localRequire.resolve(x));
    }

    function resolve(x){
      return modules[name][1][x] || x;
    }
  }

  function Module(moduleName) {
    this.id = moduleName;
    this.bundle = newRequire;
    this.exports = {};
  }

  newRequire.isParcelRequire = true;
  newRequire.Module = Module;
  newRequire.modules = modules;
  newRequire.cache = cache;
  newRequire.parent = previousRequire;
  newRequire.register = function (id, exports) {
    modules[id] = [function (require, module) {
      module.exports = exports;
    }, {}];
  };

  var error;
  for (var i = 0; i < entry.length; i++) {
    try {
      newRequire(entry[i]);
    } catch (e) {
      // Save first error but execute all entries
      if (!error) {
        error = e;
      }
    }
  }

  if (entry.length) {
    // Expose entry point to Node, AMD or browser globals
    // Based on https://github.com/ForbesLindesay/umd/blob/master/template.js
    var mainExports = newRequire(entry[entry.length - 1]);

    // CommonJS
    if (typeof exports === "object" && typeof module !== "undefined") {
      module.exports = mainExports;

    // RequireJS
    } else if (typeof define === "function" && define.amd) {
     define(function () {
       return mainExports;
     });

    // <script>
    } else if (globalName) {
      this[globalName] = mainExports;
    }
  }

  // Override the current require with this new one
  parcelRequire = newRequire;

  if (error) {
    // throw error from earlier, _after updating parcelRequire_
    throw error;
  }

  return newRequire;
})({"6cPf":[function(require,module,exports) {
if ('block' === window.learndash_builder_metaboxes.editor) {
  // If Gutenberg is used, make sure some metaboxes can't be toggled off
  wp.data.subscribe(function () {
    // "Always On" panels.
    var alwaysOn = ['meta-box-learndash-course-access-settings', 'meta-box-learndash-course-display-content-settings', 'meta-box-learndash-course-navigation-settings', 'meta-box-learndash_course_builder', 'meta-box-learndash_course_groups', 'meta-box-learndash_quiz_builder', 'meta-box-sfwd-course-lessons', 'meta-box-sfwd-course-quizzes', 'meta-box-sfwd-course-topics', 'meta-box-sfwd-quiz']; // WordPress Data Store information.

    var store = wp.data.select('core/edit-post');
    var panels = store.getPreference('panels'); // Loop over the panels object, but only those panels listed as "Always ON".

    for (var id in panels) {
      if (panels.hasOwnProperty(id) && alwaysOn.includes(id)) {
        var panel = panels[id]; // Only perform the actions with panels with enabled property.

        if (panel.hasOwnProperty('enabled')) {
          if (!panel.enabled) {
            wp.data.dispatch('core/edit-post').toggleEditorPanelEnabled(id);
          }
        }
      }
    }
  });
} else {
  // Metaboxes IDs
  var alwaysOn = ['learndash-course-access-settings', 'learndash-course-display-content-settings', 'learndash-course-navigation-settings', 'learndash_course_builder', 'learndash_course_groups', 'learndash_quiz_builder', 'sfwd-course-lessons', 'sfwd-course-quizzes', 'sfwd-course-topics', 'sfwd-quiz']; // We need to follow the core postbox.js to bind the events

  jQuery('.hide-postbox-tog').bind('click.postboxes', function (e) {
    var $el = jQuery(this),
        boxId = $el.val(),
        $postbox = jQuery('#' + boxId); // Check if the metabox is in "always on"

    if (-1 < alwaysOn.indexOf(boxId)) {
      if (!$el.prop('checked')) {
        // Prevent unchecking and force visibility
        e.preventDefault();
        $postbox.show();
        $el.prop('checked', true);
      }
    }
  });
}
},{}]},{},["6cPf"], null)