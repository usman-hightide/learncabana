/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./src/voc.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./src/voc.js":
/*!********************!*\
  !*** ./src/voc.js ***!
  \********************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

const visibleOnCompletionControls = wp.compose.createHigherOrderComponent(BlockEdit => {
  return props => {
    const {
      Fragment
    } = wp.element;
    const {
      ToggleControl,
      PanelBody,
      PanelRow,
      SelectControl
    } = wp.components;
    const {
      InspectorAdvancedControls
    } = wp.blockEditor;
    const {
      attributes,
      setAttributes,
      isSelected
    } = props;
    var el = wp.element.createElement;
    var classes = typeof attributes.className == "undefined" ? [] : attributes.className.split(" ");
    var visibleOnCompletionEnabled = classes.indexOf("gb_voc") >= 0;

    function gb_voc_toggle(val) {
      var c = typeof props.attributes.className == "string" ? props.attributes.className.split(" ").filter(function (v) {
        return v != "";
      }) : [];

      if (val && c.indexOf("gb_voc") == -1) {
        c.push("gb_voc");
        setAttributes({
          className: c.join(" ")
        });
      }

      if (!val && c.indexOf("gb_voc") >= 0) {
        c = c.filter(function (v) {
          return v != "gb_voc";
        });
        setAttributes({
          className: c.join(" ")
        });
      }
    }

    function get_xapi_contents_on_page() {
      var xapi_blocks = wp.data.select('core/block-editor').getBlocks().filter(function (block) {
        return block.name == "grassblade/xapi-content";
      });
      var xapi_content_ids = [];
      var xapi_contents = [];

      for (var i = xapi_blocks.length - 1; i >= 0; i--) {
        if (typeof xapi_blocks[i].attributes.content_id != "undefined" && !isNaN(xapi_blocks[i].attributes.content_id) && props.clientId != xapi_blocks[i].clientId) xapi_content_ids[xapi_blocks[i].attributes.content_id] = xapi_blocks[i].attributes.content_id;
      }

      if (jQuery("#show_xapi_content").val() * 1 > 0) {
        var metabox_xapi_content_id = jQuery("#show_xapi_content").val() * 1;
        xapi_content_ids[metabox_xapi_content_id] = metabox_xapi_content_id;
      }

      gb_block_data.post_content.forEach(function (post) {
        if (typeof xapi_content_ids[post.id] != "undefined" && post.completion_tracking == true) {
          var item = {
            value: post.id,
            label: post.post_title
          };
          xapi_contents.push(item);
        }
      });
      return xapi_contents;
    }

    var xapi_contents = get_xapi_contents_on_page();
    var featureDisabled = xapi_contents.length == 0;
    xapi_contents.unshift({
      value: "",
      label: "Select Content"
    });
    xapi_contents.push({
      value: "all",
      label: "All Content"
    });

    function gb_voc_selected_content() {
      var c = typeof props.attributes.className == "string" ? props.attributes.className.split(" ").filter(function (v) {
        if (v == "gb_voc_all") return true;
        var check = v.match(/^gb_voc_(\d*)/);
        if (check == null) return false;
        return v != "";
      }) : [];
      return typeof c[0] == "string" ? c[0].replace("gb_voc_", "") : "";
    }

    function gb_voc_content_selected(content_id) {
      var c = typeof props.attributes.className == "string" ? props.attributes.className.split(" ").filter(function (v) {
        var check = v.match(/^gb_voc_(\d*)/);
        if (v == "gb_voc" || check != null) return false;
        return v != "";
      }) : [];

      if (content_id * 1 > 0 || content_id == "all") {
        c.push("gb_voc");
        c.push("gb_voc_" + content_id);
      }

      setAttributes({
        className: c.join(" ")
      });
    }

    var selectedOption = gb_voc_selected_content();

    if (!featureDisabled && visibleOnCompletionEnabled && selectedOption) {
      jQuery("#block-" + props.clientId).addClass("gb_voc");
      var msg_content_part = "";
      var no_content_selected = false;
      if (selectedOption == "all") msg_content_part = "All xAPI Contents on page are ";else {
        var selected_content = gb_block_data.post_content.filter(function (content) {
          return content.id == selectedOption;
        })[0];

        if (typeof selected_content != "object" || typeof selected_content.post_title != "string") {
          jQuery("#block-" + props.clientId + "-gb_voc_css").empty();
          no_content_selected = true;
        } else {
          msg_content_part = selected_content.post_title + " is ";
        }
      }

      if (!no_content_selected) {
        var css_msg = "Visible when " + msg_content_part + " completed.";
        var css = "div#block-" + props.clientId + ':hover:before {content:"' + css_msg + '";}';
        if (jQuery("#block-" + props.clientId + "-gb_voc_css").length > 0) jQuery("#block-" + props.clientId + "-gb_voc_css").html(css);else jQuery("body").append("<style id='block-" + props.clientId + "-gb_voc_css'>" + css + "</style>");
      }
    } else {
      jQuery("#block-" + props.clientId).removeClass("gb_voc");
      jQuery("#block-" + props.clientId + "-gb_voc_css").empty();
    }

    return Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(Fragment, null, Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(BlockEdit, props), isSelected && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(InspectorAdvancedControls, null, !featureDisabled && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(ToggleControl, {
      label: wp.i18n.__('Visibile on Completion', 'grassblade'),
      checked: visibleOnCompletionEnabled,
      onChange: newval => gb_voc_toggle(newval)
    }), !featureDisabled && visibleOnCompletionEnabled && Object(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__["createElement"])(SelectControl, {
      name: "xapi_content",
      value: selectedOption,
      onChange: val => gb_voc_content_selected(val),
      options: xapi_contents
    })));
  };
}, 'coverAdvancedControls');
wp.hooks.addFilter('editor.BlockEdit', 'grassblade/visible-on-completion', visibleOnCompletionControls);

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["element"]; }());

/***/ })

/******/ });
//# sourceMappingURL=voc.js.map