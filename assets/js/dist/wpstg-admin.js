(function () {
  'use strict';

  function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) {
    try {
      var info = gen[key](arg);
      var value = info.value;
    } catch (error) {
      reject(error);
      return;
    }

    if (info.done) {
      resolve(value);
    } else {
      Promise.resolve(value).then(_next, _throw);
    }
  }

  function _asyncToGenerator(fn) {
    return function () {
      var self = this,
          args = arguments;
      return new Promise(function (resolve, reject) {
        var gen = fn.apply(self, args);

        function _next(value) {
          asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value);
        }

        function _throw(err) {
          asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err);
        }

        _next(undefined);
      });
    };
  }

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

  function _unsupportedIterableToArray(o, minLen) {
    if (!o) return;
    if (typeof o === "string") return _arrayLikeToArray(o, minLen);
    var n = Object.prototype.toString.call(o).slice(8, -1);
    if (n === "Object" && o.constructor) n = o.constructor.name;
    if (n === "Map" || n === "Set") return Array.from(o);
    if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
  }

  function _arrayLikeToArray(arr, len) {
    if (len == null || len > arr.length) len = arr.length;

    for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];

    return arr2;
  }

  function _createForOfIteratorHelperLoose(o, allowArrayLike) {
    var it;

    if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) {
      if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
        if (it) o = it;
        var i = 0;
        return function () {
          if (i >= o.length) return {
            done: true
          };
          return {
            done: false,
            value: o[i++]
          };
        };
      }

      throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
    }

    it = o[Symbol.iterator]();
    return it.next.bind(it);
  }

  /**
   * WP Staging basic jQuery replacement
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

      Swal.fire(swalOptions).then(function (result) {
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
      return Swal.fire({
        title: '',
        icon: 'warning',
        html: this.getAjaxLoader(),
        width: '300px',
        focusConfirm: false,
        customClass: {
          confirmButton: this.resetButtonClass,
          container: 'wpstg-swal2-container wpstg-swal2-loading ' + this.resetModalContainerClass
        },
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
            showCancelButton: false
          }, data.swalOptions), {
            type: data.type
          });
          return;
        }

        var modal = qs('.wpstg-reset-confirmation');
        modal.classList.remove('wpstg-swal2-loading');
        modal.querySelector('.swal2-popup').style.width = 'auto';
        modal.querySelector('.swal2-content').innerHTML = data.html;
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

  var WPStaging = function ($) {
    var that = {
      isCancelled: false,
      isFinished: false,
      getLogs: false,
      time: 1,
      executionTime: false,
      progressBar: 0,
      cloneExcludeFilters: null,
      directoryNavigator: null,
      notyf: new Notyf({
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
      })
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
       * @param obj
       * @return {boolean}
       */


    function isEmpty(obj) {
      for (var prop in obj) {
        if (obj.hasOwnProperty(prop)) {
          return false;
        }
      }

      return true;
    }
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
        window.removeEventListener('beforeunload', WPStaging.warnIfClosingDuringProcess);
        return;
      }

      if (typeof response.error !== 'undefined' && response.error) {
        console.error(response.message);
        showError(prependMessage + ' Error: ' + response.message + appendMessage);
        window.removeEventListener('beforeunload', WPStaging.warnIfClosingDuringProcess);
        return;
      }
    };
    /**
       *
       * @param response
       * @return {{ok}|*}
       */


    var handleFetchErrors = function handleFetchErrors(response) {
      if (!response.ok) {
        showError('Error: ' + response.status + ' - ' + response.statusText + '. Please try again or contact support.');
      }

      return response;
    };
    /** Hide and reset previous thrown visible errors */


    var resetErrors = function resetErrors() {
      cache.get('#wpstg-error-details').hide().html('');
    };

    var slugify = function slugify(url) {
      return url.toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, '-').replace(/&/g, '-and-').replace(/[^a-z0-9\-]/g, '').replace(/-+/g, '-').replace(/^-*/, '').replace(/-*$/, '');
    };
    /**
       * Common Elements
       */


    var elements = function elements() {
      var $workFlow = cache.get('#wpstg-workflow');
      var isAllChecked = true;
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

        if (false === isAllChecked) {
          cache.get('#wpstg_select_tables_cloning .wpstg-db-table').prop('selected', 'selected');
          cache.get('.wpstg-button-unselect').text('Unselect All');
          cache.get('.wpstg-db-table-checkboxes').prop('checked', true);
          isAllChecked = true;
        } else {
          cache.get('#wpstg_select_tables_cloning .wpstg-db-table').prop('selected', false);
          cache.get('.wpstg-button-unselect').text('Select All');
          cache.get('.wpstg-db-table-checkboxes').prop('checked', false);
          isAllChecked = false;
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
        }

        var cloneID = this.value;
        timer = setTimeout(function () {
          ajax({
            action: 'wpstg_check_clone',
            accessToken: wpstg.accessToken,
            nonce: wpstg.nonce,
            cloneID: cloneID
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

        var slug = slugify(this.value);
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
          Swal.fire({
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

        Swal.fire({
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

      that.data.cloneID = $('#wpstg-new-clone-id').val() || new Date().getTime().toString(); // Remove this to keep &_POST[] small otherwise mod_security will throw error 404
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
        showCancelButton: false
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

        if ($this.hasClass('expand')) {
          $this.find('.wpstg-tab-triangle').html('&#9660;');
        } else {
          $this.find('.wpstg-tab-triangle').html('&#9658;');
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

            if (response["delete"] === 'finished') {
              $('.wpstg-clone#' + clone).remove();
            }

            if ($('.wpstg-clone').length < 1) {
              cache.get('#wpstg-existing-clones').find('h3').text('');
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
              cache.get('.wpstg-log-details').append('<span style="color:red;">[' + value.type + ']</span>-' + '[' + value.date + '] ' + value.message + '</br>');
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


          cache.get('#wpstg-clone-id-error').html('Estimated necessary disk space: ' + response.usedspace + '<br> <span style="color:#444;">Before you proceed ensure your account has enough free disk space to hold the entire instance of the production site. You can check the available space from your hosting account (cPanel or similar).</span>').show();
          cache.get('.wpstg-loader').hide();
        }, 'json', false);
      });
    };

    var mainTabs = function mainTabs() {
      $('.wpstg--tab--header a[data-target]').on('click', function () {
        var $this = $(this);
        var target = $this.attr('data-target');
        var $wrapper = $this.parents('.wpstg--tab--wrapper');
        var $menuItems = $wrapper.find('.wpstg--tab--header a[data-target]');
        var $contents = $wrapper.find('.wpstg--tab--contents > .wpstg--tab--content');
        $contents.filter('.wpstg--tab--active:not(.wpstg--tab--active' + target + ')').removeClass('wpstg--tab--active');
        $menuItems.not($this).removeClass('wpstg--tab--active');
        $this.addClass('wpstg--tab--active');
        $(target).addClass('wpstg--tab--active');

        if ('#wpstg--tab--backup' === target) {
          that.backups.init();
        }

        if ('#wpstg--tab--database-backups' === target) {
          window.dispatchEvent(new Event('database-backups-tab'));
        }
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
          window.addEventListener('beforeunload', WPStaging.warnIfClosingDuringProcess);
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
          window.removeEventListener('beforeunload', WPStaging.warnIfClosingDuringProcess);
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
        WPStaging.ajax({
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
            window.removeEventListener('beforeunload', WPStaging.warnIfClosingDuringProcess);
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
          Swal.fire({
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
      mainTabs();
      new WpstgCloneStaging();
    };
    /**
     * Ajax call
     * @type {ajax}
     */


    that.ajax = ajax;
    that.showError = showError;
    that.getLogs = getLogs;
    that.loadOverview = loadOverview; // TODO RPoC (too big, scattered and unorganized)

    that.backups = {
      type: null,
      isCancelled: false,
      processInfo: {
        title: null,
        interval: null
      },
      modal: {
        create: {
          html: null,
          confirmBtnTxt: null
        },
        process: {
          html: null,
          cancelBtnTxt: null,
          modal: null
        },
        download: {
          html: null
        },
        "import": {
          html: null,
          btnTxtNext: null,
          btnTxtConfirm: null,
          btnTxtCancel: null,
          searchReplaceForm: null,
          file: null,
          containerUpload: null,
          containerFilesystem: null,
          setFile: function setFile(file, upload) {
            if (upload === void 0) {
              upload = true;
            }

            var toUnit = function toUnit(bytes) {
              var i = Math.floor(Math.log(bytes) / Math.log(1024));
              return (bytes / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
            };

            if (!file) {
              return;
            }

            that.backups.modal["import"].file = file;
            that.backups.modal["import"].data.file = file.name;
            $('.wpstg--backup--import--selected-file').html(file.name + " <br /> (" + toUnit(file.size) + ")").show();
            $('.wpstg--drag').hide();
            $('.wpstg--drag-or-upload').show();

            if (upload) {
              $('.wpstg--modal--actions .swal2-confirm').prop('disabled', true);
              that.backups.upload.start();
            }
          },
          baseDirectory: null,
          data: {
            file: null,
            search: [],
            replace: []
          }
        }
      },
      messages: {
        WARNING: 'warning',
        ERROR: 'error',
        INFO: 'info',
        DEBUG: 'debug',
        CRITICAL: 'critical',
        data: {
          all: [],
          // TODO RPoC
          info: [],
          error: [],
          critical: [],
          warning: [],
          debug: []
        },
        shouldWarn: function shouldWarn() {
          return that.backups.messages.data.error.length > 0 || that.backups.messages.data.critical.length > 0;
        },
        countByType: function countByType(type) {
          if (type === void 0) {
            type = that.backups.messages.ERROR;
          }

          return that.backups.messages.data[type].length;
        },
        addMessage: function addMessage(message) {
          if (Array.isArray(message)) {
            message.forEach(function (item) {
              that.backups.messages.addMessage(item);
            });
            return;
          }

          var type = message.type.toLowerCase() || 'info';

          if (!that.backups.messages.data[type]) {
            that.backups.messages.data[type] = [];
          }

          that.backups.messages.data.all.push(message); // TODO RPoC

          that.backups.messages.data[type].push(message);
        },
        reset: function reset() {
          that.backups.messages.data = {
            all: [],
            info: [],
            error: [],
            critical: [],
            warning: [],
            debug: []
          };
        }
      },
      timer: {
        totalSeconds: 0,
        interval: null,
        start: function start() {
          if (null !== that.backups.timer.interval) {
            return;
          }

          var prettify = function prettify(seconds) {
            // If potentially anything can exceed 24h execution time than that;
            // const _seconds = parseInt(seconds, 10)
            // const hours = Math.floor(_seconds / 3600)
            // const minutes = Math.floor(_seconds / 60) % 60
            // seconds = _seconds % 60
            //
            // return [hours, minutes, seconds]
            //   .map(v => v < 10 ? '0' + v : v)
            //   .filter((v,i) => v !== '00' || i > 0)
            //   .join(':')
            // ;
            // Are we sure we won't create anything that exceeds 24h execution time? If not then this;
            return "" + new Date(seconds * 1000).toISOString().substr(11, 8);
          };

          that.backups.timer.interval = setInterval(function () {
            $('.wpstg--modal--process--elapsed-time').text(prettify(that.backups.timer.totalSeconds));
            that.backups.timer.totalSeconds++;
          }, 1000);
        },
        stop: function stop() {
          that.backups.timer.totalSeconds = 0;

          if (that.backups.timer.interval) {
            clearInterval(that.backups.timer.interval);
            that.backups.timer.interval = null;
          }
        }
      },
      upload: {
        reader: null,
        file: null,
        iop: 1000 * 1024,
        uploadInfo: function uploadInfo(isShow) {
          var $containerUpload = $('.wpstg--modal--import--upload--process');
          var $containerUploader = $('.wpstg--uploader');

          if (isShow) {
            $containerUpload.css('display', 'flex');
            $containerUploader.hide();
            return;
          }

          $containerUploader.css('display', 'flex');
          $containerUpload.hide();
        },
        start: function start() {
          that.backups.upload.reader = new FileReader();
          that.backups.upload.file = that.backups.modal["import"].file;
          that.backups.upload.uploadInfo(true);
          that.backups.upload.sendChunk();
        },
        sendChunk: function sendChunk(startsAt) {
          if (startsAt === void 0) {
            startsAt = 0;
          }

          if (!that.backups.upload.file) {
            return;
          }

          var isReset = startsAt < 1;
          var endsAt = startsAt + that.backups.upload.iop + 1;
          var blob = that.backups.upload.file.slice(startsAt, endsAt);

          that.backups.upload.reader.onloadend = function (event) {
            if (event.target.readyState !== FileReader.DONE) {
              return;
            }

            var body = new FormData();
            body.append('accessToken', wpstg.accessToken);
            body.append('nonce', wpstg.nonce);
            body.append('data', event.target.result);
            body.append('filename', that.backups.upload.file.name);
            body.append('reset', isReset ? '1' : '0');
            fetch(ajaxurl + "?action=wpstg--backups--import--file-upload", {
              method: 'POST',
              body: body
            }).then(handleFetchErrors).then(function (res) {
              return res.json();
            }).then(function (res) {
              showAjaxFatalError(res, '', 'Submit an error report.');
              var writtenBytes = startsAt + that.backups.upload.iop;
              var percent = Math.floor(writtenBytes / that.backups.upload.file.size * 100);

              if (endsAt >= that.backups.upload.file.size) {
                that.backups.upload.uploadInfo(false);
                isLoading(false);
                that.backups.switchModalToConfigure();
                return;
              }

              $('.wpstg--modal--import--upload--progress--title > span').text(percent);
              $('.wpstg--modal--import--upload--progress').css('width', percent + "%");
              that.backups.upload.sendChunk(endsAt);
            })["catch"](function (e) {
              return showAjaxFatalError(e, '', 'Submit an error report.');
            });
          };

          that.backups.upload.reader.readAsDataURL(blob);
        }
      },
      status: {
        hasResponse: null,
        reTryAfter: 5000
      },
      init: function init() {
        this.create();
        this["delete"]();
        this.edit(); // noinspection JSIgnoredPromiseFromCall

        that.backups.fetchListing();
        $('body').on('click', '.wpstg--backup--download', function () {
          var url = this.getAttribute('data-url');

          if (url.length > 0) {
            window.location.href = url;
            return;
          }

          that.backups.downloadModal({
            titleExport: this.getAttribute('data-title-export'),
            title: this.getAttribute('data-title'),
            id: this.getAttribute('data-id'),
            btnTxtCancel: this.getAttribute('data-btn-cancel-txt'),
            btnTxtConfirm: this.getAttribute('data-btn-download-txt')
          });
        }).on('click', '.wpstg--backup--import', function () {
          // Clicked "Import" on the backup list
          that.backups.importModal();
          that.backups.modal["import"].data.file = this.getAttribute('data-filePath');
          that.backups.switchModalToConfigure(); // $('.wpstg--modal--actions .swal2-confirm').show();
          // $('.wpstg--modal--actions .swal2-confirm').prop('disabled', false);
        }).off('click', '#wpstg-import-backup').on('click', '#wpstg-import-backup', function () {
          that.backups.importModal();
        }) // Import
        .off('click', '.wpstg--backup--import--choose-option').on('click', '.wpstg--backup--import--choose-option', function () {
          var $this = $(this);
          var $parent = $this.parent();

          if (!$parent.hasClass('wpstg--show-options')) {
            $parent.addClass('wpstg--show-options');
            $this.text($this.attr('data-txtChoose'));
          } else {
            $parent.removeClass('wpstg--show-options');
            $this.text($this.attr('data-txtOther'));
          }
        }).off('click', '.wpstg--modal--backup--import--search-replace--new').on('click', '.wpstg--modal--backup--import--search-replace--new', function (e) {
          e.preventDefault();
          var $container = $(Swal.getContainer()).find('.wpstg--modal--backup--import--search-replace--input--container');
          var total = $container.find('.wpstg--modal--backup--import--search-replace--input-group').length;
          $container.append(that.backups.modal["import"].searchReplaceForm.replace(/{i}/g, total));
        }).off('click', '.wpstg--modal--backup--import--search-replace--remove').on('click', '.wpstg--modal--backup--import--search-replace--remove', function (e) {
          e.preventDefault();
          $(e.target).closest('.wpstg--modal--backup--import--search-replace--input-group').remove();
        }).off('input', '.wpstg--backup--import--search').on('input', '.wpstg--backup--import--search', function () {
          var index = parseInt(this.getAttribute('data-index'));

          if (!isNaN(index)) {
            that.backups.modal["import"].data.search[index] = this.value;
          }
        }).off('input', '.wpstg--backup--import--replace').on('input', '.wpstg--backup--import--replace', function () {
          var index = parseInt(this.getAttribute('data-index'));

          if (!isNaN(index)) {
            that.backups.modal["import"].data.replace[index] = this.value;
          }
        }) // Other Options
        .off('click', '.wpstg--backup--import--option[data-option]').on('click', '.wpstg--backup--import--option[data-option]', function () {
          var option = this.getAttribute('data-option');

          if (option === 'file') {
            $('input[type="file"][name="wpstg--backup--import--upload--file"]').trigger('click');
            return;
          }

          if (option === 'upload') {
            that.backups.modal["import"].containerFilesystem.hide();
            that.backups.modal["import"].containerUpload.show();
          }

          if (option !== 'filesystem') {
            return;
          }

          that.backups.modal["import"].containerUpload.hide();
          var $containerFilesystem = that.backups.modal["import"].containerFilesystem;
          $containerFilesystem.show();
          fetch(ajaxurl + "?action=wpstg--backups--import--file-list&_=" + Math.random() + "&accessToken=" + wpstg.accessToken + "&nonce=" + wpstg.nonce).then(handleFetchErrors).then(function (res) {
            return res.json();
          }).then(function (res) {
            var $ul = $('.wpstg--modal--backup--import--filesystem ul');
            $ul.empty();

            if (!res || isEmpty(res)) {
              $ul.append("<span id=\"wpstg--backups--import--file-list-empty\">" + wpstg.i18n.noImportFileFound + "</span><br />");
              $('.wpstg--modal--backup--import--search-replace--wrapper').hide();
              return;
            }

            $ul.append("<span id=\"wpstg--backups--import--file-list\">" + wpstg.i18n.selectFileToImport + "</span><br />");
            res.forEach(function (file, index) {
              $ul.append("\n<li data-filepath=\"" + file.fullPath + "\" class=\"wpstg--backups--import--file-list--sigle-item\">\n    <ul class=\"wpstg-import-backup-more-info wpstg-import-backup-more-info-" + index + ("\">\n        <li style=\"font-weight: bold\">" + file.backupName + "</li>\n        <li>Created on: " + file.dateCreatedFormatted + "</li>\n        <li>Size: " + file.size + "</li>\n        <li>\n            <div class=\"wpstg-import-backup-contains-title\">This backup contains:</div>\n            <ul class=\"wpstg-import-backup-contains\">\n            ") + (file.isExportingDatabase ? '<li><span class="dashicons dashicons-database wpstg--tooltip"><div class=\'wpstg--tooltiptext\'>Database</div></span></li>' : '') + "\n            " + (file.isExportingPlugins ? '<li><span class="dashicons dashicons-admin-plugins wpstg--tooltip"><div class=\'wpstg--tooltiptext\'>Plugins</div></span></li>' : '') + "\n            " + (file.isExportingMuPlugins ? '<li><span class="dashicons dashicons-plugins-checked wpstg--tooltip"><div class=\'wpstg--tooltiptext\'>Mu-plugins</div></span></li>' : '') + "\n            " + (file.isExportingThemes ? '<li><span class="dashicons dashicons-layout wpstg--tooltip"><div class=\'wpstg--tooltiptext\'>Themes</div></span></li>' : '') + "\n            " + (file.isExportingUploads ? '<li><span class="dashicons dashicons-images-alt wpstg--tooltip"><div class=\'wpstg--tooltiptext\'>Uploads</div></span></li>' : '') + "\n            " + (file.isExportingOtherWpContentFiles ? '<li><span class="dashicons dashicons-admin-generic wpstg--tooltip"><div class=\'wpstg--tooltiptext\'>Other files in wp-content</div></span></li>' : '') + ("\n            </ul>\n        </li>\n        <li>Filename: " + file.name + "</li>\n    </ul>\n</li>\n"));
            });
            return res;
          })["catch"](function (e) {
            return showAjaxFatalError(e, '', 'Submit an error report.');
          });
        }).off('change', 'input[type="file"][name="wpstg--backup--import--upload--file"]').on('change', 'input[type="file"][name="wpstg--backup--import--upload--file"]', function () {
          that.backups.modal["import"].setFile(this.files[0] || null);
        }).off('change', 'input[type="radio"][name="backup_import_file"]').on('change', 'input[type="radio"][name="backup_import_file"]', function () {
          $('.wpstg--modal--actions .swal2-confirm').show();
          $('.wpstg--modal--actions .swal2-confirm').prop('disabled', false);
          that.backups.modal["import"].data.file = this.value;
        }) // Drag & Drop
        .on('drag dragstart dragend dragover dragenter dragleave drop', '.wpstg--modal--backup--import--upload--container', function (e) {
          e.preventDefault();
          e.stopPropagation();
        }).on('dragover dragenter', '.wpstg--modal--backup--import--upload--container', function () {
          $(this).addClass('wpstg--has-dragover');
        }).on('dragleave dragend drop', '.wpstg--modal--backup--import--upload--container', function () {
          $(this).removeClass('wpstg--has-dragover');
        }).on('drop', '.wpstg--modal--backup--import--upload--container', function (e) {
          // Uploaded a file through the "Import Backup modal"
          that.backups.modal["import"].setFile(e.originalEvent.dataTransfer.files[0] || null);
        }).on('click', '.wpstg--modal--backup--import--filesystem li', function (e) {
          // Selected an existing backup in the Import Backup modal
          var fullPath = $(e.target).closest('.wpstg--backups--import--file-list--sigle-item').data().filepath;

          if (!fullPath.length) {
            alert('Error: Could not get file path.');
            return;
          }

          that.backups.modal["import"].data.file = fullPath;
          that.backups.switchModalToConfigure(); // $('.wpstg--modal--actions .swal2-confirm').prop('disabled', false);
          // that.backups.modal.gotoStart();
        }).on('change', '.wpstg-advanced-options-site input[type="checkbox"]', function (e) {
          /*
                 * We use a timeout here to simulate an "afterChange" event.
                 * This allows us to read from the DOM whether the checkbox is checked or not,
                 * instead of having to read from the event, which would require a lengthier code
                 * to account for all elements we might want to read.
                 */
          setTimeout(function (e) {
            // Reset
            document.getElementById('exportUploadsWithoutDatabaseWarning').style.display = 'none'; // Exporting Media Library without Database

            var databaseChecked = document.getElementById('includeDatabaseInBackup').checked;
            var mediaLibraryChecked = document.getElementById('includeMediaLibraryInBackup').checked;

            if (mediaLibraryChecked && !databaseChecked) {
              document.getElementById('exportUploadsWithoutDatabaseWarning').style.display = 'block';
            }
          }, 100);
        });
      },
      fetchListing: function fetchListing(isResetErrors) {
        if (isResetErrors === void 0) {
          isResetErrors = true;
        }

        isLoading(true);

        if (isResetErrors) {
          resetErrors();
        }

        return fetch(ajaxurl + "?action=wpstg--backups--listing&_=" + Math.random() + "&accessToken=" + wpstg.accessToken + "&nonce=" + wpstg.nonce).then(handleFetchErrors).then(function (res) {
          return res.json();
        }).then(function (res) {
          showAjaxFatalError(res, '', 'Submit an error report.');
          cache.get('#wpstg--tab--backup').html(res);
          isLoading(false);
          window.dispatchEvent(new Event('backupListingFinished'));
          return res;
        })["catch"](function (e) {
          return showAjaxFatalError(e, '', 'Submit an error report.');
        });
      },
      "delete": function _delete() {
        $('#wpstg--tab--backup').off('click', '.wpstg-delete-backup[data-md5]').on('click', '.wpstg-delete-backup[data-md5]', function (e) {
          e.preventDefault();
          resetErrors();

          if (!confirm('Are you sure you want to delete this backup?')) {
            return;
          }

          isLoading(true);
          cache.get('#wpstg-existing-backups').hide();
          var md5 = this.getAttribute('data-md5');
          that.ajax({
            action: 'wpstg--backups--delete',
            md5: md5,
            accessToken: wpstg.accessToken,
            nonce: wpstg.nonce
          }, function (response) {
            showAjaxFatalError(response, '', ' Please submit an error report by using the REPORT ISSUE button.');
            isLoading(false);
            that.backups.fetchListing();
          });
        }); // Force delete if backup tables do not exist
        // TODO This is bloated, no need extra ID, use existing one?

        $('#wpstg-error-wrapper').off('click', '#wpstg-backup-force-delete').on('click', '#wpstg-backup-force-delete', function (e) {
          e.preventDefault();
          resetErrors();
          isLoading(true);
          var id = this.getAttribute('data-id');

          if (!confirm('Do you want to delete this backup ' + id + ' from the listed backups?')) {
            isLoading(false);
            return false;
          }

          that.ajax({
            action: 'wpstg--backups--delete',
            id: id,
            accessToken: wpstg.accessToken,
            nonce: wpstg.nonce
          }, function (response) {
            showAjaxFatalError(response, '', ' Please submit an error report by using the REPORT ISSUE button.'); // noinspection JSIgnoredPromiseFromCall

            that.backups.fetchListing();
            isLoading(false);
          });
        });
      },
      create: function create() {
        var prepareBackup = function prepareBackup(data) {
          WPStaging.ajax({
            action: 'wpstg--backups--prepare-export',
            accessToken: wpstg.accessToken,
            nonce: wpstg.nonce,
            wpstgExportData: data
          }, function (response) {
            if (response.success) {
              that.backups.timer.start();
              createBackup();
            } else {
              showAjaxFatalError(response.data, '', 'Submit an error report.');
            }
          }, 'json', false, 0, 1.25);
        };

        var createBackup = function createBackup() {
          resetErrors();

          if (that.backups.isCancelled) {
            // Swal.close();
            return;
          }

          var statusStop = function statusStop() {
            clearInterval(that.backups.processInfo.interval);
            that.backups.processInfo.interval = null;
          };

          var status = function status() {
            if (that.backups.processInfo.interval !== null) {
              return;
            }

            that.backups.processInfo.interval = setInterval(function () {
              if (true === that.backups.isCancelled) {
                statusStop();
                return;
              }

              if (that.backups.status.hasResponse === false) {
                return;
              }

              that.backups.status.hasResponse = false;
              fetch(ajaxurl + "?action=wpstg--backups--status&accessToken=" + wpstg.accessToken + "&nonce=" + wpstg.nonce).then(function (res) {
                return res.json();
              }).then(function (res) {
                that.backups.status.hasResponse = true;

                if (typeof res === 'undefined') {
                  statusStop();
                }

                if (that.backups.processInfo.title === res.currentStatusTitle) {
                  return;
                }

                that.backups.processInfo.title = res.currentStatusTitle;
                var $container = $(Swal.getContainer());
                $container.find('.wpstg--modal--process--title').text(res.currentStatusTitle);
                $container.find('.wpstg--modal--process--percent').text('0');
              })["catch"](function (e) {
                that.backups.status.hasResponse = true;
                showAjaxFatalError(e, '', 'Submit an error report.');
              });
            }, 5000);
          };

          WPStaging.ajax({
            action: 'wpstg--backups--export',
            accessToken: wpstg.accessToken,
            nonce: wpstg.nonce
          }, function (response) {
            if (typeof response === 'undefined') {
              setTimeout(function () {
                createBackup();
              }, wpstg.delayReq);
              return;
            }

            that.backups.processResponse(response);

            if (!that.backups.processInfo.interval) {
              status();
            }

            if (response.status === false) {
              createBackup();
            } else if (response.status === true) {
              $('#wpstg--progress--status').text('Backup successfully created!');
              that.backups.type = null;

              if (that.backups.messages.shouldWarn()) {
                // noinspection JSIgnoredPromiseFromCall
                that.backups.fetchListing();
                that.backups.logsModal();
                return;
              }

              statusStop();
              Swal.close();
              that.backups.fetchListing().then(function () {
                if (!response.backupMd5) {
                  showError('Failed to get backup md5 from response');
                  return;
                } // Wait for fetchListing to populate the DOM with the backup data that we want to read


                var $el = '';
                var timesWaited = 0;
                var intervalWaitForBackupInDom = setInterval(function () {
                  timesWaited++;
                  $el = $(".wpstg--backup--download[data-md5=\"" + response.backupMd5 + "\"]"); // Could not find element, let's try again...

                  if (!$el.length) {
                    if (timesWaited >= 10) {
                      // Bail: We tried too many times and couldn't find.
                      clearInterval(intervalWaitForBackupInDom);
                    }

                    return;
                  } // Found it. No more need for the interval.


                  clearInterval(intervalWaitForBackupInDom);
                  var backupSize = response.hasOwnProperty('backupSize') ? ' (' + response.backupSize + ')' : '';
                  that.backups.downloadModal({
                    id: $el.data('id'),
                    url: $el.data('url'),
                    title: $el.data('title'),
                    titleExport: $el.data('title-export'),
                    btnTxtCancel: $el.data('btn-cancel-txt'),
                    btnTxtConfirm: $el.data('btn-download-txt') + backupSize
                  });
                  $('.wpstg--modal--download--logs--wrapper').show();
                  var $logsContainer = $('.wpstg--modal--process--logs');
                  that.backups.messages.data.all.forEach(function (message) {
                    var msgClass = "wpstg--modal--process--msg--" + message.type.toLowerCase();
                    $logsContainer.append("<p class=\"" + msgClass + "\">[" + message.type + "] - [" + message.date + "] - " + message.message + "</p>");
                  });
                }, 200);
              });
            } else {
              setTimeout(function () {
                createBackup();
              }, wpstg.delayReq);
            }
          }, 'json', false, 0, // Don't retry upon failure
          1.25);
        };

        var $body = $('body');
        $body.off('click', '.wpstg--tab--toggle').on('click', '.wpstg--tab--toggle', function () {
          var $this = $(this);
          var $target = $($this.attr('data-target'));
          $target.toggle();

          if ($target.is(':visible')) {
            $this.find('span').text('▼');
          } else {
            $this.find('span').text('►');
          }
        }).off('change', '[name="includedDirectories\[\]"], input#includeDatabaseInBackup, input#includeOtherFilesInWpContent').on('change', '[type="checkbox"][name="includedDirectories\[\]"], input#includeDatabaseInBackup, input#includeOtherFilesInWpContent', function () {
          var isExportingAnyDir = $('[type="checkbox"][name="includedDirectories\[\]"]:checked').length > 0;
          var isExportingDatabase = $('input#includeDatabaseInBackup:checked').length === 1;
          var isExportingOtherFilesInWpContent = $('input#includeOtherFilesInWpContent:checked').length === 1;

          if (!isExportingAnyDir && !isExportingDatabase && !isExportingOtherFilesInWpContent) {
            $('.swal2-confirm').prop('disabled', true);
          } else {
            $('.swal2-confirm').prop('disabled', false);
          }
        }); // Add backup name and notes

        $('#wpstg--tab--backup').off('click', '#wpstg-new-backup').on('click', '#wpstg-new-backup', /*#__PURE__*/function () {
          var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(e) {
            var $newBackupModal, html, btnTxt, _yield$Swal$fire, formValues;

            return regeneratorRuntime.wrap(function _callee$(_context) {
              while (1) {
                switch (_context.prev = _context.next) {
                  case 0:
                    resetErrors();
                    e.preventDefault();
                    that.backups.isCancelled = false;

                    if (!that.backups.modal.create.html || !that.backups.modal.create.confirmBtnTxt) {
                      $newBackupModal = $('#wpstg--modal--backup--new');
                      html = $newBackupModal.html();
                      btnTxt = $newBackupModal.attr('data-confirmButtonText');
                      that.backups.modal.create.html = html || null;
                      that.backups.modal.create.confirmBtnTxt = btnTxt || null;
                      $newBackupModal.remove();
                    }

                    _context.next = 6;
                    return Swal.fire({
                      title: '',
                      html: that.backups.modal.create.html,
                      focusConfirm: false,
                      confirmButtonText: that.backups.modal.create.confirmBtnTxt,
                      showCancelButton: true,
                      preConfirm: function preConfirm() {
                        var container = Swal.getContainer();
                        return {
                          name: container.querySelector('input[name="backup_name"]').value || null,
                          isExportingPlugins: container.querySelector('#includePluginsInBackup:checked') !== null,
                          isExportingMuPlugins: container.querySelector('#includeMuPluginsInBackup:checked') !== null,
                          isExportingThemes: container.querySelector('#includeThemesInBackup:checked') !== null,
                          isExportingUploads: container.querySelector('#includeMediaLibraryInBackup:checked') !== null,
                          isExportingOtherWpContentFiles: container.querySelector('#includeOtherFilesInWpContent:checked') !== null,
                          isExportingDatabase: container.querySelector('#includeDatabaseInBackup:checked') !== null
                        };
                      }
                    });

                  case 6:
                    _yield$Swal$fire = _context.sent;
                    formValues = _yield$Swal$fire.value;

                    if (formValues) {
                      _context.next = 10;
                      break;
                    }

                    return _context.abrupt("return");

                  case 10:
                    that.backups.process({
                      execute: function execute() {
                        that.backups.messages.reset();
                        prepareBackup(formValues);
                      }
                    });

                  case 11:
                  case "end":
                    return _context.stop();
                }
              }
            }, _callee);
          }));

          return function (_x) {
            return _ref.apply(this, arguments);
          };
        }());
      },
      switchModalToConfigure: function switchModalToConfigure() {
        that.backups.modal["import"].containerUpload.hide();
        that.backups.modal["import"].containerFilesystem.hide();
        that.backups.modal["import"].containerConfigure.show();
      },
      // Edit backups name and notes
      edit: function edit() {
        $('#wpstg--tab--backup').off('click', '.wpstg--backup--edit[data-md5]').on('click', '.wpstg--backup--edit[data-md5]', /*#__PURE__*/function () {
          var _ref2 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(e) {
            var $this, name, notes, _yield$Swal$fire2, formValues;

            return regeneratorRuntime.wrap(function _callee2$(_context2) {
              while (1) {
                switch (_context2.prev = _context2.next) {
                  case 0:
                    e.preventDefault();
                    $this = $(this);
                    name = $this.data('name');
                    notes = $this.data('notes');
                    _context2.next = 6;
                    return Swal.fire({
                      title: '',
                      html: "\n                    <label id=\"wpstg-backup-edit-name\">Backup Name</label>\n                    <input id=\"wpstg-backup-edit-name-input\" class=\"swal2-input\" value=\"" + name + "\">\n                    <label>Additional Notes</label>\n                    <textarea id=\"wpstg-backup-edit-notes-textarea\" class=\"swal2-textarea\">" + notes + "</textarea>\n                  ",
                      focusConfirm: false,
                      confirmButtonText: 'Update Backup',
                      showCancelButton: true,
                      preConfirm: function preConfirm() {
                        return {
                          name: document.getElementById('wpstg-backup-edit-name-input').value || null,
                          notes: document.getElementById('wpstg-backup-edit-notes-textarea').value || null
                        };
                      }
                    });

                  case 6:
                    _yield$Swal$fire2 = _context2.sent;
                    formValues = _yield$Swal$fire2.value;

                    if (formValues) {
                      _context2.next = 10;
                      break;
                    }

                    return _context2.abrupt("return");

                  case 10:
                    that.ajax({
                      action: 'wpstg--backups--edit',
                      accessToken: wpstg.accessToken,
                      nonce: wpstg.nonce,
                      md5: $this.data('md5'),
                      name: formValues.name,
                      notes: formValues.notes
                    }, function (response) {
                      showAjaxFatalError(response, '', 'Submit an error report.'); // noinspection JSIgnoredPromiseFromCall

                      that.backups.fetchListing();
                    });

                  case 11:
                  case "end":
                    return _context2.stop();
                }
              }
            }, _callee2, this);
          }));

          return function (_x2) {
            return _ref2.apply(this, arguments);
          };
        }());
      },
      cancel: function cancel() {
        that.backups.timer.stop();
        that.backups.isCancelled = true;
        Swal.close();
        setTimeout(function () {
          return that.ajax({
            action: 'wpstg--backups--cancel',
            accessToken: wpstg.accessToken,
            nonce: wpstg.nonce,
            type: that.backups.type
          }, function (response) {
            showAjaxFatalError(response, '', 'Submit an error report.');
          });
        }, 500);
      },

      /**
       * If process.execute exists, process.data and process.onResponse is not used
       * process = { data: {}, onResponse: (resp) => {}, onAfterClose: () => {}, execute: () => {}, isShowCancelButton: bool }
       * @param {object} process
       */
      process: function process(_process) {
        if (typeof _process.execute !== 'function' && (!_process.data || !_process.onResponse)) {
          Swal.close();
          showError('process.data and / or process.onResponse is not set');
          return;
        } // TODO move to backend and get the contents as xhr response?


        if (!that.backups.modal.process.html || !that.backups.modal.process.cancelBtnTxt) {
          var $modal = $('#wpstg--modal--backup--process');
          var html = $modal.html();
          var btnTxt = $modal.attr('data-cancelButtonText');
          that.backups.modal.process.html = html || null;
          that.backups.modal.process.cancelBtnTxt = btnTxt || null;
          $modal.remove();
        }

        $('body').off('click', '.wpstg--modal--process--logs--tail').on('click', '.wpstg--modal--process--logs--tail', function (e) {
          e.preventDefault();
          var container = Swal.getContainer();
          var $logs = $(container).find('.wpstg--modal--process--logs');
          $logs.toggle();

          if ($logs.is(':visible')) {
            container.childNodes[0].style.width = '100%';
            container.style['z-index'] = 9999;
          } else {
            container.childNodes[0].style.width = '600px';
          }
        });
        _process.isShowCancelButton = false !== _process.isShowCancelButton;
        that.backups.modal.process.modal = Swal.mixin({
          customClass: {
            cancelButton: 'wpstg--btn--cancel wpstg-blue-primary wpstg-link-btn',
            content: 'wpstg--process--content'
          },
          buttonsStyling: false
        }).fire({
          html: that.backups.modal.process.html,
          cancelButtonText: that.backups.modal.process.cancelBtnTxt,
          showCancelButton: _process.isShowCancelButton,
          showConfirmButton: false,
          allowOutsideClick: false,
          allowEscapeKey: false,
          width: 600,
          onRender: function onRender() {
            var _btnCancel = Swal.getContainer().getElementsByClassName('swal2-cancel wpstg--btn--cancel')[0];

            var btnCancel = _btnCancel.cloneNode(true);

            _btnCancel.parentNode.replaceChild(btnCancel, _btnCancel);

            btnCancel.addEventListener('click', function (e) {
              if (confirm('Are You Sure? This will cancel the process!')) {
                Swal.close();
              }
            });

            if (typeof _process.execute === 'function') {
              _process.execute();

              return;
            }

            if (!_process.data || !_process.onResponse) {
              Swal.close();
              showError('process.data and / or process.onResponse is not set');
              return;
            }

            that.ajax(_process.data, _process.onResponse);
          },
          onAfterClose: function onAfterClose() {
            return typeof _process.onAfterClose === 'function' && _process.onAfterClose();
          },
          onClose: function onClose() {
            that.backups.cancel();
          }
        });
      },
      processResponse: function processResponse(response, useTitle) {
        if (response === null) {
          Swal.close();
          showError('Invalid Response; null');
          throw new Error("Invalid Response; " + response);
        }

        var $container = $(Swal.getContainer());

        var title = function title() {
          if ((response.title || response.statusTitle) && useTitle === true) {
            $container.find('.wpstg--modal--process--title').text(response.title || response.statusTitle);
          }
        };

        var percentage = function percentage() {
          if (response.percentage) {
            $container.find('.wpstg--modal--process--percent').text(response.percentage);
          }
        };

        var logs = function logs() {
          if (!response.messages) {
            return;
          }

          var $logsContainer = $container.find('.wpstg--modal--process--logs');
          var stoppingTypes = [that.backups.messages.ERROR, that.backups.messages.CRITICAL];

          var appendMessage = function appendMessage(message) {
            if (Array.isArray(message)) {
              for (var _iterator = _createForOfIteratorHelperLoose(message), _step; !(_step = _iterator()).done;) {
                var item = _step.value;
                appendMessage(item);
              }

              return;
            }

            var msgClass = "wpstg--modal--process--msg--" + message.type.toLowerCase();
            $logsContainer.append("<p class=\"" + msgClass + "\">[" + message.type + "] - [" + message.date + "] - " + message.message + "</p>");

            if (stoppingTypes.includes(message.type.toLowerCase())) {
              that.backups.cancel();
              setTimeout(that.backups.logsModal, 500);
            }
          };

          for (var _iterator2 = _createForOfIteratorHelperLoose(response.messages), _step2; !(_step2 = _iterator2()).done;) {
            var message = _step2.value;

            if (!message) {
              continue;
            }

            that.backups.messages.addMessage(message);
            appendMessage(message);
          }

          if ($logsContainer.is(':visible')) {
            $logsContainer.scrollTop($logsContainer[0].scrollHeight);
          }

          if (!that.backups.messages.shouldWarn()) {
            return;
          }

          var $btnShowLogs = $container.find('.wpstg--modal--process--logs--tail');
          $btnShowLogs.html($btnShowLogs.attr('data-txt-bad'));
          $btnShowLogs.find('.wpstg--modal--logs--critical-count').text(that.backups.messages.countByType(that.backups.messages.CRITICAL));
          $btnShowLogs.find('.wpstg--modal--logs--error-count').text(that.backups.messages.countByType(that.backups.messages.ERROR));
          $btnShowLogs.find('.wpstg--modal--logs--warning-count').text(that.backups.messages.countByType(that.backups.messages.WARNING));
        };

        title();
        percentage();
        logs();

        if (response.status === true && response.job_done === true) {
          that.backups.timer.stop();
          that.backups.isCancelled = true;
        }
      },
      requestData: function requestData(notation, data) {
        var obj = {};
        var keys = notation.split('.');
        var lastIndex = keys.length - 1;
        keys.reduce(function (accumulated, current, index) {
          return accumulated[current] = index >= lastIndex ? data : {};
        }, obj);
        return obj;
      },
      logsModal: function logsModal() {
        Swal.fire({
          html: "<div class=\"wpstg--modal--error--logs\" style=\"display:block\"></div><div class=\"wpstg--modal--process--logs\" style=\"display:block\"></div>",
          width: '95%',
          onRender: function onRender() {
            var $container = $(Swal.getContainer());
            $container[0].style['z-index'] = 9999;
            var $logsContainer = $container.find('.wpstg--modal--process--logs');
            var $errorContainer = $container.find('.wpstg--modal--error--logs');
            var $translations = $('#wpstg--js--translations');
            var messages = that.backups.messages;
            var title = $translations.attr('data-modal-logs-title').replace('{critical}', messages.countByType(messages.CRITICAL)).replace('{errors}', messages.countByType(messages.ERROR)).replace('{warnings}', messages.countByType(messages.WARNING));
            $errorContainer.before("<h3>" + title + "</h3>");
            var warnings = [that.backups.messages.CRITICAL, that.backups.messages.ERROR, that.backups.messages.WARNING];

            if (!that.backups.messages.shouldWarn()) {
              $errorContainer.hide();
            }

            for (var _iterator3 = _createForOfIteratorHelperLoose(messages.data.all), _step3; !(_step3 = _iterator3()).done;) {
              var message = _step3.value;
              var msgClass = "wpstg--modal--process--msg--" + message.type.toLowerCase(); // TODO RPoC

              if (warnings.includes(message.type)) {
                $errorContainer.append("<p class=\"" + msgClass + "\">[" + message.type + "] - [" + message.date + "] - " + message.message + "</p>");
              }

              $logsContainer.append("<p class=\"" + msgClass + "\">[" + message.type + "] - [" + message.date + "] - " + message.message + "</p>");
            }
          },
          onOpen: function onOpen(container) {
            var $logsContainer = $(container).find('.wpstg--modal--process--logs');
            $logsContainer.scrollTop($logsContainer[0].scrollHeight);
          }
        });
      },
      downloadModal: function downloadModal(_ref3) {
        var _ref3$title = _ref3.title,
            title = _ref3$title === void 0 ? null : _ref3$title,
            _ref3$titleExport = _ref3.titleExport,
            titleExport = _ref3$titleExport === void 0 ? null : _ref3$titleExport,
            _ref3$id = _ref3.id,
            id = _ref3$id === void 0 ? null : _ref3$id,
            _ref3$url = _ref3.url,
            url = _ref3$url === void 0 ? null : _ref3$url,
            _ref3$btnTxtCancel = _ref3.btnTxtCancel,
            btnTxtCancel = _ref3$btnTxtCancel === void 0 ? 'Cancel' : _ref3$btnTxtCancel,
            _ref3$btnTxtConfirm = _ref3.btnTxtConfirm,
            btnTxtConfirm = _ref3$btnTxtConfirm === void 0 ? 'Download' : _ref3$btnTxtConfirm;

        if (null === that.backups.modal.download.html) {
          var $el = $('#wpstg--modal--backup--download');
          that.backups.modal.download.html = $el.html();
          $el.remove();
        }

        var exportModal = function exportModal() {
          return Swal.fire({
            html: "<h2>" + titleExport + "</h2><span class=\"wpstg-loader\"></span>",
            showCancelButton: false,
            showConfirmButton: false,
            onRender: function onRender() {
              that.ajax({
                action: 'wpstg--backups--export',
                accessToken: wpstg.accessToken,
                nonce: wpstg.nonce,
                id: id
              }, function (response) {
                if (!response || !response.success || !response.data || response.data.length < 1) {
                  return;
                }

                var a = document.createElement('a');
                a.style.display = 'none';
                a.href = response.data;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                Swal.close();
              });
            }
          });
        };

        Swal.mixin({
          customClass: {
            cancelButton: 'wpstg--btn--cancel wpstg-blue-primary wpstg-link-btn',
            confirmButton: 'wpstg--btn--confirm wpstg-blue-primary wpstg-button wpstg-link-btn',
            actions: 'wpstg--modal--actions'
          },
          buttonsStyling: false
        }).fire({
          icon: 'success',
          html: that.backups.modal.download.html.replace('{title}', title).replace('{btnTxtLog}', 'Show Logs'),
          cancelButtonText: btnTxtCancel,
          confirmButtonText: btnTxtConfirm,
          showCancelButton: true,
          showConfirmButton: true
        }).then(function (isConfirm) {
          if (!isConfirm || !isConfirm.value) {
            return;
          }

          if (url && url.length > 0) {
            window.location.href = url;
            return;
          }

          exportModal();
        });
      },
      importModal: function importModal() {
        var importSiteBackup = function importSiteBackup(data) {
          resetErrors();

          if (that.backups.isCancelled) {
            // Swal.close();
            return;
          }

          that.backups.timer.start();

          var statusStop = function statusStop() {
            clearInterval(that.backups.processInfo.interval);
            that.backups.processInfo.interval = null;
          };

          var status = function status() {
            if (that.backups.processInfo.interval !== null) {
              return;
            }

            that.backups.processInfo.interval = setInterval(function () {
              if (true === that.backups.isCancelled) {
                statusStop();
                return;
              }

              if (that.backups.status.hasResponse === false) {
                return;
              }

              that.backups.status.hasResponse = false;
              fetch(ajaxurl + "?action=wpstg--backups--status&process=restore&accessToken=" + wpstg.accessToken + "&nonce=" + wpstg.nonce).then(function (res) {
                return res.json();
              }).then(function (res) {
                that.backups.status.hasResponse = true;

                if (typeof res === 'undefined') {
                  statusStop();
                }

                if (that.backups.processInfo.title === res.currentStatusTitle) {
                  return;
                }

                that.backups.processInfo.title = res.currentStatusTitle;
                var $container = $(Swal.getContainer());
                $container.find('.wpstg--modal--process--title').text(res.currentStatusTitle);
                $container.find('.wpstg--modal--process--percent').text('0');
              })["catch"](function (e) {
                that.backups.status.hasResponse = true;
                showAjaxFatalError(e, '', 'Submit an error report.');
              });
            }, 5000);
          };

          WPStaging.ajax({
            action: 'wpstg--backups--import',
            accessToken: wpstg.accessToken,
            nonce: wpstg.nonce
          }, function (response) {
            if (typeof response === 'undefined') {
              setTimeout(function () {
                importSiteBackup();
              }, wpstg.delayReq);
              return;
            }

            that.backups.processResponse(response, true);

            if (!that.backups.processInfo.interval) {
              status();
            }

            if (response.status === false) {
              importSiteBackup();
            } else if (response.status === true) {
              $('#wpstg--progress--status').text('Backup successfully imported!');
              that.backups.type = null;

              if (that.backups.messages.shouldWarn()) {
                // noinspection JSIgnoredPromiseFromCall
                that.backups.fetchListing();
                that.backups.logsModal();
                return;
              }

              statusStop();
              var logEntries = $('.wpstg--modal--process--logs').get(1).innerHTML;
              var html = '<div class="wpstg--modal--process--logs">' + logEntries + '</div>';
              var issueFound = html.includes('wpstg--modal--process--msg--warning') || html.includes('wpstg--modal--process--msg--error') ? 'Issues(s) found! ' : ''; // var errorMessage = html.includes('wpstg--modal--process--msg--error') ? 'Errors(s) found! ' : '';
              // var Message = warningMessage + errorMessage;
              // Swal.close();

              Swal.fire({
                icon: 'success',
                title: 'Finished',
                html: 'System imported from backup. <br/><span class="wpstg--modal--process--msg-found">' + issueFound + '</span><button class="wpstg--modal--process--logs--tail" data-txt-bad="">Show Logs</button><br/>' + html
              }); // noinspection JSIgnoredPromiseFromCall

              that.backups.fetchListing();
            } else {
              setTimeout(function () {
                importSiteBackup();
              }, wpstg.delayReq);
            }
          }, 'json', false, 0, // Don't retry upon failure
          1.25);
        };

        if (!that.backups.modal["import"].html) {
          var $modal = $('#wpstg--modal--backup--import'); // Search & Replace Form

          var $form = $modal.find('.wpstg--modal--backup--import--search-replace--input--container');
          that.backups.modal["import"].searchReplaceForm = $form.html();
          $form.find('.wpstg--modal--backup--import--search-replace--input-group').remove();
          $form.html(that.backups.modal["import"].searchReplaceForm.replace(/{i}/g, 0));
          that.backups.modal["import"].html = $modal.html();
          that.backups.modal["import"].baseDirectory = $modal.attr('data-baseDirectory');
          that.backups.modal["import"].btnTxtNext = $modal.attr('data-nextButtonText');
          that.backups.modal["import"].btnTxtConfirm = $modal.attr('data-confirmButtonText');
          that.backups.modal["import"].btnTxtCancel = $modal.attr('data-cancelButtonText');
          $modal.remove();
        }

        that.backups.modal["import"].data.search = [];
        that.backups.modal["import"].data.replace = [];
        Swal.mixin({
          customClass: {
            confirmButton: 'wpstg--btn--confirm wpstg-blue-primary wpstg-button wpstg-link-btn',
            cancelButton: 'wpstg--btn--cancel wpstg-blue-primary wpstg-link-btn',
            actions: 'wpstg--modal--actions'
          },
          buttonsStyling: false // progressSteps: ['1', '2']

        }).queue([{
          html: that.backups.modal["import"].html,
          confirmButtonText: that.backups.modal["import"].btnTxtNext,
          showCancelButton: false,
          showConfirmButton: true,
          showLoaderOnConfirm: true,
          width: 650,
          onRender: function onRender() {
            // $('.wpstg--modal--actions .swal2-confirm').hide();
            // todo: hide this again
            $('.wpstg--modal--actions .swal2-confirm').show();
            $('.wpstg--modal--actions .swal2-confirm').prop('disabled', false);
            that.backups.modal["import"].containerUpload = $('.wpstg--modal--backup--import--upload');
            that.backups.modal["import"].containerFilesystem = $('.wpstg--modal--backup--import--filesystem');
            that.backups.modal["import"].containerConfigure = $('.wpstg--modal--backup--import--configure');
          },
          preConfirm: function preConfirm() {
            var body = new FormData();
            body.append('accessToken', wpstg.accessToken);
            body.append('nonce', wpstg.nonce);
            body.append('filePath', that.backups.modal["import"].data.file);
            that.backups.modal["import"].data.search.forEach(function (item, index) {
              body.append("search[" + index + "]", item);
            });
            that.backups.modal["import"].data.replace.forEach(function (item, index) {
              body.append("replace[" + index + "]", item);
            });
            return fetch(ajaxurl + "?action=wpstg--backups--import--file-info", {
              method: 'POST',
              body: body
            }).then(handleFetchErrors).then(function (res) {
              return res.json();
            }).then(function (html) {
              return Swal.insertQueueStep({
                html: html,
                confirmButtonText: that.backups.modal["import"].btnTxtConfirm,
                cancelButtonText: that.backups.modal["import"].btnTxtCancel,
                showCancelButton: true
              });
            })["catch"](function (e) {
              return showAjaxFatalError(e, '', 'Submit an error report.');
            });
          }
        }]).then(function (res) {
          if (!res || !res.value || !res.value[1] || res.value[1] !== true) {
            return;
          }

          that.backups.isCancelled = false;
          var data = that.backups.modal["import"].data;
          data['file'] = that.backups.modal["import"].baseDirectory + data['file'];
          WPStaging.ajax({
            action: 'wpstg--backups--prepare-import',
            accessToken: wpstg.accessToken,
            nonce: wpstg.nonce,
            wpstgImportData: data
          }, function (response) {
            if (response.success) {
              that.backups.process({
                execute: function execute() {
                  that.backups.messages.reset();
                  importSiteBackup();
                }
              });
            } else {
              showAjaxFatalError(response.data, '', 'Submit an error report.');
            }
          }, 'json', false, 0, 1.25);
        });
      }
    };
    window.addEventListener('backupListingFinished', function () {
      fetch(ajaxurl + "?action=wpstg--backups--import--file-list&_=" + Math.random() + "&accessToken=" + wpstg.accessToken + "&nonce=" + wpstg.nonce + "&withTemplate=true").then(handleFetchErrors).then(function (res) {
        return res.json();
      }).then(function (res) {
        var $ul = $('.wpstg-backup-list ul');
        $ul.empty();
        $ul.html(res);
      })["catch"](function (e) {
        return showAjaxFatalError(e, '', 'Submit an error report.');
      });
    });
    return that;
  }(jQuery);

  jQuery(document).ready(function () {
    WPStaging.init(); // This is necessary to make WPStaging var accessibile in WP Staging PRO js script

    window.WPStaging = WPStaging;
  });
  /**
   * Report Issue modal
   */

  jQuery(document).ready(function ($) {
    $('#wpstg-report-issue-button').on('click', function (e) {
      $('.wpstg-report-issue-form').toggleClass('wpstg-report-show');
      e.preventDefault();
    });
    $('body').on('click', '#wpstg-backups-report-issue-button', function (e) {
      $('.wpstg-report-issue-form').toggleClass('wpstg-report-show');
      e.preventDefault();
    });
    $('#wpstg-report-cancel').on('click', function (e) {
      $('.wpstg-report-issue-form').removeClass('wpstg-report-show');
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
      var email = $('.wpstg-report-email').val();
      var hosting_provider = $('.wpstg-report-hosting-provider').val();
      var message = $('.wpstg-report-description').val();
      var syslog = $('.wpstg-report-syslog').is(':checked');
      var terms = $('.wpstg-report-terms').is(':checked');
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
          $('.wpstg-report-issue-form .wpstg-message').remove();
          var errorMessage = $('<div />').addClass('wpstg-message wpstg-error-message');
          $.each(data.errors, function (key, value) {
            if (value.status === 'already_submitted') {
              errorMessage = '';
              Swal.fire({
                title: '',
                customClass: {
                  container: 'wpstg-issue-resubmit-confirmation'
                },
                icon: 'warning',
                html: value.message,
                showCloseButton: true,
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
          $('.wpstg-report-issue-form').prepend(errorMessage);
        } else {
          var successMessage = $('<div />').addClass('wpstg-message wpstg-success-message');
          successMessage.append('<p>Thanks for submitting your request! You should receive an auto reply mail with your ticket ID immediately for confirmation!<br><br>If you do not get that mail please contact us directly at <strong>support@wp-staging.com</strong></p>');
          $('.wpstg-report-issue-form').html(successMessage);
          $('.wpstg-success-message').append('<div style="float:right;margin-top:10px;"><a id="wpstg-success-button" href="#">Close</a></div>'); // Hide message

          setTimeout(function () {
            $('.wpstg-report-issue-form').removeClass('wpstg-report-active');
          }, 2000);
        }
      });
    }

    $('#wpstg-report-submit').on('click', function (e) {
      var self = $(this);
      sendIssueReport(self, 'false');
      e.preventDefault();
    }); // Open/close actions drop down menu

    $(document).on('click', '.wpstg-dropdown>.wpstg-dropdown-toggler', function (e) {
      e.preventDefault();
      $(e.target).next('.wpstg-dropdown-menu').toggleClass('shown');
    }); // Close action drop down menu if clicked anywhere outside

    document.addEventListener('click', function (event) {
      var isClickInside = event.target.closest('.wpstg-dropdown-toggler');

      if (!isClickInside) {
        var dropDown = document.getElementsByClassName('wpstg-dropdown-menu');

        for (var i = 0; i < dropDown.length; i++) {
          dropDown[i].classList.remove('shown');
        }
      }
    });
  });

}());
//# sourceMappingURL=wpstg-admin.js.map
