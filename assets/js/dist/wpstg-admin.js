(function () {
  'use strict';

  function _extends() {
    _extends = Object.assign || function (target) {
      for (var i = 1; i < arguments.length; i++) {
        var source = arguments[i];

        for (var key in source) {
          if (Object.prototype.hasOwnProperty.call(source, key)) {
            target[key] = source[key];
          }
        }
      }

      return target;
    };

    return _extends.apply(this, arguments);
  }

  /**
   * WP STAGING basic jQuery replacement
   */

  /**
   * Shortcut for document.querySelector() or jQuery's $()
   * Return single element only
   */
  function qs(selector) {
    return document.querySelector(selector);
  }
  /**
   * Shortcut for document.querySelector() or jQuery's $()
   * Return collection of elements
   */

  function all(selector) {
    return document.querySelectorAll(selector);
  }
  /**
   * alternative of jQuery - $(parent).on(event, selector, handler)
   */

  function addEvent(parent, evt, selector, handler) {
    parent.addEventListener(evt, function (event) {
      if (event.target.matches(selector + ', ' + selector + ' *')) {
        handler(event.target.closest(selector), event);
      }
    }, false);
  }
  function slideDown(element, duration) {
    if (duration === void 0) {
      duration = 400;
    }

    element.style.display = 'block';
    element.style.overflow = 'hidden';
    var height = element.offsetHeight;
    element.style.height = '0px';
    element.style.transitionProperty = 'height';
    element.style.transitionDuration = duration + 'ms';
    setTimeout(function () {
      element.style.height = height + 'px';
      window.setTimeout(function () {
        element.style.removeProperty('height');
        element.style.removeProperty('overflow');
        element.style.removeProperty('transition-duration');
        element.style.removeProperty('transition-property');
      }, duration);
    }, 0);
  }
  function slideUp(element, duration) {
    if (duration === void 0) {
      duration = 400;
    }

    element.style.display = 'block';
    element.style.overflow = 'hidden';
    var height = element.offsetHeight;
    element.style.height = height + 'px';
    element.style.transitionProperty = 'height';
    element.style.transitionDuration = duration + 'ms';
    setTimeout(function () {
      element.style.height = '0px';
      window.setTimeout(function () {
        element.style.display = 'none';
        element.style.removeProperty('height');
        element.style.removeProperty('overflow');
        element.style.removeProperty('transition-duration');
        element.style.removeProperty('transition-property');
      }, duration);
    }, 0);
  }
  function getNextSibling(element, selector) {
    var sibling = element.nextElementSibling;

    while (sibling) {
      if (sibling.matches(selector)) {
        return sibling;
      }

      sibling = sibling.nextElementSibling;
    }
  }
  function getParents(element, selector) {
    var result = [];

    for (var parent = element && element.parentElement; parent; parent = parent.parentElement) {
      if (parent.matches(selector)) {
        result.push(parent);
      }
    }

    return result;
  }

  /**
   * Enable/Disable cloning for staging site
   */

  var WpstgCloneStaging = /*#__PURE__*/function () {
    function WpstgCloneStaging(pageWrapperId, wpstgObject) {
      if (pageWrapperId === void 0) {
        pageWrapperId = '#wpstg-clonepage-wrapper';
      }

      if (wpstgObject === void 0) {
        wpstgObject = wpstg;
      }

      this.pageWrapper = qs(pageWrapperId);
      this.wpstgObject = wpstgObject;
      this.enableButtonId = '#wpstg-enable-staging-cloning';
      this.enableAction = 'wpstg_enable_staging_cloning';
      this.notyf = new Notyf({
        duration: 10000,
        position: {
          x: 'center',
          y: 'bottom'
        },
        dismissible: true,
        types: [{
          type: 'warning',
          background: 'orange',
          icon: false
        }]
      });
      this.init();
    }

    var _proto = WpstgCloneStaging.prototype;

    _proto.addEvents = function addEvents() {
      var _this = this;

      if (this.pageWrapper === null) {
        return;
      }

      addEvent(this.pageWrapper, 'click', this.enableButtonId, function () {
        _this.sendRequest(_this.enableAction);
      });
    };

    _proto.init = function init() {
      this.addEvents();
    };

    _proto.sendRequest = function sendRequest(action) {
      var _this2 = this;

      fetch(this.wpstgObject.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new URLSearchParams({
          action: action,
          accessToken: this.wpstgObject.accessToken,
          nonce: this.wpstgObject.nonce
        }),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      }).then(function (response) {
        if (response.ok) {
          return response.json();
        }

        return Promise.reject(response);
      }).then(function (data) {
        // Reload current page if successful.
        if ('undefined' !== typeof data.success && data.success) {
          location.reload();
          return;
        } // There will be message probably in case of error


        if ('undefined' !== typeof data.message) {
          _this2.notyf.error(data.message);

          return;
        }

        _this2.notyf.error(_this2.wpstgObject.i18n['somethingWentWrong']);
      })["catch"](function (error) {
        console.warn(_this2.wpstgObject.i18n['somethingWentWrong'], error);
      });
    };

    return WpstgCloneStaging;
  }();

  /**
   * Fetch directory direct child directories
   */

  var WpstgDirectoryNavigation = /*#__PURE__*/function () {
    function WpstgDirectoryNavigation(directoryListingSelector, wpstgObject, notyf) {
      if (directoryListingSelector === void 0) {
        directoryListingSelector = '#wpstg-directories-listing';
      }

      if (wpstgObject === void 0) {
        wpstgObject = wpstg;
      }

      if (notyf === void 0) {
        notyf = null;
      }

      this.directoryListingContainer = qs(directoryListingSelector);
      this.wpstgObject = wpstgObject;
      this.dirCheckboxSelector = '.wpstg-check-dir';
      this.dirExpandSelector = '.wpstg-expand-dirs';
      this.unselectAllDirsSelector = '.wpstg-unselect-dirs';
      this.selectDefaultDirsSelector = '.wpstg-select-dirs-default';
      this.fetchChildrenAction = 'wpstg_fetch_dir_childrens';
      this.currentCheckboxElement = null;
      this.currentParentDiv = null;
      this.currentLoader = null;
      this.existingExcludes = [];
      this.excludedDirectories = [];
      this.isDefaultSelected = false;
      this.notyf = notyf;
      this.init();
    }

    var _proto = WpstgDirectoryNavigation.prototype;

    _proto.addEvents = function addEvents() {
      var _this = this;

      if (this.directoryListingContainer === null) {
        console.log('Error: directory navigation add events');
        return;
      }

      addEvent(this.directoryListingContainer, 'click', this.dirExpandSelector, function (element, event) {
        event.preventDefault();

        if (_this.toggleDirExpand(element)) {
          _this.sendRequest(_this.fetchChildrenAction, element);
        }
      });
      addEvent(this.directoryListingContainer, 'click', this.unselectAllDirsSelector, function () {
        _this.unselectAll();
      });
      addEvent(this.directoryListingContainer, 'click', this.selectDefaultDirsSelector, function () {
        _this.selectDefault();
      });
    };

    _proto.init = function init() {
      this.addEvents();
      this.parseExcludes();
    }
    /**
     * Toggle Dir Expand,
     * Return true if children aren't fetched
     * @param {HTMLElement} element
     * @return {boolean}
     */
    ;

    _proto.toggleDirExpand = function toggleDirExpand(element) {
      this.currentParentDiv = element.parentElement;
      this.currentCheckboxElement = element.previousSibling;
      this.currentLoader = this.currentParentDiv.querySelector('.wpstg-is-dir-loading');

      if (this.currentCheckboxElement.getAttribute('data-navigateable', 'false') === 'false') {
        return false;
      }

      if (this.currentCheckboxElement.getAttribute('data-scanned', 'false') === 'false') {
        return true;
      }

      return false;
    };

    _proto.sendRequest = function sendRequest(action) {
      var _this2 = this;

      if (this.currentLoader !== null) {
        this.currentLoader.style.display = 'inline-block';
      }

      fetch(this.wpstgObject.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new URLSearchParams({
          action: action,
          accessToken: this.wpstgObject.accessToken,
          nonce: this.wpstgObject.nonce,
          dirPath: this.currentCheckboxElement.value,
          isChecked: this.currentCheckboxElement.checked,
          forceDefault: this.isDefaultSelected
        }),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      }).then(function (response) {
        if (response.ok) {
          return response.json();
        }

        return Promise.reject(response);
      }).then(function (data) {
        if ('undefined' !== typeof data.success && data.success) {
          _this2.currentCheckboxElement.setAttribute('data-scanned', true);

          var dirContainer = document.createElement('div');
          dirContainer.classList.add('wpstg-dir');
          dirContainer.classList.add('wpstg-subdir');
          dirContainer.innerHTML = JSON.parse(data.directoryListing);

          _this2.currentParentDiv.appendChild(dirContainer);

          if (_this2.currentLoader !== null) {
            _this2.currentLoader.style.display = 'none';
          }

          slideDown(dirContainer);
          return;
        }

        if (_this2.notyf !== null) {
          _this2.notyf.error(_this2.wpstgObject.i18n['somethingWentWrong']);
        } else {
          alert('Error: ' + _this2.wpstgObject.i18n['somethingWentWrong']);
        }
      })["catch"](function (error) {
        console.warn(_this2.wpstgObject.i18n['somethingWentWrong'], error);
      });
    };

    _proto.getExcludedDirectories = function getExcludedDirectories() {
      var _this3 = this;

      this.excludedDirectories = [];
      this.directoryListingContainer.querySelectorAll('.wpstg-dir input:not(:checked)').forEach(function (element) {
        if (!_this3.isParentExcluded(element.value)) {
          _this3.excludedDirectories.push(element.value);
        }
      });
      this.existingExcludes.forEach(function (exclude) {
        if (!_this3.isParentExcluded(exclude) && !_this3.isExcludeScanned(exclude)) {
          _this3.excludedDirectories.push(exclude);
        }
      });
      return this.excludedDirectories.join(this.wpstgObject.settings.directorySeparator);
    }
    /**
     * @param {string} path
     * @return {bool}
     */
    ;

    _proto.isParentExcluded = function isParentExcluded(path) {
      var isParentAlreadyExcluded = false;
      this.excludedDirectories.forEach(function (dir) {
        if (path.startsWith(dir + '/')) {
          isParentAlreadyExcluded = true;
        }
      });
      return isParentAlreadyExcluded;
    };

    _proto.getExtraDirectoriesRootOnly = function getExtraDirectoriesRootOnly() {
      this.getExcludedDirectories();
      var extraDirectories = [];
      this.directoryListingContainer.querySelectorAll(':not(.wpstg-subdir)>.wpstg-dir>input.wpstg-wp-non-core-dir:checked').forEach(function (element) {
        extraDirectories.push(element.value);
      }); // Check if extra directories text area exists
      // TODO: remove extraCustomDirectories code if no one require extraCustomDirectories...

      var extraDirectoriesTextArea = qs('#wpstg_extraDirectories');

      if (extraDirectoriesTextArea === null || extraDirectoriesTextArea.value === '') {
        return extraDirectories.join(this.wpstgObject.settings.directorySeparator);
      }

      var extraCustomDirectories = extraDirectoriesTextArea.value.split(/\r?\n/);
      return extraDirectories.concat(extraCustomDirectories).join(this.wpstgObject.settings.directorySeparator);
    };

    _proto.unselectAll = function unselectAll() {
      this.directoryListingContainer.querySelectorAll('.wpstg-dir input').forEach(function (element) {
        element.checked = false;
      });
    };

    _proto.selectDefault = function selectDefault() {
      // unselect all checkboxes
      this.unselectAll(); // only select those checkboxes whose class is wpstg-wp-core-dir

      this.directoryListingContainer.querySelectorAll('.wpstg-dir input.wpstg-wp-core-dir').forEach(function (element) {
        element.checked = true;
      }); // then unselect those checkboxes whose parent has wpstg extra checkbox

      this.directoryListingContainer.querySelectorAll('.wpstg-dir > .wpstg-wp-non-core-dir').forEach(function (element) {
        element.parentElement.querySelectorAll('input.wpstg-wp-core-dir').forEach(function (element) {
          element.checked = false;
        });
      });
      this.isDefaultSelected = true;
    };

    _proto.parseExcludes = function parseExcludes() {
      this.existingExcludes = this.directoryListingContainer.getAttribute('data-existing-excludes', []);

      if (this.existingExcludes === '') {
        this.existingExcludes = [];
      }

      if (this.existingExcludes.length !== 0) {
        this.existingExcludes = this.existingExcludes.split(',');
      }
    };

    _proto.isExcludeScanned = function isExcludeScanned(exclude) {
      this.directoryListingContainer.querySelectorAll('.wpstg-dir input').forEach(function (element) {
        if (element.value === exclude) {
          return true;
        }
      });
      return false;
    };

    return WpstgDirectoryNavigation;
  }();

  /**
   * Rich Exclude Filter Module
   */

  var WpstgExcludeFilters = /*#__PURE__*/function () {
    function WpstgExcludeFilters(excludeFilterContainerSelector, wpstgObject) {
      if (excludeFilterContainerSelector === void 0) {
        excludeFilterContainerSelector = '#wpstg-exclude-filters-container';
      }

      if (wpstgObject === void 0) {
        wpstgObject = wpstg;
      }

      this.excludeContainer = qs(excludeFilterContainerSelector);
      this.excludeTableBody = qs(excludeFilterContainerSelector + " tbody");
      this.wpstgObject = wpstgObject;
      this.init();
    }

    var _proto = WpstgExcludeFilters.prototype;

    _proto.addEvents = function addEvents() {
      var _this = this;

      addEvent(this.excludeContainer, 'click', '.wpstg-file-size-rule', function () {
        _this.addFileSizeExclude();
      });
      addEvent(this.excludeContainer, 'click', '.wpstg-file-ext-rule', function () {
        _this.addFileExtExclude();
      });
      addEvent(this.excludeContainer, 'click', '.wpstg-file-name-rule', function () {
        _this.addFileNameExclude();
      });
      addEvent(this.excludeContainer, 'click', '.wpstg-dir-name-rule', function () {
        _this.addDirNameExclude();
      });
      addEvent(this.excludeContainer, 'click', '.wpstg-clear-all-rules', function () {
        _this.clearExcludes();
      });
      addEvent(this.excludeContainer, 'click', '.wpstg-remove-exclude-rule', function (target) {
        _this.removeExclude(target);
      });
    };

    _proto.init = function init() {
      if (this.excludeContainer === null) {
        console.log('Error: Given table selector not found!');
        return;
      }

      this.addEvents();
    };

    _proto.addFileSizeExclude = function addFileSizeExclude() {
      this.addExcludeRuleRow('#wpstg-file-size-exclude-filter-template');
    };

    _proto.addFileExtExclude = function addFileExtExclude() {
      this.addExcludeRuleRow('#wpstg-file-ext-exclude-filter-template');
    };

    _proto.addFileNameExclude = function addFileNameExclude() {
      this.addExcludeRuleRow('#wpstg-file-name-exclude-filter-template');
    };

    _proto.addDirNameExclude = function addDirNameExclude() {
      this.addExcludeRuleRow('#wpstg-dir-name-exclude-filter-template');
    };

    _proto.addExcludeRuleRow = function addExcludeRuleRow(templateName) {
      var excludeRowTemplate = qs(templateName);

      if (excludeRowTemplate !== null) {
        var clone = excludeRowTemplate.content.cloneNode(true);
        var excludeRow = clone.querySelector('tr');
        this.excludeTableBody.appendChild(excludeRow);
        all('.wpstg-has-exclude-rules').forEach(function (e) {
          e.style.display = 'inherit';
        });
      }
    };

    _proto.clearExcludes = function clearExcludes() {
      this.excludeTableBody.innerHTML = '';
      all('.wpstg-has-exclude-rules').forEach(function (e) {
        e.style.display = 'none';
      });
    };

    _proto.removeExclude = function removeExclude(target) {
      if (target.parentElement !== null && target.parentElement.parentElement !== null) {
        this.excludeTableBody.removeChild(target.parentElement.parentElement);
      }

      if (this.excludeTableBody.innerHTML.trim() === '') {
        all('.wpstg-has-exclude-rules').forEach(function (e) {
          e.style.display = 'none';
        });
      }
    }
    /**
     * Converts all the exclude filters arrays into one single string to keep size of post request small
     * @return {string}
     */
    ;

    _proto.getExcludeFilters = function getExcludeFilters() {
      var _this2 = this;

      var globExcludes = [];
      var sizeExcludes = [];
      var sizeCompares = this.excludeTableBody.querySelectorAll('select[name="wpstgFileSizeExcludeRuleCompare[]"]');
      var sizeSizes = this.excludeTableBody.querySelectorAll('input[name="wpstgFileSizeExcludeRuleSize[]"]');
      var sizeByte = this.excludeTableBody.querySelectorAll('select[name="wpstgFileSizeExcludeRuleByte[]"]');

      for (var _i = 0, _Object$entries = Object.entries(sizeSizes); _i < _Object$entries.length; _i++) {
        var _Object$entries$_i = _Object$entries[_i],
            key = _Object$entries$_i[0],
            sizeInput = _Object$entries$_i[1];

        if (sizeInput.value !== '') {
          sizeExcludes.push(sizeCompares[key].value + ' ' + sizeInput.value + sizeByte[key].value);
        }
      }

      var extensionInputs = this.excludeTableBody.querySelectorAll('input[name="wpstgFileExtExcludeRule[]"]');
      extensionInputs.forEach(function (x) {
        var ext = _this2.cleanStringForGlob(x.value);

        if (ext !== '') {
          globExcludes.push('ext:' + ext.trim());
        }
      });
      var fileNamesPos = this.excludeTableBody.querySelectorAll('select[name="wpstgFileNameExcludeRulePos[]"]');
      var fileNames = this.excludeTableBody.querySelectorAll('input[name="wpstgFileNameExcludeRulePath[]"]');

      for (var _i2 = 0, _Object$entries2 = Object.entries(fileNames); _i2 < _Object$entries2.length; _i2++) {
        var _Object$entries2$_i = _Object$entries2[_i2],
            _key = _Object$entries2$_i[0],
            fileInput = _Object$entries2$_i[1];
        var fileName = this.cleanStringForGlob(fileInput.value);

        if (fileName !== '') {
          globExcludes.push('file:' + fileNamesPos[_key].value + ' ' + fileName.trim());
        }
      }

      var dirNamesPos = this.excludeTableBody.querySelectorAll('select[name="wpstgDirNameExcludeRulePos[]"]');
      var dirNames = this.excludeTableBody.querySelectorAll('input[name="wpstgDirNameExcludeRulePath[]"]');

      for (var _i3 = 0, _Object$entries3 = Object.entries(dirNames); _i3 < _Object$entries3.length; _i3++) {
        var _Object$entries3$_i = _Object$entries3[_i3],
            _key2 = _Object$entries3$_i[0],
            dirInput = _Object$entries3$_i[1];
        var dirName = this.cleanStringForGlob(dirInput.value);

        if (dirName !== '') {
          globExcludes.push('dir:' + dirNamesPos[_key2].value + ' ' + dirName.trim());
        }
      }

      return {
        'sizes': sizeExcludes.filter(this.onlyUnique).join(','),
        // return set of unique rules
        'globs': globExcludes.filter(this.onlyUnique).join(',')
      };
    };

    _proto.onlyUnique = function onlyUnique(value, index, self) {
      return self.indexOf(value) === index;
    }
    /**
     * Remove most of the comment glob characters from the string
     * @param {String} value
     * @return {String}
     */
    ;

    _proto.cleanStringForGlob = function cleanStringForGlob(value) {
      // will replace character like * ^ / \ ! ? [ from the string
      return value.replace(/[*^//!\.[?]/g, '');
    };

    return WpstgExcludeFilters;
  }();

  /**
   * Basic WP Staging Modal implemented with help of Sweetalerts
   */
  var WpstgModal = /*#__PURE__*/function () {
    function WpstgModal(confirmAction, wpstgObject) {
      if (wpstgObject === void 0) {
        wpstgObject = wpstg;
      }

      this.confirmAction = confirmAction;
      this.wpstgObject = wpstgObject;
    }

    var _proto = WpstgModal.prototype;

    _proto.show = function show(swalOptions, additionalParams, callback) {
      var _this = this;

      if (additionalParams === void 0) {
        additionalParams = {};
      }

      if (callback === void 0) {
        callback = null;
      }

      wpstgSwal.fire(swalOptions).then(function (result) {
        if (result.value && _this.error !== null) {
          _this.triggerConfirmAction(additionalParams, callback);
        }
      });
    };

    _proto.triggerConfirmAction = function triggerConfirmAction(additionalParams, callback) {
      var _this2 = this;

      if (additionalParams === void 0) {
        additionalParams = {};
      }

      if (callback === void 0) {
        callback = null;
      }

      fetch(this.wpstgObject.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new URLSearchParams(Object.assign({
          action: this.confirmAction,
          accessToken: this.wpstgObject.accessToken,
          nonce: this.wpstgObject.nonce
        }, additionalParams)),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      }).then(function (response) {
        if (response.ok) {
          return response.json();
        }

        return Promise.reject(response);
      }).then(function (response) {
        if (callback !== null) {
          callback(response);
        }
      })["catch"](function (error) {
        console.log(_this2.wpstgObject.i18n['somethingWentWrong'], error);
      });
    };

    return WpstgModal;
  }();

  /**
   * This is a namespaced port of https://github.com/tristen/hoverintent,
   * with slight modification to accept selector with dynamically added element in dom,
   * instead of just already present element.
   *
   * @param {HTMLElement} parent
   * @param {string} selector
   * @param {CallableFunction} onOver
   * @param {CallableFunction} onOut
   *
   * @return {object}
   */

  function wpstgHoverIntent (parent, selector, onOver, onOut) {
    var x;
    var y;
    var pX;
    var pY;
    var mouseOver = false;
    var focused = false;
    var h = {};
    var state = 0;
    var timer = 0;
    var options = {
      sensitivity: 7,
      interval: 100,
      timeout: 0,
      handleFocus: false
    };

    function delay(el, e) {
      if (timer) {
        timer = clearTimeout(timer);
      }

      state = 0;
      return focused ? undefined : onOut(el, e);
    }

    function tracker(e) {
      x = e.clientX;
      y = e.clientY;
    }

    function compare(el, e) {
      if (timer) timer = clearTimeout(timer);

      if (Math.abs(pX - x) + Math.abs(pY - y) < options.sensitivity) {
        state = 1;
        return focused ? undefined : onOver(el, e);
      } else {
        pX = x;
        pY = y;
        timer = setTimeout(function () {
          compare(el, e);
        }, options.interval);
      }
    } // Public methods


    h.options = function (opt) {
      var focusOptionChanged = opt.handleFocus !== options.handleFocus;
      options = Object.assign({}, options, opt);

      if (focusOptionChanged) {
        options.handleFocus ? addFocus() : removeFocus();
      }

      return h;
    };

    function dispatchOver(el, e) {
      mouseOver = true;

      if (timer) {
        timer = clearTimeout(timer);
      }

      el.removeEventListener('mousemove', tracker, false);

      if (state !== 1) {
        pX = e.clientX;
        pY = e.clientY;
        el.addEventListener('mousemove', tracker, false);
        timer = setTimeout(function () {
          compare(el, e);
        }, options.interval);
      }

      return this;
    }
    /**
     * Newly added method,
     * A wrapper around dispatchOver to support dynamically added elements to dom
     */


    function onMouseOver(event) {
      if (event.target.matches(selector + ', ' + selector + ' *')) {
        dispatchOver(event.target.closest(selector), event);
      }
    }

    function dispatchOut(el, e) {
      mouseOver = false;

      if (timer) {
        timer = clearTimeout(timer);
      }

      el.removeEventListener('mousemove', tracker, false);

      if (state === 1) {
        timer = setTimeout(function () {
          delay(el, e);
        }, options.timeout);
      }

      return this;
    }
    /**
     * Newly added method,
     * A wrapper around dispatchOut to support dynamically added elements to dom
     */


    function onMouseOut(event) {
      if (event.target.matches(selector + ', ' + selector + ' *')) {
        dispatchOut(event.target.closest(selector), event);
      }
    }

    function dispatchFocus(el, e) {
      if (!mouseOver) {
        focused = true;
        onOver(el, e);
      }
    }
    /**
     * Newly added method,
     * A wrapper around dispatchFocus to support dynamically added elements to dom
     */


    function onFocus(event) {
      if (event.target.matches(selector + ', ' + selector + ' *')) {
        dispatchFocus(event.target.closest(selector), event);
      }
    }

    function dispatchBlur(el, e) {
      if (!mouseOver && focused) {
        focused = false;
        onOut(el, e);
      }
    }
    /**
     * Newly added method,
     * A wrapper around dispatchBlur to support dynamically added elements to dom
     */


    function onBlur(event) {
      if (event.target.matches(selector + ', ' + selector + ' *')) {
        dispatchBlur(event.target.closest(selector), event);
      }
    }
    /**
     * Modified to support dynamically added element
     */

    function addFocus() {
      parent.addEventListener('focus', onFocus, false);
      parent.addEventListener('blur', onBlur, false);
    }
    /**
     * Modified to support dynamically added element
     */


    function removeFocus() {
      parent.removeEventListener('focus', onFocus, false);
      parent.removeEventListener('blur', onBlur, false);
    }
    /**
     * Modified to support dynamically added element
     */


    h.remove = function () {
      if (!parent) {
        return;
      }

      parent.removeEventListener('mouseover', onMouseOver, false);
      parent.removeEventListener('mouseout', onMouseOut, false);
      removeFocus();
    };
    /**
     * Modified to support dynamically added element
     */


    if (parent) {
      parent.addEventListener('mouseover', onMouseOver, false);
      parent.addEventListener('mouseout', onMouseOut, false);
    }

    return h;
  }

  var WPStagingCommon = (function ($) {
    var WPStagingCommon = {
      continueErrorHandle: true,
      cache: {
        elements: [],
        get: function get(selector) {
          // It is already cached!
          if ($.inArray(selector, this.elements) !== -1) {
            return this.elements[selector];
          } // Create cache and return


          this.elements[selector] = $(selector);
          return this.elements[selector];
        },
        refresh: function refresh(selector) {
          selector.elements[selector] = $(selector);
        }
      },
      listenTooltip: function listenTooltip() {
        wpstgHoverIntent(document, '.wpstg--tooltip', function (target, event) {
          target.querySelector('.wpstg--tooltiptext').style.visibility = 'visible';
        }, function (target, event) {
          target.querySelector('.wpstg--tooltiptext').style.visibility = 'hidden';
        });
      },
      isEmpty: function isEmpty(obj) {
        for (var prop in obj) {
          if (obj.hasOwnProperty(prop)) {
            return false;
          }
        }

        return true;
      },
      // Get the custom themed Swal Modal for WP Staging
      // Easy to maintain now in one place now
      getSwalModal: function getSwalModal(isContentCentered, customClasses) {
        if (isContentCentered === void 0) {
          isContentCentered = false;
        }

        if (customClasses === void 0) {
          customClasses = {};
        }

        // common style for all swal modal used in WP Staging
        var defaultCustomClasses = {
          confirmButton: 'wpstg--btn--confirm wpstg-blue-primary wpstg-button wpstg-link-btn wpstg-100-width',
          cancelButton: 'wpstg--btn--cancel wpstg-blue-primary wpstg-link-btn wpstg-100-width',
          actions: 'wpstg--modal--actions',
          popup: isContentCentered ? 'wpstg-swal-popup centered-modal' : 'wpstg-swal-popup'
        }; // If a attribute exists in both default and additional attributes,
        // The class(es) of the additional attribute will overrite the default one.

        var options = {
          customClass: Object.assign(defaultCustomClasses, customClasses),
          buttonsStyling: false,
          reverseButtons: true,
          showClass: {
            popup: 'wpstg--swal2-show wpstg-swal-show'
          }
        };
        return wpstgSwal.mixin(options);
      },
      showSuccessModal: function showSuccessModal(htmlContent) {
        this.getSwalModal().fire({
          showConfirmButton: false,
          showCancelButton: true,
          cancelButtonText: 'OK',
          icon: 'success',
          title: 'Success!',
          html: '<div class="wpstg--grey" style="text-align: left; margin-top: 8px;">' + htmlContent + '</div>'
        });
      },
      showWarningModal: function showWarningModal(htmlContent) {
        this.getSwalModal().fire({
          showConfirmButton: false,
          showCancelButton: true,
          cancelButtonText: 'OK',
          icon: 'warning',
          title: '',
          html: '<div class="wpstg--grey" style="text-align: left; margin-top: 8px;">' + htmlContent + '</div>'
        });
      },
      showErrorModal: function showErrorModal(htmlContent) {
        this.getSwalModal().fire({
          showConfirmButton: false,
          showCancelButton: true,
          cancelButtonText: 'OK',
          icon: 'error',
          title: 'Error!',
          html: '<div class="wpstg--grey" style="text-align: left; margin-top: 8px;">' + htmlContent + '</div>'
        });
      },
      getSwalContainer: function getSwalContainer() {
        return wpstgSwal.getContainer();
      },
      closeSwalModal: function closeSwalModal() {
        wpstgSwal.close();
      },

      /**
       * Treats a default response object generated by WordPress's
       * wp_send_json_success() or wp_send_json_error() functions in
       * PHP, parses it in JavaScript, and either throws if it's an error,
       * or returns the data if the response is successful.
       *
       * @param {object} response
       * @return {*}
       */
      getDataFromWordPressResponse: function getDataFromWordPressResponse(response) {
        if (typeof response !== 'object') {
          throw new Error('Unexpected response (ERR 1341)');
        }

        if (!response.hasOwnProperty('success')) {
          throw new Error('Unexpected response (ERR 1342)');
        }

        if (!response.hasOwnProperty('data')) {
          throw new Error('Unexpected response (ERR 1343)');
        }

        if (response.success === false) {
          if (response.data instanceof Array && response.data.length > 0) {
            throw new Error(response.data.shift());
          } else {
            throw new Error('Response was not successful');
          }
        } else {
          // Successful response. Return the data.
          return response.data;
        }
      },
      isLoading: function isLoading(_isLoading) {
        if (!_isLoading || _isLoading === false) {
          WPStagingCommon.cache.get('.wpstg-loader').hide();
        } else {
          WPStagingCommon.cache.get('.wpstg-loader').show();
        }
      },

      /**
       * Convert the given url to make it slug compatible
       * @param {string} url
       */
      slugify: function slugify(url) {
        return url.toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, '-').replace(/&/g, '-and-').replace(/[^a-z0-9\-]/g, '').replace(/-+/g, '-').replace(/^-*/, '').replace(/-*$/, '');
      },
      showAjaxFatalError: function showAjaxFatalError(response, prependMessage, appendMessage) {
        prependMessage = prependMessage ? prependMessage + '<br/><br/>' : 'Something went wrong! <br/><br/>';
        appendMessage = appendMessage ? appendMessage + '<br/><br/>' : '<br/><br/>Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.';

        if (response === false) {
          WPStagingCommon.showError(prependMessage + ' Error: No response.' + appendMessage);
          window.removeEventListener('beforeunload', WPStaging.warnIfClosingDuringProcess);
          return;
        }

        if (typeof response.error !== 'undefined' && response.error) {
          WPStagingCommon.showError(prependMessage + ' Error: ' + response.message + appendMessage);
          window.removeEventListener('beforeunload', WPStaging.warnIfClosingDuringProcess);
          return;
        }
      },
      handleFetchErrors: function handleFetchErrors(response) {
        if (!response.ok) {
          WPStagingCommon.showError('Error: ' + response.status + ' - ' + response.statusText + '. Please try again or contact support.');
        }

        return response;
      },
      showError: function showError(message) {
        WPStagingCommon.cache.get('#wpstg-try-again').css('display', 'inline-block');
        WPStagingCommon.cache.get('#wpstg-cancel-cloning').text('Reset');
        WPStagingCommon.cache.get('#wpstg-resume-cloning').show();
        WPStagingCommon.cache.get('#wpstg-error-wrapper').show();
        WPStagingCommon.cache.get('#wpstg-error-details').show().html(message);
        WPStagingCommon.cache.get('#wpstg-removing-clone').removeClass('loading');
        WPStagingCommon.cache.get('.wpstg-loader').hide();
        $('.wpstg--modal--process--generic-problem').show().html(message);
      },
      resetErrors: function resetErrors() {
        WPStagingCommon.cache.get('#wpstg-error-details').hide().html('');
      },

      /**
       * Ajax Requests
       * @param {Object} data
       * @param {Function} callback
       * @param {string} dataType
       * @param {bool} showErrors
       * @param {int} tryCount
       * @param {float} incrementRatio
       * @param errorCallback
       */
      ajax: function ajax(data, callback, dataType, showErrors, tryCount, incrementRatio, errorCallback) {
        if (incrementRatio === void 0) {
          incrementRatio = null;
        }

        if (errorCallback === void 0) {
          errorCallback = null;
        }

        if ('undefined' === typeof dataType) {
          dataType = 'json';
        }

        if (false !== showErrors) {
          showErrors = true;
        }

        tryCount = 'undefined' === typeof tryCount ? 0 : tryCount;
        var retryLimit = 10;
        var retryTimeout = 10000 * tryCount;
        incrementRatio = parseInt(incrementRatio);

        if (!isNaN(incrementRatio)) {
          retryTimeout *= incrementRatio;
        }

        $.ajax({
          url: ajaxurl + '?action=wpstg_processing&_=' + Date.now() / 1000,
          type: 'POST',
          dataType: dataType,
          cache: false,
          data: data,
          error: function error(xhr, textStatus, errorThrown) {
            console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);

            if (typeof errorCallback === 'function') {
              // Custom error handler
              errorCallback(xhr, textStatus, errorThrown);

              if (!WPStagingCommon.continueErrorHandle) {
                // Reset state
                WPStagingCommon.continueErrorHandle = true;
                return;
              }
            } // Default error handler


            tryCount++;

            if (tryCount <= retryLimit) {
              setTimeout(function () {
                WPStagingCommon.ajax(data, callback, dataType, showErrors, tryCount, incrementRatio);
                return;
              }, retryTimeout);
            } else {
              var errorCode = 'undefined' === typeof xhr.status ? 'Unknown' : xhr.status;
              WPStagingCommon.showError('Fatal Error:  ' + errorCode + ' Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.');
            }
          },
          success: function success(data) {
            if ('function' === typeof callback) {
              callback(data);
            }
          },
          statusCode: {
            404: function _() {
              if (tryCount >= retryLimit) {
                WPStagingCommon.showError('Error 404 - Can\'t find ajax request URL! Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.');
              }
            },
            500: function _() {
              if (tryCount >= retryLimit) {
                WPStagingCommon.showError('Fatal Error 500 - Internal server error while processing the request! Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.');
              }
            },
            504: function _() {
              if (tryCount > retryLimit) {
                WPStagingCommon.showError('Error 504 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ');
              }
            },
            502: function _() {
              if (tryCount >= retryLimit) {
                WPStagingCommon.showError('Error 502 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ');
              }
            },
            503: function _() {
              if (tryCount >= retryLimit) {
                WPStagingCommon.showError('Error 503 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ');
              }
            },
            429: function _() {
              if (tryCount >= retryLimit) {
                WPStagingCommon.showError('Error 429 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ');
              }
            },
            403: function _() {
              if (tryCount >= retryLimit) {
                WPStagingCommon.showError('Refresh page or login again! The process should be finished successfully. \n\ ');
              }
            }
          }
        });
      }
    };
    return WPStagingCommon;
  })(jQuery);

  /**
   * Manage RESET MODAL
   */

  var WpstgResetModal = /*#__PURE__*/function () {
    function WpstgResetModal(cloneID, workflowSelector, fetchExcludeSettingsAction, modalErrorAction, wpstgObject) {
      if (workflowSelector === void 0) {
        workflowSelector = '#wpstg-workflow';
      }

      if (fetchExcludeSettingsAction === void 0) {
        fetchExcludeSettingsAction = 'wpstg_clone_excludes_settings';
      }

      if (modalErrorAction === void 0) {
        modalErrorAction = 'wpstg_modal_error';
      }

      if (wpstgObject === void 0) {
        wpstgObject = wpstg;
      }

      this.cloneID = cloneID;
      this.workflow = qs(workflowSelector);
      this.wpstgObject = wpstgObject;
      this.fetchExcludeSettingsAction = fetchExcludeSettingsAction;
      this.modalErrorAction = modalErrorAction;
      this.resetButtonClass = 'wpstg-confirm-reset-clone';
      this.resetModalContainerClass = 'wpstg-reset-confirmation';
      this.resetTabSelector = '.wpstg-reset-exclude-tab';
      this.directoryNavigator = null;
      this.excludeFilters = null;
      this.isAllTablesChecked = true;
    }

    var _proto = WpstgResetModal.prototype;

    _proto.addEvents = function addEvents() {
      var _this = this;

      var resetModalContainer = qs('.' + this.resetModalContainerClass);

      if (resetModalContainer === null) {
        console.log('Exit');
        return;
      }

      addEvent(resetModalContainer, 'click', this.resetTabSelector, function (target) {
        _this.toggleContent(target);
      });
      addEvent(resetModalContainer, 'click', '.wpstg-button-select', function () {
        _this.selectDefaultTables();
      });
      addEvent(resetModalContainer, 'click', '.wpstg-button-unselect', function () {
        _this.toggleTableSelection();
      });
      addEvent(resetModalContainer, 'click', '.wpstg-expand-dirs', function (target, event) {
        event.preventDefault();

        _this.toggleDirectoryNavigation(target);
      });
      addEvent(resetModalContainer, 'change', 'input.wpstg-check-dir', function (target) {
        _this.updateDirectorySelection(target);
      });
    };

    _proto.init = function init() {
      this.addEvents();
    };

    _proto.toggleContent = function toggleContent(target) {
      var resetModalContainer = qs('.' + this.resetModalContainerClass);
      var contentId = target.getAttribute('data-id');
      var tabTriangle = target.querySelector('.wpstg-tab-triangle');
      var isCollapsed = target.getAttribute('data-collapsed', 'true');
      var content = qs(contentId);

      if (isCollapsed === 'true') {
        if (resetModalContainer.classList.contains('has-collapsible-open')) {
          resetModalContainer.classList.add('has-collapsible-open-2');
        } else {
          resetModalContainer.classList.add('has-collapsible-open');
        }

        slideDown(content);
        tabTriangle.style.transform = 'rotate(90deg)';
        target.setAttribute('data-collapsed', 'false');
      } else {
        if (resetModalContainer.classList.contains('has-collapsible-open-2')) {
          resetModalContainer.classList.remove('has-collapsible-open-2');
        } else {
          resetModalContainer.classList.remove('has-collapsible-open');
        }

        slideUp(content);
        tabTriangle.style.removeProperty('transform');
        target.setAttribute('data-collapsed', 'true');
      }
    }
    /**
     * Show Swal alert with loader and send ajax request to fetch content of alert.
     * @return Promise
     */
    ;

    _proto.showModal = function showModal() {
      var swalPromise = this.loadModal();
      this.init();
      this.fetchCloneExcludes();
      return swalPromise;
    };

    _proto.loadModal = function loadModal() {
      return WPStagingCommon.getSwalModal(false, {
        confirmButton: this.resetButtonClass + ' wpstg-confirm-reset-clone wpstg--btn--confirm wpstg-blue-primary wpstg-button wpstg-link-btn',
        container: this.resetModalContainerClass + ' wpstg-swal2-container wpstg-swal2-loading'
      }).fire({
        title: '',
        icon: 'warning',
        html: this.getAjaxLoader(),
        width: '400px',
        focusConfirm: false,
        confirmButtonText: this.wpstgObject.i18n.resetClone,
        showCancelButton: true
      });
    };

    _proto.fetchCloneExcludes = function fetchCloneExcludes() {
      var _this2 = this;

      this.error = null; // send ajax request and fetch preserved exclude settings

      fetch(this.wpstgObject.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new URLSearchParams({
          action: this.fetchExcludeSettingsAction,
          accessToken: this.wpstgObject.accessToken,
          nonce: this.wpstgObject.nonce,
          clone: this.cloneID,
          job: 'resetting'
        }),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      }).then(function (response) {
        if (response.ok) {
          return response.json();
        }

        return Promise.reject(response);
      }).then(function (data) {
        if (!data.success) {
          var errorModal = new WpstgModal(_this2.modalErrorAction, _this2.wpstgObject);
          errorModal.show(Object.assign({
            title: 'Error',
            icon: 'error',
            html: _this2.wpstgObject.i18n['somethingWentWrong'],
            width: '500px',
            confirmButtonText: 'Ok',
            showCancelButton: false,
            customClass: {
              confirmButton: 'wpstg--btn--confirm wpstg-blue-primary wpstg-button wpstg-link-btn',
              cancelButton: 'wpstg--btn--cancel wpstg-blue-primary wpstg-link-btn',
              actions: 'wpstg--modal--actions',
              popup: 'wpstg-swal-popup centered-modal'
            },
            buttonsStyling: false,
            reverseButtons: true
          }, data.swalOptions), {
            type: data.type
          });
          return;
        }

        var modal = qs('.wpstg-reset-confirmation');
        modal.classList.remove('wpstg-swal2-loading');
        modal.querySelector('.wpstg--swal2-popup').style.width = '500px';
        modal.querySelector('.wpstg--swal2-content').innerHTML = data.html;
        _this2.directoryNavigator = new WpstgDirectoryNavigation();
        _this2.excludeFilters = new WpstgExcludeFilters();
      })["catch"](function (error) {
        _this2.renderError({
          'html': _this2.wpstgObject.i18n['somethingWentWrong'] + ' ' + error
        });
      });
    };

    _proto.getDirectoryNavigator = function getDirectoryNavigator() {
      return this.directoryNavigator;
    };

    _proto.getExcludeFilters = function getExcludeFilters() {
      return this.excludeFilters;
    };

    _proto.getAjaxLoader = function getAjaxLoader() {
      return '<div class="wpstg-swal2-ajax-loader"><img src="' + this.wpstgObject.wpstgIcon + '" /></div>';
    };

    _proto.toggleDirectoryNavigation = function toggleDirectoryNavigation(element) {
      var cbElement = element.previousSibling;

      if (cbElement.getAttribute('data-navigateable', 'false') === 'false') {
        return;
      }

      if (cbElement.getAttribute('data-scanned', 'false') === 'false') {
        return;
      }

      var subDirectories = getNextSibling(element, '.wpstg-subdir');

      if (subDirectories.style.display === 'none') {
        slideDown(subDirectories);
      } else {
        slideUp(subDirectories);
      }
    };

    _proto.updateDirectorySelection = function updateDirectorySelection(element) {
      var parent = element.parentElement;

      if (element.checked) {
        getParents(parent, '.wpstg-dir').forEach(function (parElem) {
          for (var i = 0; i < parElem.children.length; i++) {
            if (parElem.children[i].matches('.wpstg-check-dir')) {
              parElem.children[i].checked = true;
            }
          }
        });
        parent.querySelectorAll('.wpstg-expand-dirs').forEach(function (x) {
          x.classList.remove('disabled');
        });
        parent.querySelectorAll('.wpstg-subdir .wpstg-check-dir').forEach(function (x) {
          x.checked = true;
        });
      } else {
        parent.querySelectorAll('.wpstg-expand-dirs, .wpstg-check-subdirs').forEach(function (x) {
          x.classList.add('disabled');
        });
        parent.querySelectorAll('.wpstg-dir .wpstg-check-dir').forEach(function (x) {
          x.checked = false;
        });
      }
    };

    _proto.selectDefaultTables = function selectDefaultTables() {
      var _this3 = this;

      var resetModalContainer = qs('.' + this.resetModalContainerClass);
      var options = resetModalContainer.querySelectorAll('#wpstg_select_tables_cloning .wpstg-db-table');
      var multisitePattern = '^' + this.wpstgObject.tblprefix + '([^0-9])_*';
      var singleSitePattern = '^' + this.wpstgObject.tblprefix;
      options.forEach(function (option) {
        var name = option.getAttribute('name', '');

        if (_this3.wpstgObject.isMultisite === '1' && name.match(multisitePattern)) {
          option.setAttribute('selected', 'selected');
        } else if (_this3.wpstgObject.isMultisite === '' && name.match(singleSitePattern)) {
          option.setAttribute('selected', 'selected');
        } else {
          option.removeAttribute('selected');
        }
      });
    };

    _proto.toggleTableSelection = function toggleTableSelection() {
      var resetModalContainer = qs('.' + this.resetModalContainerClass);

      if (false === this.isAllTablesChecked) {
        resetModalContainer.querySelectorAll('#wpstg_select_tables_cloning .wpstg-db-table').forEach(function (option) {
          option.setAttribute('selected', 'selected');
        });
        resetModalContainer.querySelector('.wpstg-button-unselect').innerHTML = 'Unselect All'; // cache.get('.wpstg-db-table-checkboxes').prop('checked', true);

        this.isAllTablesChecked = true;
      } else {
        resetModalContainer.querySelectorAll('#wpstg_select_tables_cloning .wpstg-db-table').forEach(function (option) {
          option.removeAttribute('selected');
        });
        resetModalContainer.querySelector('.wpstg-button-unselect').innerHTML = 'Select All'; // cache.get('.wpstg-db-table-checkboxes').prop('checked', false);

        this.isAllTablesChecked = false;
      }
    };

    return WpstgResetModal;
  }();

  /**
   * Handle toggle of advance settings checkboxes
   */

  var WpstgCloningAdvanceSettings = /*#__PURE__*/function () {
    function WpstgCloningAdvanceSettings(baseContainerSelector) {
      if (baseContainerSelector === void 0) {
        baseContainerSelector = '#wpstg-clonepage-wrapper';
      }

      this.baseContainer = qs(baseContainerSelector);
      this.checkBoxSettingTogglerSelector = '.wpstg-toggle-advance-settings-section';
      this.init();
    }
    /**
     * Add events
     * @return {void}
     */


    var _proto = WpstgCloningAdvanceSettings.prototype;

    _proto.addEvents = function addEvents() {
      var _this = this;

      if (this.baseContainer === null) {
        return;
      }

      addEvent(this.baseContainer, 'change', this.checkBoxSettingTogglerSelector, function (element) {
        _this.toggleSettings(element);
      });
    }
    /**
     * @return {void}
     */
    ;

    _proto.init = function init() {
      this.addEvents();
    }
    /**
     * Expand/Collapse checkbox content on change
     * @return {void}
     */
    ;

    _proto.toggleSettings = function toggleSettings(element) {
      var target = qs('#' + element.getAttribute('data-id'));

      if (element.checked) {
        slideDown(target);
      } else {
        slideUp(target);
      }
    };

    return WpstgCloningAdvanceSettings;
  }();

  var WpstgMainMenu = /*#__PURE__*/function () {
    function WpstgMainMenu() {
      this.activeTabClass = 'wpstg--tab--active';
      this.mainMenu();
    }

    var _proto = WpstgMainMenu.prototype;

    _proto.mainMenu = function mainMenu() {
      var _this = this;

      var tabHeader = qs('.wpstg--tab--header'); // Early bail if tab header is not available

      if (tabHeader === null) {
        return;
      }

      addEvent(qs('.wpstg--tab--header'), 'click', '.wpstg-button', function (element) {
        var $this = element;
        var target = $this.getAttribute('data-target');
        var targetElements = all(target);
        var menuItems = all('.wpstg--tab--header a[data-target]');
        var contents = all('.wpstg--tab--contents > .wpstg--tab--content');
        contents.forEach(function (content) {
          // active tab class is without the css dot class prefix
          if (content.matches('.' + _this.activeTabClass + ':not(' + target + ')')) {
            content.classList.remove(_this.activeTabClass);
          }
        });
        menuItems.forEach(function (menuItem) {
          if (menuItem !== $this) {
            menuItem.classList.remove(_this.activeTabClass);
          }
        });
        $this.classList.add(_this.activeTabClass);
        targetElements.forEach(function (targetElement) {
          targetElement.classList.add(_this.activeTabClass);
        });

        if ('#wpstg--tab--backup' === target) {
          window.dispatchEvent(new Event('backups-tab'));
        }
      });
    };

    return WpstgMainMenu;
  }();

  var WPStaging$1 = function ($) {
    var that = {
      isCancelled: false,
      isFinished: false,
      getLogs: false,
      time: 1,
      executionTime: false,
      progressBar: 0,
      cloneExcludeFilters: null,
      directoryNavigator: null,
      notyf: null,
      areAllTablesChecked: true
    };
    var cache = {
      elements: []
    };
    var ajaxSpinner;
    /**
       * Get / Set Cache for Selector
       * @param {String} selector
       * @return {*}
       */

    cache.get = function (selector) {
      // It is already cached!
      if ($.inArray(selector, cache.elements) !== -1) {
        return cache.elements[selector];
      } // Create cache and return


      cache.elements[selector] = jQuery(selector);
      return cache.elements[selector];
    };
    /**
       * Refreshes given cache
       * @param {String} selector
       */


    cache.refresh = function (selector) {
      selector.elements[selector] = jQuery(selector);
    };
    /**
       * Show and Log Error Message
       * @param {String} message
       */


    var showError = function showError(message) {
      cache.get('#wpstg-try-again').css('display', 'inline-block');
      cache.get('#wpstg-cancel-cloning').text('Reset');
      cache.get('#wpstg-resume-cloning').show();
      cache.get('#wpstg-error-wrapper').show();
      cache.get('#wpstg-error-details').show().html(message);
      cache.get('#wpstg-removing-clone').removeClass('loading');
      cache.get('.wpstg-loader').hide();
      $('.wpstg--modal--process--generic-problem').show().html(message);
    };
    /**
     * Show warning during cloning or push process when closing tab or browser, or changing page
     * @param {beforeunload} event
     * @return {null}
     */


    that.warnIfClosingDuringProcess = function (event) {
      // Only some browsers show the message below, most say something like "Changes you made may not be saved" (Chrome) or "You have unsaved changes. Exit?"
      event.returnValue = 'You MUST leave this window open while cloning/pushing. Please wait...';
      return null;
    };
    /**
       *
       * @param response the error object
       * @param prependMessage Overwrite default error message at beginning
       * @param appendMessage Overwrite default error message at end
       * @returns void
       */


    var showAjaxFatalError = function showAjaxFatalError(response, prependMessage, appendMessage) {
      prependMessage = prependMessage ? prependMessage + '<br/><br/>' : 'Something went wrong! <br/><br/>';
      appendMessage = appendMessage ? appendMessage + '<br/><br/>' : '<br/><br/>Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.';

      if (response === false) {
        showError(prependMessage + ' Error: No response.' + appendMessage);
        window.removeEventListener('beforeunload', WPStaging$1.warnIfClosingDuringProcess);
        return;
      }

      if (typeof response.error !== 'undefined' && response.error) {
        console.error(response.message);
        showError(prependMessage + ' Error: ' + response.message + appendMessage);
        window.removeEventListener('beforeunload', WPStaging$1.warnIfClosingDuringProcess);
        return;
      }
    };
    /** Hide and reset previous thrown visible errors */


    var resetErrors = function resetErrors() {
      cache.get('#wpstg-error-details').hide().html('');
    };
    /**
       * Common Elements
       */


    var elements = function elements() {
      var $workFlow = cache.get('#wpstg-workflow');
      var urlSpinner = ajaxurl.replace('/admin-ajax.php', '') + '/images/spinner';
      var timer;

      if (2 < window.devicePixelRatio) {
        urlSpinner += '-2x';
      }

      urlSpinner += '.gif';
      ajaxSpinner = '<img src=\'\'' + urlSpinner + '\' alt=\'\' class=\'ajax-spinner general-spinner\' />';

      $workFlow // Check / Un-check All Database Tables New
      .on('click', '.wpstg-button-unselect', function (e) {
        e.preventDefault();

        if (false === that.areAllTablesChecked) {
          cache.get('#wpstg_select_tables_cloning .wpstg-db-table').prop('selected', 'selected');
          cache.get('.wpstg-button-unselect').text('Unselect All');
          cache.get('.wpstg-db-table-checkboxes').prop('checked', true);
          that.areAllTablesChecked = true;
        } else {
          cache.get('#wpstg_select_tables_cloning .wpstg-db-table').prop('selected', false);
          cache.get('.wpstg-button-unselect').text('Select All');
          cache.get('.wpstg-db-table-checkboxes').prop('checked', false);
          that.areAllTablesChecked = false;
        }
      })
      /**
               * Select tables with certain tbl prefix | NEW
               * @param obj e
               * @returns {undefined}
               */
      .on('click', '.wpstg-button-select', function (e) {
        e.preventDefault();
        $('#wpstg_select_tables_cloning .wpstg-db-table').each(function () {
          if (wpstg.isMultisite == 1) {
            if ($(this).attr('name').match('^' + wpstg.tblprefix + '([^0-9])_*')) {
              $(this).prop('selected', 'selected');
            } else {
              $(this).prop('selected', false);
            }
          }

          if (wpstg.isMultisite == 0) {
            if ($(this).attr('name').match('^' + wpstg.tblprefix)) {
              $(this).prop('selected', 'selected');
            } else {
              $(this).prop('selected', false);
            }
          }
        });
      }) // Expand Directories
      .on('click', '.wpstg-expand-dirs', function (e) {
        e.preventDefault();
        var $this = $(this);
        $this.siblings('.wpstg-subdir').slideToggle();
      }) // When a directory checkbox is Selected
      .on('change', 'input.wpstg-check-dir', function () {
        var $directory = $(this).parent('.wpstg-dir');

        if (this.checked) {
          $directory.parents('.wpstg-dir').children('.wpstg-check-dir').prop('checked', true);
          $directory.find('.wpstg-expand-dirs').removeClass('disabled');
          $directory.find('.wpstg-subdir .wpstg-check-dir').prop('checked', true);
        } else {
          $directory.find('.wpstg-dir .wpstg-check-dir').prop('checked', false);
          $directory.find('.wpstg-expand-dirs, .wpstg-check-subdirs').addClass('disabled');
          $directory.find('.wpstg-check-subdirs').data('action', 'check').text('check');
        }
      }) // When a directory name is Selected
      .on('change', 'href.wpstg-check-dir', function () {
        var $directory = $(this).parent('.wpstg-dir');

        if (this.checked) {
          $directory.parents('.wpstg-dir').children('.wpstg-check-dir').prop('checked', true);
          $directory.find('.wpstg-expand-dirs').removeClass('disabled');
          $directory.find('.wpstg-subdir .wpstg-check-dir').prop('checked', true);
        } else {
          $directory.find('.wpstg-dir .wpstg-check-dir').prop('checked', false);
          $directory.find('.wpstg-expand-dirs, .wpstg-check-subdirs').addClass('disabled');
          $directory.find('.wpstg-check-subdirs').data('action', 'check').text('check');
        }
      }) // Check the max length of the clone name and if the clone name already exists
      .on('keyup', '#wpstg-new-clone-id', function () {
        // Hide previous errors
        document.getElementById('wpstg-error-details').style.display = 'none'; // This request was already sent, clear it up!

        if ('number' === typeof timer) {
          clearInterval(timer);
        } // Early bail if site name is empty


        if (this.value === undefined || this.value === '') {
          cache.get('#wpstg-new-clone-id').removeClass('wpstg-error-input');
          cache.get('#wpstg-start-cloning').removeAttr('disabled');
          cache.get('#wpstg-clone-id-error').text('').hide();
          return;
        } // Convert the site name to directory name (slugify the site name to create directory name)


        var cloneDirectoryName = WPStagingCommon.slugify(this.value);
        timer = setTimeout(function () {
          ajax({
            action: 'wpstg_check_clone',
            accessToken: wpstg.accessToken,
            nonce: wpstg.nonce,
            directoryName: cloneDirectoryName
          }, function (response) {
            if (response.status === 'success') {
              cache.get('#wpstg-new-clone-id').removeClass('wpstg-error-input');
              cache.get('#wpstg-start-cloning').removeAttr('disabled');
              cache.get('#wpstg-clone-id-error').text('').hide();
            } else {
              cache.get('#wpstg-new-clone-id').addClass('wpstg-error-input');
              cache.get('#wpstg-start-cloning').prop('disabled', true);
              cache.get('#wpstg-clone-id-error').text(response.message).show();
            }
          });
        }, 500);
      }) // Restart cloning process
      .on('click', '#wpstg-start-cloning', function () {
        resetErrors();
        that.isCancelled = false;
        that.getLogs = false;
        that.progressBar = 0;
      }).on('input', '#wpstg-new-clone-id', function () {
        if ($('#wpstg-clone-directory').length < 1) {
          return;
        }

        var slug = WPStagingCommon.slugify(this.value);
        var $targetDir = $('#wpstg-use-target-dir');
        var $targetUri = $('#wpstg-use-target-hostname');
        var path = $targetDir.data('base-path');
        var uri = $targetUri.data('base-uri');

        if (path) {
          path = path.replace(/\/+$/g, '') + '/' + slug + '/';
        }

        if (uri) {
          uri = uri.replace(/\/+$/g, '') + '/' + slug;
        }

        $('.wpstg-use-target-dir--value').text(path);
        $('.wpstg-use-target-hostname--value').text(uri);
        $targetDir.attr('data-path', path);
        $targetUri.attr('data-uri', uri);
        $('#wpstg_clone_dir').attr('placeholder', path);
        $('#wpstg_clone_hostname').attr('placeholder', uri);
      }).on('input', '#wpstg_clone_hostname', function () {
        if ($(this).val() === '' || validateTargetHost()) {
          $('#wpstg_clone_hostname_error').remove();
          return;
        }

        if (!validateTargetHost() && !$('#wpstg_clone_hostname_error').length) {
          $('#wpstg-clone-directory tr:last-of-type').after('<tr><td>&nbsp;</td><td><p id="wpstg_clone_hostname_error" style="color: red;">&nbsp;Invalid host name. Please provide it in a format like http://example.com</p></td></tr>');
        }
      });
      cloneActions();
    };
    /* @returns {boolean} */


    var validateTargetHost = function validateTargetHost() {
      var the_domain = $('#wpstg_clone_hostname').val();

      if (the_domain === '') {
        return true;
      }

      var reg = /^http(s)?:\/\/.*$/;

      if (reg.test(the_domain) === false) {
        return false;
      }

      return true;
    };
    /**
       * Clone actions
       */


    var cloneActions = function cloneActions() {
      var $workFlow = cache.get('#wpstg-workflow');
      $workFlow // Cancel cloning
      .on('click', '#wpstg-cancel-cloning', function () {
        if (!confirm('Are you sure you want to cancel cloning process?')) {
          return false;
        }

        var $this = $(this);
        $('#wpstg-try-again, #wpstg-home-link').hide();
        $this.prop('disabled', true);
        that.isCancelled = true;
        that.progressBar = 0;
        $('#wpstg-processing-status').text('Please wait...this can take up a while.');
        $('.wpstg-loader, #wpstg-show-log-button').hide();
        $this.parent().append(ajaxSpinner);
        cancelCloning();
      }) // Resume cloning
      .on('click', '#wpstg-resume-cloning', function () {
        resetErrors();
        var $this = $(this);
        $('#wpstg-try-again, #wpstg-home-link').hide();
        that.isCancelled = false;
        $('#wpstg-processing-status').text('Try to resume cloning process...');
        $('#wpstg-error-details').hide();
        $('.wpstg-loader').show();
        $this.parent().append(ajaxSpinner);
        that.startCloning();
      }) // Cancel update cloning
      .on('click', '#wpstg-cancel-cloning-update', function () {
        resetErrors();
        var $this = $(this);
        $('#wpstg-try-again, #wpstg-home-link').hide();
        $this.prop('disabled', true);
        that.isCancelled = true;
        $('#wpstg-cloning-result').text('Please wait...this can take up a while.');
        $('.wpstg-loader, #wpstg-show-log-button').hide();
        $this.parent().append(ajaxSpinner);
        cancelCloningUpdate();
      }) // Restart cloning
      .on('click', '#wpstg-restart-cloning', function () {
        resetErrors();
        var $this = $(this);
        $('#wpstg-try-again, #wpstg-home-link').hide();
        $this.prop('disabled', true);
        that.isCancelled = true;
        $('#wpstg-cloning-result').text('Please wait...this can take up a while.');
        $('.wpstg-loader, #wpstg-show-log-button').hide();
        $this.parent().append(ajaxSpinner);
        restart();
      }) // Delete clone - confirmation
      .on('click', '.wpstg-remove-clone[data-clone]', function (e) {
        resetErrors();
        e.preventDefault();
        var $existingClones = cache.get('#wpstg-existing-clones');
        $workFlow.removeClass('active');
        cache.get('.wpstg-loader').show();
        ajax({
          action: 'wpstg_confirm_delete_clone',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
          clone: $(this).data('clone')
        }, function (response) {
          cache.get('#wpstg-removing-clone').html(response);
          $existingClones.children('img').remove();
          cache.get('.wpstg-loader').hide();
          $('html, body').animate({
            // This logic is meant to be a "scrollBottom"
            scrollTop: $('#wpstg-remove-clone').offset().top - $(window).height() + $('#wpstg-remove-clone').height() + 50
          }, 100);
        }, 'HTML');
      }) // Delete clone - confirmed
      .on('click', '#wpstg-remove-clone', function (e) {
        resetErrors();
        e.preventDefault();
        cache.get('#wpstg-removing-clone').addClass('loading');
        cache.get('.wpstg-loader').show();
        deleteClone($(this).data('clone'));
      }) // Cancel deleting clone
      .on('click', '#wpstg-cancel-removing', function (e) {
        e.preventDefault();
        $('.wpstg-clone').removeClass('active');
        cache.get('#wpstg-removing-clone').html('');
      }) // Update
      .on('click', '.wpstg-execute-clone', function (e) {
        e.preventDefault();
        var clone = $(this).data('clone');
        $workFlow.addClass('loading');
        that.cloneExcludeFilters = null;
        ajax({
          action: 'wpstg_scanning',
          clone: clone,
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce
        }, function (response) {
          if (response.length < 1) {
            showError('Something went wrong! Error: No response.  Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.');
          }

          var jsonResponse = tryParseJson(response);

          if (jsonResponse !== false && jsonResponse.success === false) {
            $workFlow.removeClass('loading');
            showErrorModal(jsonResponse);
            return;
          }

          $workFlow.removeClass('loading').html(response); // register check disk space function for clone update process.

          checkDiskSpace();
          that.directoryNavigator = new WpstgDirectoryNavigation('#wpstg-directories-listing', wpstg, that.notyf);
          that.cloneExcludeFilters = new WpstgExcludeFilters();
          that.switchStep(2);
        }, 'HTML');
      }) // Reset Clone
      .on('click', '.wpstg-reset-clone', function (e) {
        e.preventDefault();
        var clone = $(this).data('clone');
        var resetModal = new WpstgResetModal(clone);
        var promise = resetModal.showModal();
        that.areAllTablesChecked = true;
        promise.then(function (result) {
          if (result.value) {
            var dirNavigator = resetModal.getDirectoryNavigator();
            var exclFilters = resetModal.getExcludeFilters().getExcludeFilters();
            resetClone(clone, {
              includedTables: getIncludedTables(),
              excludeSizeRules: encodeURIComponent(exclFilters.sizes),
              excludeGlobRules: encodeURIComponent(exclFilters.globs),
              excludedDirectories: dirNavigator.getExcludedDirectories(),
              extraDirectories: dirNavigator.getExtraDirectoriesRootOnly()
            });
          }
        });
        return;
      });
    };
    /**
     * Ajax Requests
     * @param Object data
     * @param Function callback
     * @param string dataType
     * @param bool showErrors
     * @param int tryCount
     * @param float incrementRatio
     */


    var ajax = function ajax(data, callback, dataType, showErrors, tryCount, incrementRatio) {
      if (incrementRatio === void 0) {
        incrementRatio = null;
      }

      if ('undefined' === typeof dataType) {
        dataType = 'json';
      }

      if (false !== showErrors) {
        showErrors = true;
      }

      tryCount = 'undefined' === typeof tryCount ? 0 : tryCount;
      var retryLimit = 10;
      var retryTimeout = 10000 * tryCount;
      incrementRatio = parseInt(incrementRatio);

      if (!isNaN(incrementRatio)) {
        retryTimeout *= incrementRatio;
      }

      $.ajax({
        url: ajaxurl + '?action=wpstg_processing&_=' + Date.now() / 1000,
        type: 'POST',
        dataType: dataType,
        cache: false,
        data: data,
        error: function error(xhr, textStatus, errorThrown) {
          // try again after 10 seconds
          tryCount++;

          if (tryCount <= retryLimit) {
            setTimeout(function () {
              ajax(data, callback, dataType, showErrors, tryCount, incrementRatio);
              return;
            }, retryTimeout);
          } else {
            var errorCode = 'undefined' === typeof xhr.status ? 'Unknown' : xhr.status;
            showError('Fatal Error:  ' + errorCode + ' Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.');
          }
        },
        success: function success(data) {
          if ('function' === typeof callback) {
            callback(data);
          }
        },
        statusCode: {
          404: function _() {
            if (tryCount >= retryLimit) {
              showError('Error 404 - Can\'t find ajax request URL! Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.');
            }
          },
          500: function _() {
            if (tryCount >= retryLimit) {
              showError('Fatal Error 500 - Internal server error while processing the request! Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.');
            }
          },
          504: function _() {
            if (tryCount > retryLimit) {
              showError('Error 504 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ');
            }
          },
          502: function _() {
            if (tryCount >= retryLimit) {
              showError('Error 502 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ');
            }
          },
          503: function _() {
            if (tryCount >= retryLimit) {
              showError('Error 503 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ');
            }
          },
          429: function _() {
            if (tryCount >= retryLimit) {
              showError('Error 429 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ');
            }
          },
          403: function _() {
            if (tryCount >= retryLimit) {
              showError('Refresh page or login again! The process should be finished successfully. \n\ ');
            }
          }
        }
      });
    };
    /**
     * Next / Previous Step Clicks to Navigate Through Staging Job
     */


    var stepButtons = function stepButtons() {
      var $workFlow = cache.get('#wpstg-workflow');
      $workFlow // Next Button
      .on('click', '.wpstg-next-step-link', function (e) {
        e.preventDefault();
        var $this = $(this);

        if ($('#wpstg_clone_hostname').length && !validateTargetHost()) {
          $('#wpstg_clone_hostname').focus();
          return false;
        }

        if ($this.data('action') === 'wpstg_update' || $this.data('action') === 'wpstg_reset') {
          // Update / Reset Clone - confirmed
          var onlyUpdateMessage = '';

          if ($this.data('action') === 'wpstg_update') {
            onlyUpdateMessage = ' \n\nExclude all tables and folders you do not want to overwrite, first! \n\nDo not cancel the updating process! This can break your staging site. \n\n\Create a backup of your staging website before you proceed.';
          }

          if (!confirm('STOP! This will overwrite your staging site with all selected data from the production site! This should be used only if you want to clone again your production site. Are you sure you want to do this?' + onlyUpdateMessage)) {
            return false;
          }
        } // Button is disabled


        if ($this.attr('disabled')) {
          return false;
        }

        if ($this.data('action') === 'wpstg_cloning') {
          // Verify External Database If Checked and Not Skipped
          if ($('#wpstg-ext-db').is(':checked')) {
            verifyExternalDatabase($this, $workFlow);
            return;
          }
        }

        proceedCloning($this, $workFlow);
      }) // Previous Button
      .on('click', '.wpstg-prev-step-link', function (e) {
        e.preventDefault();
        cache.get('.wpstg-loader').removeClass('wpstg-finished');
        cache.get('.wpstg-loader').hide();
        loadOverview();
      });
    };
    /**
     * Get Included (Checked) Database Tables
     * @return {Array}
     */


    var getIncludedTables = function getIncludedTables() {
      var includedTables = [];
      $('#wpstg_select_tables_cloning option:selected').each(function () {
        includedTables.push(this.value);
      });
      return includedTables;
    };
    /**
     * Get Excluded (Unchecked) Database Tables
     * Not used anymore!
     * @return {Array}
     */


    var getExcludedTables = function getExcludedTables() {
      var excludedTables = [];
      $('.wpstg-db-table input:not(:checked)').each(function () {
        excludedTables.push(this.name);
      });
      return excludedTables;
    };
    /**
     * Verify External Database for Cloning
     */


    var verifyExternalDatabase = function verifyExternalDatabase($this, workflow) {
      cache.get('.wpstg-loader').show();
      ajax({
        action: 'wpstg_database_verification',
        accessToken: wpstg.accessToken,
        nonce: wpstg.nonce,
        databaseUser: cache.get('#wpstg_db_username').val(),
        databasePassword: cache.get('#wpstg_db_password').val(),
        databaseServer: cache.get('#wpstg_db_server').val(),
        databaseDatabase: cache.get('#wpstg_db_database').val()
      }, function (response) {
        // Undefined Error
        if (false === response) {
          showError('Something went wrong! Error: No response.' + 'Please try again. If that does not help, ' + '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a> ');
          cache.get('.wpstg-loader').hide();
          return;
        } // Throw Error


        if ('undefined' === typeof response.success) {
          showError('Something went wrong! Error: Invalid response.' + 'Please try again. If that does not help, ' + '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a> ');
          cache.get('.wpstg-loader').hide();
          return;
        }

        if (response.success) {
          cache.get('.wpstg-loader').hide();
          proceedCloning($this, workflow);
          return;
        }

        if (response.error_type === 'comparison') {
          cache.get('.wpstg-loader').hide();
          var render = '<table style="width: 100%;"><thead><tr><th>Property</th><th>Production DB</th><th>Staging DB</th><th>Status</th></tr></thead><tbody>';
          response.checks.forEach(function (x) {
            var icon = '<i style="color: #00ff00">✔</i>';

            if (x.production !== x.staging) {
              icon = '<i style="color: #ff0000">❌</i>';
            }

            render += '<tr><td>' + x.name + '</td><td>' + x.production + '</td><td>' + x.staging + '</td><td>' + icon + '</td></tr>';
          });
          render += '</tbody></table><p>Note: Some mySQL properties do not match. You may proceed but the staging site may not work as expected.</p>';
          WPStagingCommon.getSwalModal().fire({
            title: 'Different Database Properties',
            icon: 'warning',
            html: render,
            width: '650px',
            focusConfirm: false,
            confirmButtonText: 'Proceed Anyway',
            showCancelButton: true
          }).then(function (result) {
            if (result.value) {
              proceedCloning($this, workflow);
            }
          });
          return;
        }

        WPStagingCommon.getSwalModal(true).fire({
          title: 'Different Database Properties',
          icon: 'error',
          html: response.message,
          focusConfirm: true,
          confirmButtonText: 'Ok',
          showCancelButton: false
        });
        cache.get('.wpstg-loader').hide();
      }, 'json', false);
    };
    /**
     * Get Cloning Step Data
     */


    var getCloningData = function getCloningData() {
      if ('wpstg_cloning' !== that.data.action && 'wpstg_update' !== that.data.action && 'wpstg_reset' !== that.data.action) {
        return;
      }

      that.data.cloneID = new Date().getTime().toString();

      if ('wpstg_update' === that.data.action) {
        that.data.cloneID = $('#wpstg-new-clone-id').data('clone');
      }

      that.data.cloneName = $('#wpstg-new-clone-id').val() || that.data.cloneID; // Remove this to keep &_POST[] small otherwise mod_security will throw error 404
      // that.data.excludedTables = getExcludedTables();

      if (that.directoryNavigator !== null) {
        that.data.excludedDirectories = encodeURIComponent(that.directoryNavigator.getExcludedDirectories());
        that.data.extraDirectories = encodeURIComponent(that.directoryNavigator.getExtraDirectoriesRootOnly());
      }

      that.data.excludeGlobRules = '';
      that.data.excludeSizeRules = '';

      if (that.cloneExcludeFilters instanceof WpstgExcludeFilters) {
        var rules = that.cloneExcludeFilters.getExcludeFilters();
        that.data.excludeGlobRules = encodeURIComponent(rules.globs);
        that.data.excludeSizeRules = encodeURIComponent(rules.sizes);
      }

      that.data.includedTables = getIncludedTables();
      that.data.databaseServer = $('#wpstg_db_server').val();
      that.data.databaseUser = $('#wpstg_db_username').val();
      that.data.databasePassword = $('#wpstg_db_password').val();
      that.data.databaseDatabase = $('#wpstg_db_database').val();
      that.data.databasePrefix = $('#wpstg_db_prefix').val();
      var cloneDir = $('#wpstg_clone_dir').val();
      that.data.cloneDir = encodeURIComponent($.trim(cloneDir));
      that.data.cloneHostname = $('#wpstg_clone_hostname').val();
      that.data.emailsAllowed = $('#wpstg_allow_emails').is(':checked');
      that.data.uploadsSymlinked = $('#wpstg_symlink_upload').is(':checked');
      that.data.cleanPluginsThemes = $('#wpstg-clean-plugins-themes').is(':checked');
      that.data.cleanUploadsDir = $('#wpstg-clean-uploads').is(':checked');
    };

    var proceedCloning = function proceedCloning($this, workflow) {
      // Add loading overlay
      workflow.addClass('loading'); // Prepare data

      that.data = {
        action: $this.data('action'),
        accessToken: wpstg.accessToken,
        nonce: wpstg.nonce
      }; // Cloning data

      getCloningData();
      sendCloningAjax(workflow);
    };

    var sendCloningAjax = function sendCloningAjax(workflow) {
      // Send ajax request
      ajax(that.data, function (response) {
        // Undefined Error
        if (false === response) {
          showError('Something went wrong!<br/><br/> Go to WP Staging > Settings and lower \'File Copy Limit\' and \'DB Query Limit\'. Also set \'CPU Load Priority to low \'' + 'and try again. If that does not help, ' + '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a> ');
        }

        if (response.length < 1) {
          showError('Something went wrong! No response.  Go to WP Staging > Settings and lower \'File Copy Limit\' and \'DB Query Limit\'. Also set \'CPU Load Priority to low \'' + 'and try again. If that does not help, ' + '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a> ');
        }

        var jsonResponse = tryParseJson(response);

        if (jsonResponse !== false && jsonResponse.success === false) {
          workflow.removeClass('loading');
          showErrorModal(jsonResponse);
          return;
        } // Styling of elements


        workflow.removeClass('loading').html(response);
        that.cloneExcludeFilters = null;

        if (that.data.action === 'wpstg_scanning') {
          that.areAllTablesChecked = true;
          that.directoryNavigator = new WpstgDirectoryNavigation('#wpstg-directories-listing', wpstg, that.notyf);
          that.switchStep(2);
          that.cloneExcludeFilters = new WpstgExcludeFilters();
        } else if (that.data.action === 'wpstg_cloning' || that.data.action === 'wpstg_update' || that.data.action === 'wpstg_reset') {
          that.switchStep(3);
        } // Start cloning


        that.startCloning();
      }, 'HTML');
    };

    var showErrorModal = function showErrorModal(response) {
      var errorModal = new WpstgModal('wpstg_modal_error', wpstg);
      errorModal.show(Object.assign({
        title: 'Error',
        icon: 'error',
        html: wpstg.i18n['somethingWentWrong'],
        width: '500px',
        confirmButtonText: 'Ok',
        showCancelButton: false,
        customClass: {
          confirmButton: 'wpstg--btn--confirm wpstg-blue-primary wpstg-button wpstg-link-btn',
          cancelButton: 'wpstg--btn--cancel wpstg-blue-primary wpstg-link-btn',
          actions: 'wpstg--modal--actions',
          popup: 'wpstg-swal-popup centered-modal'
        },
        buttonsStyling: false,
        reverseButtons: true
      }, response.swalOptions), {
        type: response.type
      });
    };

    var tryParseJson = function tryParseJson(json) {
      // early bail if not string
      if (!json) {
        return false;
      }

      try {
        var object = JSON.parse(json);

        if (object && typeof object === 'object') {
          return object;
        }
      } catch (e) {// do nothing on catch
      }

      return false;
    };

    var resetClone = function resetClone(clone, excludeOptions) {
      that.data = {
        action: 'wpstg_reset',
        accessToken: wpstg.accessToken,
        nonce: wpstg.nonce,
        cloneID: clone
      };
      that.data = _extends({}, that.data, excludeOptions);
      var $workFlow = cache.get('#wpstg-workflow');
      sendCloningAjax($workFlow);
    };
    /**
     * Loads Overview (first step) of Staging Job
     */


    var loadOverview = function loadOverview() {
      var $workFlow = cache.get('#wpstg-workflow');
      $workFlow.addClass('loading');
      ajax({
        action: 'wpstg_overview',
        accessToken: wpstg.accessToken,
        nonce: wpstg.nonce
      }, function (response) {
        if (response.length < 1) {
          showError('Something went wrong! No response. Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report.');
        }

        cache.get('.wpstg-current-step'); // Styling of elements

        $workFlow.removeClass('loading').html(response);
      }, 'HTML');
      that.switchStep(1);
      cache.get('.wpstg-step3-cloning').show();
      cache.get('.wpstg-step3-pushing').hide();
    };
    /**
     * Load Tabs
     */


    var tabs = function tabs() {
      cache.get('#wpstg-workflow').on('click', '.wpstg-tab-header', function (e) {
        e.preventDefault();
        var $this = $(this);
        var $section = cache.get($this.data('id'));
        $this.toggleClass('expand');
        $section.slideToggle();
        var tabTriangle = $this.find('.wpstg-tab-triangle');

        if ($this.hasClass('expand')) {
          tabTriangle.removeClass('wpstg-no-icon');
          tabTriangle.text('');
          tabTriangle.addClass('wpstg-rotate-90');
        } else {
          tabTriangle.removeClass('wpstg-rotate-90');
        }
      });
    };
    /**
     * Delete Clone
     * @param {String} clone
     */


    var deleteClone = function deleteClone(clone) {
      var deleteDir = $('#deleteDirectory:checked').data('deletepath');
      ajax({
        action: 'wpstg_delete_clone',
        clone: clone,
        accessToken: wpstg.accessToken,
        nonce: wpstg.nonce,
        excludedTables: getExcludedTables(),
        deleteDir: deleteDir
      }, function (response) {
        if (response) {
          showAjaxFatalError(response); // Finished

          if ('undefined' !== typeof response["delete"] && (response["delete"] === 'finished' || response["delete"] === 'unfinished')) {
            cache.get('#wpstg-removing-clone').removeClass('loading').html('');

            if (response["delete"] === 'finished' && response.error === undefined) {
              $('.wpstg-clone[data-clone-id="' + clone + '"]').remove();
            } // No staging site message is also of type/class .wpstg-class but hidden
            // We have just excluded that from search when counting no of clones


            if ($('#wpstg-existing-clones .wpstg-clone').length < 1) {
              cache.get('#wpstg-existing-clones').find('h3').text('');
              cache.get('#wpstg-no-staging-site-results').show();
            }

            cache.get('.wpstg-loader').hide();
            return;
          }
        } // continue


        if (true !== response) {
          deleteClone(clone);
          return;
        }
      });
    };
    /**
     * Cancel Cloning Process
     */


    var cancelCloning = function cancelCloning() {
      that.timer('stop');

      if (true === that.isFinished) {
        return true;
      }

      ajax({
        action: 'wpstg_cancel_clone',
        clone: that.data.cloneID,
        accessToken: wpstg.accessToken,
        nonce: wpstg.nonce
      }, function (response) {
        if (response && 'undefined' !== typeof response["delete"] && response["delete"] === 'finished') {
          cache.get('.wpstg-loader').hide(); // Load overview

          loadOverview();
          return;
        }

        if (true !== response) {
          // continue
          cancelCloning();
          return;
        } // Load overview


        loadOverview();
      });
    };
    /**
     * Cancel Cloning Process
     */


    var cancelCloningUpdate = function cancelCloningUpdate() {
      if (true === that.isFinished) {
        return true;
      }

      ajax({
        action: 'wpstg_cancel_update',
        clone: that.data.cloneID,
        accessToken: wpstg.accessToken,
        nonce: wpstg.nonce
      }, function (response) {
        if (response && 'undefined' !== typeof response["delete"] && response["delete"] === 'finished') {
          // Load overview
          loadOverview();
          return;
        }

        if (true !== response) {
          // continue
          cancelCloningUpdate();
          return;
        } // Load overview


        loadOverview();
      });
    };
    /**
     * Cancel Cloning Process
     */


    var restart = function restart() {
      if (true === that.isFinished) {
        return true;
      }

      ajax({
        action: 'wpstg_restart',
        // clone: that.data.cloneID,
        accessToken: wpstg.accessToken,
        nonce: wpstg.nonce
      }, function (response) {
        if (response && 'undefined' !== typeof response["delete"] && response["delete"] === 'finished') {
          // Load overview
          loadOverview();
          return;
        }

        if (true !== response) {
          // continue
          cancelCloningUpdate();
          return;
        } // Load overview


        loadOverview();
      });
    };
    /**
     * Scroll the window log to bottom
     * @return void
     */


    var logscroll = function logscroll() {
      var $div = cache.get('.wpstg-log-details');

      if ('undefined' !== typeof $div[0]) {
        $div.scrollTop($div[0].scrollHeight);
      }
    };
    /**
     * Append the log to the logging window
     * @param string log
     * @return void
     */


    var getLogs = function getLogs(log) {
      if (log != null && 'undefined' !== typeof log) {
        if (log.constructor === Array) {
          $.each(log, function (index, value) {
            if (value === null) {
              return;
            }

            if (value.type === 'ERROR') {
              cache.get('.wpstg-log-details').append('<span class="wpstg--red">[' + value.type + ']</span>-' + '[' + value.date + '] ' + value.message + '</br>');
            } else {
              cache.get('.wpstg-log-details').append('[' + value.type + ']-' + '[' + value.date + '] ' + value.message + '</br>');
            }
          });
        } else {
          cache.get('.wpstg-log-details').append('[' + log.type + ']-' + '[' + log.date + '] ' + log.message + '</br>');
        }
      }

      logscroll();
    };
    /**
     * Check diskspace
     * @return string json
     */


    var checkDiskSpace = function checkDiskSpace() {
      cache.get('#wpstg-check-space').on('click', function (e) {
        cache.get('.wpstg-loader').show();
        var excludedDirectories = encodeURIComponent(that.directoryNavigator.getExcludedDirectories());
        var extraDirectories = encodeURIComponent(that.directoryNavigator.getExtraDirectoriesRootOnly());
        ajax({
          action: 'wpstg_check_disk_space',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
          excludedDirectories: excludedDirectories,
          extraDirectories: extraDirectories
        }, function (response) {
          if (false === response) {
            cache.get('#wpstg-clone-id-error').text('Can not detect required disk space').show();
            cache.get('.wpstg-loader').hide();
            return;
          } // Show required disk space


          cache.get('#wpstg-clone-id-error').html('Estimated necessary disk space: ' + response.requiredSpace + (response.errorMessage !== null ? '<br>' + response.errorMessage : '') + '<br> <span style="color:#444;">Before you proceed ensure your account has enough free disk space to hold the entire instance of the production site. You can check the available space from your hosting account (cPanel or similar).</span>').show();
          cache.get('.wpstg-loader').hide();
        }, 'json', false);
      });
    };
    /**
     * Show or hide animated loading icon
     * @param isLoading bool
     */


    var isLoading = function isLoading(_isLoading) {
      if (!_isLoading || _isLoading === false) {
        cache.get('.wpstg-loader').hide();
      } else {
        cache.get('.wpstg-loader').show();
      }
    };
    /**
     * Count up processing execution time
     * @param string status
     * @return html
     */


    that.timer = function (status) {
      if (status === 'stop') {
        var time = that.time;
        that.time = 1;
        clearInterval(that.executionTime);
        return that.convertSeconds(time);
      }

      that.executionTime = setInterval(function () {
        if (null !== document.getElementById('wpstg-processing-timer')) {
          document.getElementById('wpstg-processing-timer').innerHTML = 'Elapsed Time: ' + that.convertSeconds(that.time);
        }

        that.time++;

        if (status === 'stop') {
          that.time = 1;
          clearInterval(that.executionTime);
        }
      }, 1000);
    };
    /**
     * Convert seconds to hourly format
     * @param int seconds
     * @return string
     */


    that.convertSeconds = function (seconds) {
      var date = new Date(null);
      date.setSeconds(seconds); // specify value for SECONDS here

      return date.toISOString().substr(11, 8);
    };
    /**
     * Start Cloning Process
     * @type {Function}
     */


    that.startCloning = function () {
      resetErrors(); // Register function for checking disk space

      checkDiskSpace();

      if ('wpstg_cloning' !== that.data.action && 'wpstg_update' !== that.data.action && 'wpstg_reset' !== that.data.action) {
        return;
      }

      that.isCancelled = false; // Start the process

      start(); // Functions
      // Start

      function start() {
        cache.get('.wpstg-loader').show();
        cache.get('#wpstg-cancel-cloning').text('Cancel');
        cache.get('#wpstg-resume-cloning').hide();
        cache.get('#wpstg-error-details').hide(); // Clone Database

        setTimeout(function () {
          // cloneDatabase();
          window.addEventListener('beforeunload', WPStaging$1.warnIfClosingDuringProcess);
          processing();
        }, wpstg.delayReq);
        that.timer('start');
      }
      /**
       * Start ajax processing
       * @return string
       */


      var processing = function processing() {
        if (true === that.isCancelled) {
          window.removeEventListener('beforeunload', WPStaging$1.warnIfClosingDuringProcess);
          return false;
        }

        isLoading(true);
        var excludedDirectories = '';
        var extraDirectories = '';

        if (that.directoryNavigator !== null) {
          excludedDirectories = that.directoryNavigator.getExcludedDirectories();
          extraDirectories = that.directoryNavigator.getExtraDirectoriesRootOnly();
        } // Show logging window


        cache.get('.wpstg-log-details').show();
        WPStaging$1.ajax({
          action: 'wpstg_processing',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
          excludedTables: getExcludedTables(),
          excludedDirectories: encodeURIComponent(excludedDirectories),
          extraDirectories: encodeURIComponent(extraDirectories)
        }, function (response) {
          showAjaxFatalError(response); // Add Log messages

          if ('undefined' !== typeof response.last_msg && response.last_msg) {
            getLogs(response.last_msg);
          } // Continue processing


          if (false === response.status) {
            progressBar(response);
            setTimeout(function () {
              cache.get('.wpstg-loader').show();
              processing();
            }, wpstg.delayReq);
          } else if (true === response.status && 'finished' !== response.status) {
            cache.get('#wpstg-error-details').hide();
            cache.get('#wpstg-error-wrapper').hide();
            progressBar(response);
            processing();
          } else if ('finished' === response.status || 'undefined' !== typeof response.job_done && response.job_done) {
            window.removeEventListener('beforeunload', WPStaging$1.warnIfClosingDuringProcess);
            finish(response);
          }
        }, 'json', false);
      }; // Finish


      function finish(response) {
        if (true === that.getLogs) {
          getLogs();
        }

        progressBar(response); // Add Log

        if ('undefined' !== typeof response.last_msg) {
          getLogs(response.last_msg);
        }

        cache.get('.wpstg-loader').hide();
        cache.get('#wpstg-processing-header').html('Processing Complete');
        $('#wpstg-processing-status').text('Succesfully finished');
        cache.get('#wpstg_staging_name').html(that.data.cloneID);
        cache.get('#wpstg-finished-result').show();
        cache.get('#wpstg-cancel-cloning').hide();
        cache.get('#wpstg-resume-cloning').hide();
        cache.get('#wpstg-cancel-cloning-update').prop('disabled', true);
        var $link1 = cache.get('#wpstg-clone-url-1');
        var $link = cache.get('#wpstg-clone-url');
        $link1.attr('href', response.url);
        $link1.html(response.url);
        $link.attr('href', response.url);
        cache.get('#wpstg-remove-clone').data('clone', that.data.cloneID); // Finished

        that.isFinished = true;
        that.timer('stop');
        cache.get('.wpstg-loader').hide();
        cache.get('#wpstg-processing-header').html('Processing Complete'); // show alert

        var msg = wpstg.i18n.cloneResetComplete;

        if (that.data.action === 'wpstg_update') {
          msg = wpstg.i18n.cloneUpdateComplete;
        }

        if (that.data.action === 'wpstg_update' || that.data.action === 'wpstg_reset') {
          cache.get('#wpstg-cancel-cloning-update').hide();
          cache.get('.wpstg-prev-step-link').show();
          WPStagingCommon.getSwalModal(true, {
            confirmButton: 'wpstg--btn--confirm wpstg-green-button wpstg-button wpstg-link-btn wpstg-100-width'
          }).fire({
            title: '',
            icon: 'success',
            html: msg,
            width: '500px',
            focusConfirm: true
          });
        }

        return false;
      }
      /**
       * Add percentage progress bar
       * @param object response
       * @return {Boolean}
       */


      var progressBar = function progressBar(response, restart) {
        if ('undefined' === typeof response.percentage) {
          return false;
        }

        if (response.job === 'database') {
          cache.get('#wpstg-progress-db').width(response.percentage * 0.2 + '%').html(response.percentage + '%');
          cache.get('#wpstg-processing-status').html(response.percentage.toFixed(0) + '%' + ' - Step 1 of 4 Cloning Database Tables...');
        }

        if (response.job === 'SearchReplace') {
          cache.get('#wpstg-progress-db').css('background-color', '#3bc36b');
          cache.get('#wpstg-progress-db').html('1. Database'); // Assumption: All previous steps are done.
          // This avoids bugs where some steps are skipped and the progress bar is incomplete as a result

          cache.get('#wpstg-progress-db').width('20%');
          cache.get('#wpstg-progress-sr').width(response.percentage * 0.1 + '%').html(response.percentage + '%');
          cache.get('#wpstg-processing-status').html(response.percentage.toFixed(0) + '%' + ' - Step 2 of 4 Preparing Database Data...');
        }

        if (response.job === 'directories') {
          cache.get('#wpstg-progress-sr').css('background-color', '#3bc36b');
          cache.get('#wpstg-progress-sr').html('2. Data');
          cache.get('#wpstg-progress-sr').width('10%');
          cache.get('#wpstg-progress-dirs').width(response.percentage * 0.1 + '%').html(response.percentage + '%');
          cache.get('#wpstg-processing-status').html(response.percentage.toFixed(0) + '%' + ' - Step 3 of 4 Getting files...');
        }

        if (response.job === 'files') {
          cache.get('#wpstg-progress-dirs').css('background-color', '#3bc36b');
          cache.get('#wpstg-progress-dirs').html('3. Files');
          cache.get('#wpstg-progress-dirs').width('10%');
          cache.get('#wpstg-progress-files').width(response.percentage * 0.6 + '%').html(response.percentage + '%');
          cache.get('#wpstg-processing-status').html(response.percentage.toFixed(0) + '%' + ' - Step 4 of 4 Copy files...');
        }

        if (response.job === 'finish') {
          cache.get('#wpstg-progress-files').css('background-color', '#3bc36b');
          cache.get('#wpstg-progress-files').html('4. Copy Files');
          cache.get('#wpstg-progress-files').width('60%');
          cache.get('#wpstg-processing-status').html(response.percentage.toFixed(0) + '%' + ' - Cloning Process Finished');
        }
      };
    };

    that.switchStep = function (step) {
      cache.get('.wpstg-current-step').removeClass('wpstg-current-step');
      cache.get('.wpstg-step' + step).addClass('wpstg-current-step');
    };
    /**
     * Initiation
     * @type {Function}
     */


    that.init = function () {
      loadOverview();
      elements();
      stepButtons();
      tabs();
      WPStagingCommon.listenTooltip();
      new WpstgMainMenu();
      new WpstgCloneStaging();
      new WpstgCloningAdvanceSettings();
      that.notyf = new Notyf({
        duration: 10000,
        position: {
          x: 'center',
          y: 'bottom'
        },
        dismissible: true,
        types: [{
          type: 'warning',
          background: 'orange',
          icon: false
        }]
      });
    };
    /**
     * Ajax call
     * @type {ajax}
     */


    that.ajax = ajax;
    that.showError = showError;
    that.getLogs = getLogs;
    that.loadOverview = loadOverview;
    return that;
  }(jQuery);

  jQuery(document).ready(function () {
    WPStaging$1.init(); // This is necessary to make WPStaging var accessibile in WP Staging PRO js script

    window.WPStaging = WPStaging$1;
  });
  /**
   * Report Issue modal
   */

  jQuery(document).ready(function ($) {
    $('body').on('click', '#wpstg-report-issue-button', function (e) {
      console.log('REPORT');
      $('.wpstg--tab--active .wpstg-report-issue-form').toggleClass('wpstg-report-show');
      e.preventDefault();
    });
    $('body').on('click', '#wpstg-backups-report-issue-button', function (e) {
      $('.wpstg--tab--active .wpstg-report-issue-form').toggleClass('wpstg-report-show');
      e.preventDefault();
    });
    $('body').on('click', '#wpstg-report-cancel', function (e) {
      $('.wpstg--tab--active .wpstg-report-issue-form').removeClass('wpstg-report-show');
      e.preventDefault();
    });
    $('body').on('click', '.wpstg--tab--active #wpstg-report-submit', function (e) {
      var self = $(this);
      sendIssueReport(self, 'false');
      e.preventDefault();
    });
    /*
       * Close Success Modal
       */

    $('body').on('click', '#wpstg-success-button', function (e) {
      e.preventDefault();
      $('.wpstg-report-issue-form').removeClass('wpstg-report-show');
    });

    function sendIssueReport(button, forceSend) {
      if (forceSend === void 0) {
        forceSend = 'false';
      }

      var spinner = button.next();
      var email = $('.wpstg--tab--active .wpstg-report-email').val();
      var hosting_provider = $('.wpstg--tab--active .wpstg-report-hosting-provider').val();
      var message = $('.wpstg--tab--active .wpstg-report-description').val();
      var syslog = $('.wpstg--tab--active .wpstg-report-syslog').is(':checked');
      var terms = $('.wpstg--tab--active .wpstg-report-terms').is(':checked');
      button.attr('disabled', true);
      spinner.css('visibility', 'visible');
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        async: true,
        data: {
          'action': 'wpstg_send_report',
          'accessToken': wpstg.accessToken,
          'nonce': wpstg.nonce,
          'wpstg_email': email,
          'wpstg_provider': hosting_provider,
          'wpstg_message': message,
          'wpstg_syslog': +syslog,
          'wpstg_terms': +terms,
          'wpstg_force_send': forceSend
        }
      }).done(function (data) {
        button.attr('disabled', false);
        spinner.css('visibility', 'hidden');

        if (data.errors.length > 0) {
          $('.wpstg--tab--active .wpstg-report-issue-form .wpstg-message').remove();
          var errorMessage = $('<div />').addClass('wpstg-message wpstg-error-message');
          $.each(data.errors, function (key, value) {
            if (value.status === 'already_submitted') {
              errorMessage = ''; // TODO: remove default custom classes

              WPStagingCommon.getSwalModal(true, {
                container: 'wpstg-issue-resubmit-confirmation'
              }).fire({
                title: '',
                icon: 'warning',
                html: value.message,
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No'
              }).then(function (result) {
                if (result.isConfirmed) {
                  sendIssueReport(button, 'true');
                }
              });
            } else {
              errorMessage.append('<p>' + value + '</p>');
            }
          });
          $('.wpstg--tab--active .wpstg-report-issue-form').prepend(errorMessage);
        } else {
          var successMessage = $('<div />').addClass('wpstg-message wpstg-success-message');
          successMessage.append('<p>Thanks for submitting your request! You should receive an auto reply mail with your ticket ID immediately for confirmation!<br><br>If you do not get that mail please contact us directly at <strong>support@wp-staging.com</strong></p>');
          $('.wpstg--tab--active .wpstg-report-issue-form').html(successMessage);
          $('.wpstg--tab--active .wpstg-success-message').append('<div style="float:right;margin-top:10px;"><a id="wpstg-success-button" href="#" class="wpstg--red">[X] CLOSE</a></div>'); // Hide message

          setTimeout(function () {
            $('.wpstg--tab--active .wpstg-report-issue-form').removeClass('wpstg-report-active');
          }, 2000);
        }
      });
    } // Open/close actions drop down menu


    $(document).on('click', '.wpstg-dropdown>.wpstg-dropdown-toggler', function (e) {
      e.preventDefault();
      $(e.target).next('.wpstg-dropdown-menu').toggleClass('shown');
      $(e.target).find('.wpstg-caret').toggleClass('wpstg-caret-up');
    });
    $(document).on('click', '.wpstg-caret', function (e) {
      e.preventDefault();
      var toggler = $(e.target).closest('.wpstg-dropdown-toggler');

      if (toggler) {
        toggler.trigger('click');
      }
    }); // Close action drop down menu if clicked anywhere outside

    document.addEventListener('click', function (event) {
      var isClickInside = event.target.closest('.wpstg-dropdown-toggler');

      if (!isClickInside) {
        var dropDown = document.getElementsByClassName('wpstg-dropdown-menu');

        for (var i = 0; i < dropDown.length; i++) {
          dropDown[i].classList.remove('shown');
        }

        $('.wpstg-caret').removeClass('wpstg-caret-up');
      }
    });
  });

}());
//# sourceMappingURL=wpstg-admin.js.map
