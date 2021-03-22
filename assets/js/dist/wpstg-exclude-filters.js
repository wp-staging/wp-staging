import * as dom from './wpstg-dom-utils.js';
/**
 * Rich Exclude Filter Module
 */

var WpstgExcludeFilters = /*#__PURE__*/function () {
  function WpstgExcludeFilters(excludeFilterTableSelector, wpstgObject) {
    if (excludeFilterTableSelector === void 0) {
      excludeFilterTableSelector = '#wpstg-exclude-filters-table';
    }

    if (wpstgObject === void 0) {
      wpstgObject = wpstg;
    }

    this.excludeTable = dom.qs(excludeFilterTableSelector);
    this.excludeTableBody = dom.qs(excludeFilterTableSelector + " > tbody");
    this.wpstgObject = wpstgObject;
    this.index = 0;
    this.rowCount = 0;
    this.init();
  }

  var _proto = WpstgExcludeFilters.prototype;

  _proto.addEvents = function addEvents() {
    var _this = this;

    dom.addEvent(this.excludeTable, 'click', '.wpstg-file-size-rule', function () {
      _this.addFileSizeExclude();
    });
    dom.addEvent(this.excludeTable, 'click', '.wpstg-file-ext-rule', function () {
      _this.addFileExtExclude();
    });
    dom.addEvent(this.excludeTable, 'click', '.wpstg-file-name-rule', function () {
      _this.addFileNameExclude();
    });
    dom.addEvent(this.excludeTable, 'click', '.wpstg-dir-name-rule', function () {
      _this.addDirNameExclude();
    });
    dom.addEvent(this.excludeTable, 'click', '.wpstg-remove-exclude-rule', function (target) {
      _this.removeExclude(target);
    });
  };

  _proto.init = function init() {
    if (this.excludeTable === null) {
      console.log('Error: Given table selector not found!');
      return;
    }

    if (this.excludeTableBody.hasAttribute('data-wpstg-rows-count')) {
      this.rowCount = this.excludeTableBody.getAttribute('data-wpstg-rows-count');
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
    this.index++;
    var excludeRowTemplate = dom.qs(templateName);

    if (excludeRowTemplate !== null) {
      var clone = excludeRowTemplate.content.cloneNode(true);
      var rowId = "wpstg-exclude-filter-" + this.index;
      var excludeRow = clone.querySelector('tr');
      excludeRow.setAttribute('id', rowId);
      var excludeRowRemoveButton = excludeRow.querySelector('button.wpstg-remove-exclude-rule');

      if (excludeRowRemoveButton !== null) {
        excludeRowRemoveButton.setAttribute('data-wpstg-remove-filter', "#" + rowId);
      }

      if (this.rowCount == 0) {
        this.excludeTableBody.innerText = '';
      }

      this.excludeTableBody.appendChild(excludeRow);
      this.rowCount++;
      this.excludeTableBody.setAttribute('data-wpstg-rows-count', this.rowCount);
    }
  };

  _proto.removeExclude = function removeExclude(target) {
    var containerSelector = target.getAttribute('data-wpstg-remove-filter');

    if (containerSelector !== null) {
      var container = dom.qs(containerSelector);
      this.excludeTableBody.removeChild(container);
      this.rowCount--;
      this.excludeTableBody.setAttribute('data-wpstg-rows-count', this.rowCount);

      if (this.rowCount === 0) {
        var _container = document.createElement('tr');

        var emptyData = document.createElement('td');
        emptyData.setAttribute('colspan', 3);
        emptyData.style['textAlign'] = 'center';
        emptyData.innerText = 'No Exclusion Rule';

        _container.appendChild(emptyData);

        this.excludeTableBody.appendChild(_container);
      }
    }
  }
  /**
   * Converts all the exclude filters arrays into one single string to keep size of post request small
   * @return {string}
   */
  ;

  _proto.getExcludeFilters = function getExcludeFilters() {
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
      if (x.value !== '') {
        globExcludes.push('/**/*.' + x.value.trim());
      }
    });
    var fileNamesPos = this.excludeTableBody.querySelectorAll('select[name="wpstgFileNameExcludeRulePos[]"]');
    var fileNames = this.excludeTableBody.querySelectorAll('input[name="wpstgFileNameExcludeRulePath[]"]');

    for (var _i2 = 0, _Object$entries2 = Object.entries(fileNames); _i2 < _Object$entries2.length; _i2++) {
      var _Object$entries2$_i = _Object$entries2[_i2],
          _key = _Object$entries2$_i[0],
          fileInput = _Object$entries2$_i[1];

      if (fileInput.value !== '') {
        globExcludes.push(this.makeGlob(fileInput.value.trim(), fileNamesPos[_key].value) + '.*');
      }
    }

    var dirNamesPos = this.excludeTableBody.querySelectorAll('select[name="wpstgDirNameExcludeRulePos[]"]');
    var dirNames = this.excludeTableBody.querySelectorAll('input[name="wpstgDirNameExcludeRulePath[]"]');

    for (var _i3 = 0, _Object$entries3 = Object.entries(dirNames); _i3 < _Object$entries3.length; _i3++) {
      var _Object$entries3$_i = _Object$entries3[_i3],
          _key2 = _Object$entries3$_i[0],
          dirInput = _Object$entries3$_i[1];

      if (dirInput.value !== '') {
        globExcludes.push(this.makeGlob(dirInput.value.trim(), dirNamesPos[_key2].value) + '/**');
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
  };

  _proto.makeGlob = function makeGlob(value, type) {
    if (type == 1) {
      value = value + '*';
    }

    if (type == 2) {
      value = '*' + value;
    }

    if (type == 4) {
      value = '*' + value + '*';
    }

    return '/**/' + value;
  };

  return WpstgExcludeFilters;
}();

export { WpstgExcludeFilters as default };