function _createForOfIteratorHelperLoose(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; return function () { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } it = o[Symbol.iterator](); return it.next.bind(it); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

(function ($) {
  window.addEventListener('database-backups-tab', function () {
    WPStagingLegacyDatabase.init();
  });
  var WPStagingLegacyDatabase = {
    type: null,
    isCancelled: false,
    processInfo: {
      title: null,
      interval: null
    },
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
    init: function init() {
      WPStagingLegacyDatabase.fetchListing();
      this.create();
      this["delete"]();
      this.restore();
      this.edit(); // noinspection JSIgnoredPromiseFromCall

      $('body').off('change', '#wpstg--backups--filter').on('change', '#wpstg--backups--filter', function () {
        var $records = $('#wpstg-existing-database-backups').find('> div[id][data-type].wpstg-backup');

        if (this.value === '') {
          $records.show();
        } else if (this.value === 'database') {
          $records.filter('[data-type="site"]').hide();
          $records.filter('[data-type="database"]').show();
        } else if (this.value === 'site') {
          $records.filter('[data-type="database"]').hide();
          $records.filter('[data-type="site"]').show();
        }
      }).on('click', '.wpstg--backup--download', function () {
        var url = this.getAttribute('data-url');

        if (url.length > 0) {
          window.location.href = url;
          return;
        }

        WPStagingLegacyDatabase.downloadModal({
          titleExport: this.getAttribute('data-title-export'),
          title: this.getAttribute('data-title'),
          id: this.getAttribute('data-id'),
          btnTxtCancel: this.getAttribute('data-btn-cancel-txt'),
          btnTxtConfirm: this.getAttribute('data-btn-download-txt')
        });
      }).off('click', '#wpstg-import-backup').on('click', '#wpstg-import-backup', function () {
        WPStagingLegacyDatabase.importModal();
      }) // Import
      .off('click', '.wpstg--backup--import--choose-option').on('click', '.wpstg--backup--import--choose-option', function () {
        var $parent = $(this).parent();

        if (!$parent.hasClass('wpstg--show-options')) {
          $parent.addClass('wpstg--show-options');
          $(this).text($(this).attr('data-txtChoose'));
        } else {
          $parent.removeClass('wpstg--show-options');
          $(this).text($(this).attr('data-txtOther'));
        }
      }).off('click', '.wpstg--modal--backup--import--search-replace--new').on('click', '.wpstg--modal--backup--import--search-replace--new', function (e) {
        e.preventDefault();
        var $container = $(Swal.getContainer()).find('.wpstg--modal--backup--import--search-replace--input--container');
        var total = $container.find('.wpstg--modal--backup--import--search-replace--input-group').length;
        $container.append(WPStagingLegacyDatabase.modal["import"].searchReplaceForm.replace(/{i}/g, total));
      }).off('input', '.wpstg--backup--import--search').on('input', '.wpstg--backup--import--search', function () {
        var index = parseInt(this.getAttribute('data-index'));

        if (!isNaN(index)) {
          WPStagingLegacyDatabase.modal["import"].data.search[index] = this.value;
        }
      }).off('input', '.wpstg--backup--import--replace').on('input', '.wpstg--backup--import--replace', function () {
        var index = parseInt(this.getAttribute('data-index'));

        if (!isNaN(index)) {
          WPStagingLegacyDatabase.modal["import"].data.replace[index] = this.value;
        }
      }) // Other Options
      .off('click', '.wpstg--backup--import--option[data-option]').on('click', '.wpstg--backup--import--option[data-option]', function () {
        var option = this.getAttribute('data-option');

        if (option === 'file') {
          $('input[type="file"][name="wpstg--backup--import--upload--file"]').click();
          return;
        }

        if (option === 'upload') {
          WPStagingLegacyDatabase.modal["import"].containerFilesystem.hide();
          WPStagingLegacyDatabase.modal["import"].containerUpload.show();
          $('.wpstg--backup--import--choose-option').click();
          $('.wpstg--modal--backup--import--search-replace--wrapper').show();
        }

        if (option !== 'filesystem') {
          return;
        }

        WPStagingLegacyDatabase.modal["import"].containerUpload.hide();
        var $containerFilesystem = WPStagingLegacyDatabase.modal["import"].containerFilesystem;
        $containerFilesystem.show();
        fetch(ajaxurl + "?action=wpstg--backups--import--file-list&_=" + Math.random() + "&accessToken=" + wpstg.accessToken + "&nonce=" + wpstg.nonce).then(WPStagingLegacyDatabase.handleFetchErrors).then(function (res) {
          return res.json();
        }).then(function (res) {
          var $ul = $('.wpstg--modal--backup--import--filesystem ul');
          $ul.empty();

          if (!res || WPStagingLegacyDatabase.isEmpty(res)) {
            $ul.append("<span id=\"wpstg--backups--import--file-list-empty\">No import file found! Upload an import file to the folder above.</span><br />");
            $('.wpstg--modal--backup--import--search-replace--wrapper').hide();
            return;
          }

          $ul.append("<span id=\"wpstg--backups--import--file-list\">Select file to import:</span><br />");
          res.forEach(function (file, index) {
            // var checked = (index === 0) ? 'checked' : '';
            $ul.append("<li><label><input name=\"backup_import_file\" type=\"radio\" value=\"" + file.fullPath + "\">" + file.name + " <br /> " + file.size + "</label></li>");
          }); // $('.wpstg--modal--actions .swal2-confirm').prop('disabled', false);

          return res;
        })["catch"](function (e) {
          return WPStagingLegacyDatabase.showAjaxFatalError(e, '', 'Submit an error report.');
        });
      }).off('change', 'input[type="file"][name="wpstg--backup--import--upload--file"]').on('change', 'input[type="file"][name="wpstg--backup--import--upload--file"]', function () {
        WPStagingLegacyDatabase.modal["import"].setFile(this.files[0] || null);
        $('.wpstg--backup--import--choose-option').click();
      }).off('change', 'input[type="radio"][name="backup_import_file"]').on('change', 'input[type="radio"][name="backup_import_file"]', function () {
        $('.wpstg--modal--actions .swal2-confirm').prop('disabled', false);
        WPStagingLegacyDatabase.modal["import"].data.file = this.value;
      }) // Drag & Drop
      .on('drag dragstart dragend dragover dragenter dragleave drop', '.wpstg--modal--backup--import--upload--container', function (e) {
        e.preventDefault();
        e.stopPropagation();
      }).on('dragover dragenter', '.wpstg--modal--backup--import--upload--container', function () {
        $(this).addClass('wpstg--has-dragover');
      }).on('dragleave dragend drop', '.wpstg--modal--backup--import--upload--container', function () {
        $(this).removeClass('wpstg--has-dragover');
      }).on('drop', '.wpstg--modal--backup--import--upload--container', function (e) {
        WPStagingLegacyDatabase.modal["import"].setFile(e.originalEvent.dataTransfer.files[0] || null);
      });
    },
    create: function create() {
      var createBackup = function createBackup(data) {
        WPStagingLegacyDatabase.resetErrors();

        if (WPStagingLegacyDatabase.isCancelled) {
          // Swal.close();
          return;
        }

        var reset = data['reset'];
        delete data['reset'];
        var requestData = Object.assign({}, data);
        var useResponseTitle = true;

        if (data.type === 'database') {
          WPStagingLegacyDatabase.type = data.type; // Only send to back-end what BE is expecting to receive.
          // Prevent error: Trying to hydrate DTO with value that does not exist.

          delete requestData['includedDirectories'];
          delete requestData['wpContentDir'];
          delete requestData['availableDirectories'];
          delete requestData['wpStagingDir'];
          delete requestData['exportDatabase'];
          delete requestData['includeOtherFilesInWpContent'];
          requestData = WPStagingLegacyDatabase.requestData('tasks.backup.database.create', _extends({}, requestData, {
            type: 'manual'
          }));
        } else if (data.type === 'site') {
          WPStagingLegacyDatabase.type = data.type; // Only send to back-end what BE is expecting to receive.
          // Prevent error: Trying to hydrate DTO with value that does not exist.

          delete requestData['type'];
          requestData = WPStagingLegacyDatabase.requestData('jobs.backup.site.create', requestData);
          useResponseTitle = false;
          requestData.jobs.backup.site.create.directories = [data.wpContentDir];
          requestData.jobs.backup.site.create.excludedDirectories = data.availableDirectories.split('|').filter(function (item) {
            return !data.includedDirectories.includes(item);
          }).map(function (item) {
            return item;
          });
          requestData.jobs.backup.site.create.includeOtherFilesInWpContent = [data.includeOtherFilesInWpContent]; // Do not exclude the wp-content/uploads/wp-staging using regex by default
          // This folder is excluded by PHP without REGEX.
          // requestData.jobs.backup.site.create.excludedDirectories.push(`#${data.wpStagingDir}*#`);
          // delete requestData.jobs.backup.site.create.includedDirectories;

          delete requestData.jobs.backup.site.create.wpContentDir;
          delete requestData.jobs.backup.site.create.wpStagingDir;
          delete requestData.jobs.backup.site.create.availableDirectories;
        } else {
          WPStagingLegacyDatabase.type = null;
          Swal.close();
          WPStagingLegacyDatabase.showError('Invalid Backup Type');
          return;
        }

        WPStagingLegacyDatabase.timer.start();

        var statusStop = function statusStop() {
          console.log('Status: Stop');
          clearInterval(WPStagingLegacyDatabase.processInfo.interval);
          WPStagingLegacyDatabase.processInfo.interval = null;
        };

        var status = function status() {
          if (WPStagingLegacyDatabase.processInfo.interval !== null) {
            return;
          }

          console.log('Status: Start');
          WPStagingLegacyDatabase.processInfo.interval = setInterval(function () {
            if (true === WPStagingLegacyDatabase.isCancelled) {
              statusStop();
              return;
            }

            if (WPStagingLegacyDatabase.status.hasResponse === false) {
              return;
            }

            WPStagingLegacyDatabase.status.hasResponse = false;
            fetch(ajaxurl + "?action=wpstg--backups--status&accessToken=" + wpstg.accessToken + "&nonce=" + wpstg.nonce).then(function (res) {
              return res.json();
            }).then(function (res) {
              WPStagingLegacyDatabase.status.hasResponse = true;

              if (typeof res === 'undefined') {
                statusStop();
              }

              if (WPStagingLegacyDatabase.processInfo.title === res.currentStatusTitle) {
                return;
              }

              WPStagingLegacyDatabase.processInfo.title = res.currentStatusTitle;
              var $container = $(Swal.getContainer());
              $container.find('.wpstg--modal--process--title').text(res.currentStatusTitle);
              $container.find('.wpstg--modal--process--percent').text('0');
            })["catch"](function (e) {
              WPStagingLegacyDatabase.status.hasResponse = true;
              WPStagingLegacyDatabase.showAjaxFatalError(e, '', 'Submit an error report.');
            });
          }, 5000);
        };

        WPStaging.ajax({
          action: 'wpstg--backups--database-legacy-create',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
          reset: reset,
          wpstg: requestData
        }, function (response) {
          if (typeof response === 'undefined') {
            setTimeout(function () {
              createBackup(data);
            }, wpstg.delayReq);
            return;
          }

          WPStagingLegacyDatabase.processResponse(response, useResponseTitle);

          if (!useResponseTitle && !WPStagingLegacyDatabase.processInfo.interval) {
            status();
          }

          if (response.status === false) {
            createBackup(data);
          } else if (response.status === true) {
            $('#wpstg--progress--status').text('Backup successfully created!');
            WPStagingLegacyDatabase.type = null;

            if (WPStagingLegacyDatabase.messages.shouldWarn()) {
              // noinspection JSIgnoredPromiseFromCall
              WPStagingLegacyDatabase.fetchListing();
              WPStagingLegacyDatabase.logsModal();
              return;
            }

            statusStop();
            Swal.close();
            WPStagingLegacyDatabase.fetchListing().then(function () {
              if (!response.backupId) {
                WPStagingLegacyDatabase.showError('Failed to get backup ID from response');
                return;
              } // TODO RPoC


              var $el = $(".wpstg--backup--download[data-id=\"" + response.backupId + "\"]");
              WPStagingLegacyDatabase.downloadModal({
                id: $el.data('id'),
                url: $el.data('url'),
                title: $el.data('title'),
                titleExport: $el.data('title-export'),
                btnTxtCancel: $el.data('btn-cancel-txt'),
                btnTxtConfirm: $el.data('btn-download-txt')
              });
              $('.wpstg--modal--download--logs--wrapper').show();
              var $logsContainer = $('.wpstg--modal--process--logs');
              WPStagingLegacyDatabase.messages.data.all.forEach(function (message) {
                var msgClass = "wpstg--modal--process--msg--" + message.type.toLowerCase();
                $logsContainer.append("<p class=\"" + msgClass + "\">[" + message.type + "] - [" + message.date + "] - " + message.message + "</p>");
              });
            });
          } else {
            setTimeout(function () {
              createBackup(data);
            }, wpstg.delayReq);
          }
        }, 'json', false, 0, // Don't retry upon failure
        1.25);
      };

      var $body = $('body');
      $body.off('click', 'input[name="backup_type"]').on('click', 'input[name="backup_type"]', function () {
        var advancedOptions = $('.wpstg-advanced-options');

        if (this.value === 'database') {
          advancedOptions.hide();
          return;
        }

        advancedOptions.show();
      }).off('click', '.wpstg--tab--toggle').on('click', '.wpstg--tab--toggle', function () {
        var $target = $($(this).attr('data-target'));
        $target.toggle();

        if ($target.is(':visible')) {
          $(this).find('span').text('▼');
        } else {
          $(this).find('span').text('►');
        }
      }).off('change', '[name="includedDirectories\[\]"], [type="checkbox"][name="export_database"]').on('change', '[type="checkbox"][name="includedDirectories\[\]"], [type="checkbox"][name="export_database"]', function () {
        var totalDirs = $('[type="checkbox"][name="includedDirectories\[\]"]:checked').length;
        var isExportDatabase = $('[type="checkbox"][name="export_database"]:checked').length === 1;

        if (totalDirs < 1 && !isExportDatabase) {
          $('.swal2-confirm').prop('disabled', true);
        } else {
          $('.swal2-confirm').prop('disabled', false);
        }
      }); // Add backup name and notes

      $('#wpstg--tab--database-backups').off('click', '#wpstg-new-database-backup').on('click', '#wpstg-new-database-backup', /*#__PURE__*/function () {
        var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(e) {
          var $newBackupModal, html, btnTxt, _yield$Swal$fire, formValues;

          return regeneratorRuntime.wrap(function _callee$(_context) {
            while (1) {
              switch (_context.prev = _context.next) {
                case 0:
                  WPStagingLegacyDatabase.resetErrors();
                  e.preventDefault();
                  WPStagingLegacyDatabase.isCancelled = false;

                  if (!WPStagingLegacyDatabase.modal.create.html || !WPStagingLegacyDatabase.modal.create.confirmBtnTxt) {
                    $newBackupModal = $('#wpstg--modal--database--new');
                    html = $newBackupModal.html();
                    btnTxt = $newBackupModal.attr('data-confirmButtonText');
                    WPStagingLegacyDatabase.modal.create.html = html || null;
                    WPStagingLegacyDatabase.modal.create.confirmBtnTxt = btnTxt || null;
                    $newBackupModal.remove();
                  }

                  _context.next = 6;
                  return Swal.fire({
                    title: '',
                    html: WPStagingLegacyDatabase.modal.create.html,
                    focusConfirm: false,
                    confirmButtonText: WPStagingLegacyDatabase.modal.create.confirmBtnTxt,
                    showCancelButton: true,
                    preConfirm: function preConfirm() {
                      var container = Swal.getContainer();
                      return {
                        type: 'database',
                        name: container.querySelector('input[name="backup_name"]').value || null,
                        notes: container.querySelector('textarea[name="backup_note"]').value || null,
                        includedDirectories: Array.from(container.querySelectorAll('input[name="includedDirectories\\[\\]"]:checked') || []).map(function (i) {
                          return i.value;
                        }),
                        wpContentDir: container.querySelector('input[name="wpContentDir"]').value || null,
                        availableDirectories: container.querySelector('input[name="availableDirectories"]').value || null,
                        wpStagingDir: container.querySelector('input[name="wpStagingDir"]').value || null,
                        exportDatabase: container.querySelector('input[name="export_database"]:checked') !== null,
                        includeOtherFilesInWpContent: container.querySelector('input[name="includeOtherFilesInWpContent"]:checked') !== null
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
                  formValues.reset = true;
                  WPStagingLegacyDatabase.process({
                    execute: function execute() {
                      WPStagingLegacyDatabase.messages.reset();
                      createBackup(formValues);
                    }
                  });

                case 12:
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
    isEmpty: function isEmpty(obj) {
      for (var prop in obj) {
        if (obj.hasOwnProperty(prop)) {
          return false;
        }
      }

      return true;
    },
    isLoading: function isLoading(_isLoading) {
      if (!_isLoading || _isLoading === false) {
        WPStagingLegacyDatabase.cache.get('.wpstg-loader').hide();
      } else {
        WPStagingLegacyDatabase.cache.get('.wpstg-loader').show();
      }
    },
    showAjaxFatalError: function showAjaxFatalError(response, prependMessage, appendMessage) {
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
    },
    handleFetchErrors: function handleFetchErrors(response) {
      if (!response.ok) {
        showError('Error: ' + response.status + ' - ' + response.statusText + '. Please try again or contact support.');
      }

      return response;
    },
    showError: function showError(message) {
      WPStagingLegacyDatabase.cache.get('#wpstg-try-again').css('display', 'inline-block');
      WPStagingLegacyDatabase.cache.get('#wpstg-cancel-cloning').text('Reset');
      WPStagingLegacyDatabase.cache.get('#wpstg-resume-cloning').show();
      WPStagingLegacyDatabase.cache.get('#wpstg-error-wrapper').show();
      WPStagingLegacyDatabase.cache.get('#wpstg-error-details').show().html(message);
      WPStagingLegacyDatabase.cache.get('#wpstg-removing-clone').removeClass('loading');
      WPStagingLegacyDatabase.cache.get('.wpstg-loader').hide();
      $('.wpstg--modal--process--generic-problem').show().html(message);
    },
    resetErrors: function resetErrors() {
      WPStagingLegacyDatabase.cache.get('#wpstg-error-details').hide().html('');
    },

    /**
    * Ajax Requests
    * @param {Object} data
    * @param {Function} callback
    * @param {string} dataType
    * @param {bool} showErrors
    * @param {int} tryCount
    * @param {float} incrementRatio
    */
    ajax: function ajax(data, callback, dataType, showErrors, tryCount, incrementRatio) {
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
          console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus); // try again after 10 seconds

          tryCount++;

          if (tryCount <= retryLimit) {
            setTimeout(function () {
              WPStagingLegacyDatabase.ajax(data, callback, dataType, showErrors, tryCount, incrementRatio);
              return;
            }, retryTimeout);
          } else {
            var errorCode = 'undefined' === typeof xhr.status ? 'Unknown' : xhr.status;
            WPStagingLegacyDatabase.showError('Fatal Error:  ' + errorCode + ' Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.');
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
              WPStagingLegacyDatabase.showError('Error 404 - Can\'t find ajax request URL! Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.');
            }
          },
          500: function _() {
            if (tryCount >= retryLimit) {
              WPStagingLegacyDatabase.showError('Fatal Error 500 - Internal server error while processing the request! Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.');
            }
          },
          504: function _() {
            if (tryCount > retryLimit) {
              WPStagingLegacyDatabase.showError('Error 504 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ');
            }
          },
          502: function _() {
            if (tryCount >= retryLimit) {
              WPStagingLegacyDatabase.showError('Error 502 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ');
            }
          },
          503: function _() {
            if (tryCount >= retryLimit) {
              WPStagingLegacyDatabase.showError('Error 503 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ');
            }
          },
          429: function _() {
            if (tryCount >= retryLimit) {
              WPStagingLegacyDatabase.showError('Error 429 - It looks like your server is rate limiting ajax requests. Please try to resume after a minute. If this still not works try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report and contact us.\n\ ');
            }
          },
          403: function _() {
            if (tryCount >= retryLimit) {
              WPStagingLegacyDatabase.showError('Refresh page or login again! The process should be finished successfully. \n\ ');
            }
          }
        }
      });
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

          WPStagingLegacyDatabase.modal["import"].file = file;
          WPStagingLegacyDatabase.modal["import"].data.file = file.name;
          console.log("File " + file.name);
          $('.wpstg--backup--import--selected-file').html(file.name + " <br /> (" + toUnit(file.size) + ")").show();
          $('.wpstg--drag').hide();
          $('.wpstg--drag-or-upload').show();

          if (upload) {
            $('.wpstg--modal--actions .swal2-confirm').prop('disabled', true);
            WPStagingLegacyDatabase.upload.start();
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
        return WPStagingLegacyDatabase.messages.data.error.length > 0 || WPStagingLegacyDatabase.messages.data.critical.length > 0;
      },
      countByType: function countByType(type) {
        if (type === void 0) {
          type = WPStagingLegacyDatabase.messages.ERROR;
        }

        return WPStagingLegacyDatabase.messages.data[type].length;
      },
      addMessage: function addMessage(message) {
        if (Array.isArray(message)) {
          message.forEach(function (item) {
            WPStagingLegacyDatabase.messages.addMessage(item);
          });
          return;
        }

        var type = message.type.toLowerCase() || 'info';

        if (!WPStagingLegacyDatabase.messages.data[type]) {
          WPStagingLegacyDatabase.messages.data[type] = [];
        }

        WPStagingLegacyDatabase.messages.data.all.push(message); // TODO RPoC

        WPStagingLegacyDatabase.messages.data[type].push(message);
      },
      reset: function reset() {
        WPStagingLegacyDatabase.messages.data = {
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
        if (null !== WPStagingLegacyDatabase.timer.interval) {
          return;
        }

        var prettify = function prettify(seconds) {
          console.log("Process running for " + seconds + " seconds"); // If potentially anything can exceed 24h execution time than that;
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

        WPStagingLegacyDatabase.timer.interval = setInterval(function () {
          $('.wpstg--modal--process--elapsed-time').text(prettify(WPStagingLegacyDatabase.timer.totalSeconds));
          WPStagingLegacyDatabase.timer.totalSeconds++;
        }, 1000);
      },
      stop: function stop() {
        WPStagingLegacyDatabase.timer.totalSeconds = 0;

        if (WPStagingLegacyDatabase.timer.interval) {
          clearInterval(WPStagingLegacyDatabase.timer.interval);
          WPStagingLegacyDatabase.timer.interval = null;
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
        console.log("file " + WPStagingLegacyDatabase.modal["import"].data.file);
        WPStagingLegacyDatabase.upload.reader = new FileReader();
        WPStagingLegacyDatabase.upload.file = WPStagingLegacyDatabase.modal["import"].file;
        WPStagingLegacyDatabase.upload.uploadInfo(true);
        WPStagingLegacyDatabase.upload.sendChunk();
      },
      sendChunk: function sendChunk(startsAt) {
        if (startsAt === void 0) {
          startsAt = 0;
        }

        if (!WPStagingLegacyDatabase.upload.file) {
          return;
        }

        var isReset = startsAt < 1;
        var endsAt = startsAt + WPStagingLegacyDatabase.upload.iop + 1;
        var blob = WPStagingLegacyDatabase.upload.file.slice(startsAt, endsAt);

        WPStagingLegacyDatabase.upload.reader.onloadend = function (event) {
          if (event.target.readyState !== FileReader.DONE) {
            return;
          }

          var body = new FormData();
          body.append('accessToken', wpstg.accessToken);
          body.append('nonce', wpstg.nonce);
          body.append('data', event.target.result);
          body.append('filename', WPStagingLegacyDatabase.upload.file.name);
          body.append('reset', isReset ? '1' : '0');
          fetch(ajaxurl + "?action=wpstg--backups--import--file-upload", {
            method: 'POST',
            body: body
          }).then(WPStagingLegacyDatabase.handleFetchErrors).then(function (res) {
            return res.json();
          }).then(function (res) {
            WPStagingLegacyDatabase.showAjaxFatalError(res, '', 'Submit an error report.');
            var writtenBytes = startsAt + WPStagingLegacyDatabase.upload.iop;
            var percent = Math.floor(writtenBytes / WPStagingLegacyDatabase.upload.file.size * 100);

            if (endsAt >= WPStagingLegacyDatabase.upload.file.size) {
              WPStagingLegacyDatabase.upload.uploadInfo(false);
              WPStagingLegacyDatabase.isLoading(false);
              $('.wpstg--modal--actions .swal2-confirm').prop('disabled', false);
              return;
            }

            $('.wpstg--modal--import--upload--progress--title > span').text(percent);
            $('.wpstg--modal--import--upload--progress').css('width', percent + "%");
            WPStagingLegacyDatabase.upload.sendChunk(endsAt);
          })["catch"](function (e) {
            return WPStagingLegacyDatabase.showAjaxFatalError(e, '', 'Submit an error report.');
          });
        };

        WPStagingLegacyDatabase.upload.reader.readAsDataURL(blob);
      }
    },
    status: {
      hasResponse: null,
      reTryAfter: 5000
    },
    fetchListing: function fetchListing(isResetErrors) {
      if (isResetErrors === void 0) {
        isResetErrors = true;
      }

      WPStagingLegacyDatabase.isLoading(true);

      if (isResetErrors) {
        WPStagingLegacyDatabase.resetErrors();
      }

      return fetch(ajaxurl + "?action=wpstg--backups--database-legacy-listing&_=" + Math.random() + "&accessToken=" + wpstg.accessToken + "&nonce=" + wpstg.nonce).then(WPStagingLegacyDatabase.handleFetchErrors).then(function (res) {
        return res.json();
      }).then(function (res) {
        WPStagingLegacyDatabase.showAjaxFatalError(res, '', 'Submit an error report.');
        WPStagingLegacyDatabase.cache.get('#wpstg--tab--database-backups').html(res);
        WPStagingLegacyDatabase.isLoading(false);
        return res;
      })["catch"](function (e) {
        return WPStagingLegacyDatabase.showAjaxFatalError(e, '', 'Submit an error report.');
      });
    },
    "delete": function _delete() {
      $('#wpstg--tab--database-backups').off('click', '.wpstg-delete-backup[data-id]').on('click', '.wpstg-delete-backup[data-id]', function (e) {
        e.preventDefault();
        WPStagingLegacyDatabase.resetErrors();
        WPStagingLegacyDatabase.isLoading(true);
        WPStagingLegacyDatabase.cache.get('#wpstg-existing-database-backups').hide();
        var id = this.getAttribute('data-id');
        WPStagingLegacyDatabase.ajax({
          action: 'wpstg--backups--database-legacy-delete-confirm',
          id: id,
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce
        }, function (response) {
          WPStagingLegacyDatabase.showAjaxFatalError(response, '', ' Please submit an error report by using the REPORT ISSUE button.');
          WPStagingLegacyDatabase.isLoading(false);
          WPStagingLegacyDatabase.cache.get('#wpstg-delete-confirmation').html(response);
        });
      }) // Delete final confirmation page
      .off('click', '#wpstg-delete-backup').on('click', '#wpstg-delete-backup', function (e) {
        e.preventDefault();
        WPStagingLegacyDatabase.resetErrors();
        WPStagingLegacyDatabase.isLoading(true);
        var id = this.getAttribute('data-id');
        WPStagingLegacyDatabase.ajax({
          action: 'wpstg--backups--database-legacy-delete',
          id: id,
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce
        }, function (response) {
          WPStagingLegacyDatabase.showAjaxFatalError(response, '', ' Please submit an error report by using the REPORT ISSUE button.'); // noinspection JSIgnoredPromiseFromCall

          WPStagingLegacyDatabase.fetchListing();
          WPStagingLegacyDatabase.isLoading(false);
        });
      }).off('click', '#wpstg-cancel-backup-delete').on('click', '#wpstg-cancel-backup-delete', function (e) {
        e.preventDefault();
        WPStagingLegacyDatabase.isLoading(false); // noinspection JSIgnoredPromiseFromCall

        WPStagingLegacyDatabase.fetchListing();
      }); // Force delete if backup tables do not exist
      // TODO This is bloated, no need extra ID, use existing one?

      $('#wpstg-error-wrapper').off('click', '#wpstg-backup-force-delete').on('click', '#wpstg-backup-force-delete', function (e) {
        e.preventDefault();
        WPStagingLegacyDatabase.resetErrors();
        WPStagingLegacyDatabase.isLoading(true);
        var id = this.getAttribute('data-id');

        if (!confirm('Do you want to delete this backup ' + id + ' from the listed backups?')) {
          WPStagingLegacyDatabase.isLoading(false);
          return false;
        }

        WPStagingLegacyDatabase.ajax({
          action: 'wpstg--backups--database-legacy-delete',
          id: id,
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce
        }, function (response) {
          WPStagingLegacyDatabase.showAjaxFatalError(response, '', ' Please submit an error report by using the REPORT ISSUE button.'); // noinspection JSIgnoredPromiseFromCall

          WPStagingLegacyDatabase.fetchListing();
          WPStagingLegacyDatabase.isLoading(false);
        });
      });
    },
    restore: function restore() {
      var restoreBackup = function restoreBackup(prefix, reset) {
        WPStagingLegacyDatabase.isLoading(true);
        WPStagingLegacyDatabase.resetErrors();

        if (typeof reset === 'undefined') {
          reset = false;
        }

        WPStaging.ajax({
          action: 'wpstg--backups--database-legacy-restore',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
          wpstg: {
            tasks: {
              backup: {
                database: {
                  create: {
                    source: prefix,
                    reset: reset
                  }
                }
              }
            }
          }
        }, function (response) {
          if (typeof response === 'undefined') {
            setTimeout(function () {
              restoreBackup(prefix);
            }, wpstg.delayReq);
            return;
          }

          WPStagingLegacyDatabase.processResponse(response);

          if (response.status === false || response.job_done === false) {
            restoreBackup(prefix);
          } else if (response.status === true && response.job_done === true) {
            WPStagingLegacyDatabase.isLoading(false);
            $('.wpstg--modal--process--title').text('Backup successfully restored');
            setTimeout(function () {
              Swal.close(); // noinspection JSIgnoredPromiseFromCall

              alert('Backup successfully restored. Please log-in again to WordPress.');
              WPStagingLegacyDatabase.fetchListing();
            }, 1000);
          } else {
            setTimeout(function () {
              restoreBackup(prefix);
            }, wpstg.delayReq);
          }
        }, 'json', false, 0, 1.25);
      };

      $('#wpstg--tab--database-backups').off('click', '.wpstg--backup--restore[data-id]').on('click', '.wpstg--backup--restore[data-id]', function (e) {
        e.preventDefault();
        WPStagingLegacyDatabase.resetErrors();
        WPStagingLegacyDatabase.ajax({
          action: 'wpstg--backups--database-legacy-restore-confirm',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
          id: $(this).data('id')
        }, function (data) {
          WPStagingLegacyDatabase.cache.get('#wpstg--tab--database-backups').html(data);
        });
      }).off('click', '#wpstg--backup--restore--cancel').on('click', '#wpstg--backup--restore--cancel', function (e) {
        WPStagingLegacyDatabase.resetErrors();
        e.preventDefault(); // noinspection JSIgnoredPromiseFromCall

        WPStagingLegacyDatabase.fetchListing();
      }).off('click', '#wpstg--backup--restore[data-id]').on('click', '#wpstg--backup--restore[data-id]', function (e) {
        e.preventDefault();
        WPStagingLegacyDatabase.resetErrors();
        var id = this.getAttribute('data-id');
        WPStagingLegacyDatabase.process({
          execute: function execute() {
            WPStagingLegacyDatabase.messages.reset();
            restoreBackup(id, true);
          },
          isShowCancelButton: false
        });
      });
    },
    // Edit backups name and notes
    edit: function edit() {
      $('#wpstg--tab--database-backups').off('click', '.wpstg--backup--edit[data-id]').on('click', '.wpstg--backup--edit[data-id]', /*#__PURE__*/function () {
        var _ref2 = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2(e) {
          var name, notes, _yield$Swal$fire2, formValues;

          return regeneratorRuntime.wrap(function _callee2$(_context2) {
            while (1) {
              switch (_context2.prev = _context2.next) {
                case 0:
                  e.preventDefault();
                  name = $(this).data('name');
                  notes = $(this).data('notes');
                  _context2.next = 5;
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

                case 5:
                  _yield$Swal$fire2 = _context2.sent;
                  formValues = _yield$Swal$fire2.value;

                  if (formValues) {
                    _context2.next = 9;
                    break;
                  }

                  return _context2.abrupt("return");

                case 9:
                  WPStagingLegacyDatabase.ajax({
                    action: 'wpstg--backups--database-legacy-edit',
                    accessToken: wpstg.accessToken,
                    nonce: wpstg.nonce,
                    id: $(this).data('id'),
                    name: formValues.name,
                    notes: formValues.notes
                  }, function (response) {
                    WPStagingLegacyDatabase.showAjaxFatalError(response, '', 'Submit an error report.'); // noinspection JSIgnoredPromiseFromCall

                    WPStagingLegacyDatabase.fetchListing();
                  });

                case 10:
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
      WPStagingLegacyDatabase.timer.stop();
      WPStagingLegacyDatabase.isCancelled = true;
      Swal.close();
      setTimeout(function () {
        return WPStagingLegacyDatabase.ajax({
          action: 'wpstg--backups--cancel',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
          type: WPStagingLegacyDatabase.type
        }, function (response) {
          WPStagingLegacyDatabase.showAjaxFatalError(response, '', 'Submit an error report.');
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
        WPStagingLegacyDatabase.showError('process.data and / or process.onResponse is not set');
        return;
      } // TODO move to backend and get the contents as xhr response?


      if (!WPStagingLegacyDatabase.modal.process.html || !WPStagingLegacyDatabase.modal.process.cancelBtnTxt) {
        var $modal = $('#wpstg--modal--backup--process');
        var html = $modal.html();
        var btnTxt = $modal.attr('data-cancelButtonText');
        WPStagingLegacyDatabase.modal.process.html = html || null;
        WPStagingLegacyDatabase.modal.process.cancelBtnTxt = btnTxt || null;
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
      WPStagingLegacyDatabase.modal.process.modal = Swal.mixin({
        customClass: {
          cancelButton: 'wpstg--btn--cancel wpstg-blue-primary wpstg-link-btn',
          content: 'wpstg--process--content'
        },
        buttonsStyling: false
      }).fire({
        html: WPStagingLegacyDatabase.modal.process.html,
        cancelButtonText: WPStagingLegacyDatabase.modal.process.cancelBtnTxt,
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
            WPStagingLegacyDatabase.showError('process.data and / or process.onResponse is not set');
            return;
          }

          WPStagingLegacyDatabase.ajax(_process.data, _process.onResponse);
        },
        onAfterClose: function onAfterClose() {
          return typeof _process.onAfterClose === 'function' && _process.onAfterClose();
        },
        onClose: function onClose() {
          console.log('cancelled');
          WPStagingLegacyDatabase.cancel();
        }
      });
    },
    processResponse: function processResponse(response, useTitle) {
      if (response === null) {
        Swal.close();
        WPStagingLegacyDatabase.showError('Invalid Response; null');
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
        var stoppingTypes = [WPStagingLegacyDatabase.messages.ERROR, WPStagingLegacyDatabase.messages.CRITICAL];

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
            WPStagingLegacyDatabase.cancel();
            setTimeout(WPStagingLegacyDatabase.logsModal, 500);
          }
        };

        for (var _iterator2 = _createForOfIteratorHelperLoose(response.messages), _step2; !(_step2 = _iterator2()).done;) {
          var message = _step2.value;

          if (!message) {
            continue;
          }

          WPStagingLegacyDatabase.messages.addMessage(message);
          appendMessage(message);
        }

        if ($logsContainer.is(':visible')) {
          $logsContainer.scrollTop($logsContainer[0].scrollHeight);
        }

        if (!WPStagingLegacyDatabase.messages.shouldWarn()) {
          return;
        }

        var $btnShowLogs = $container.find('.wpstg--modal--process--logs--tail');
        $btnShowLogs.html($btnShowLogs.attr('data-txt-bad'));
        $btnShowLogs.find('.wpstg--modal--logs--critical-count').text(WPStagingLegacyDatabase.messages.countByType(WPStagingLegacyDatabase.messages.CRITICAL));
        $btnShowLogs.find('.wpstg--modal--logs--error-count').text(WPStagingLegacyDatabase.messages.countByType(WPStagingLegacyDatabase.messages.ERROR));
        $btnShowLogs.find('.wpstg--modal--logs--warning-count').text(WPStagingLegacyDatabase.messages.countByType(WPStagingLegacyDatabase.messages.WARNING));
      };

      title();
      percentage();
      logs();

      if (response.status === true && response.job_done === true) {
        WPStagingLegacyDatabase.timer.stop();
        WPStagingLegacyDatabase.isCancelled = true;
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
          var messages = WPStagingLegacyDatabase.messages;
          var title = $translations.attr('data-modal-logs-title').replace('{critical}', messages.countByType(messages.CRITICAL)).replace('{errors}', messages.countByType(messages.ERROR)).replace('{warnings}', messages.countByType(messages.WARNING));
          $errorContainer.before("<h3>" + title + "</h3>");
          var warnings = [WPStagingLegacyDatabase.messages.CRITICAL, WPStagingLegacyDatabase.messages.ERROR, WPStagingLegacyDatabase.messages.WARNING];

          if (!WPStagingLegacyDatabase.messages.shouldWarn()) {
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

      if (null === WPStagingLegacyDatabase.modal.download.html) {
        var $el = $('#wpstg--modal--backup--download');
        WPStagingLegacyDatabase.modal.download.html = $el.html();
        $el.remove();
      }

      var exportModal = function exportModal() {
        return Swal.fire({
          html: "<h2>" + titleExport + "</h2><span class=\"wpstg-loader\"></span>",
          showCancelButton: false,
          showConfirmButton: false,
          onRender: function onRender() {
            WPStagingLegacyDatabase.ajax({
              action: 'wpstg--backups--database-legacy-export',
              accessToken: wpstg.accessToken,
              nonce: wpstg.nonce,
              id: id
            }, function (response) {
              console.log(response);

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
        html: WPStagingLegacyDatabase.modal.download.html.replace('{title}', title).replace('{btnTxtLog}', 'Show Logs'),
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
      var restoreSiteBackup = function restoreSiteBackup(data) {
        WPStagingLegacyDatabase.resetErrors();

        if (WPStagingLegacyDatabase.isCancelled) {
          console.log('cancelled'); // Swal.close();

          return;
        }

        var reset = data['reset'];
        delete data['reset'];
        data['mergeMediaFiles'] = 1; // always merge for uploads / media

        var requestData = Object.assign({}, data);
        requestData = WPStagingLegacyDatabase.requestData('jobs.backup.site.restore', _extends({}, WPStagingLegacyDatabase.modal["import"].data));
        WPStagingLegacyDatabase.timer.start();

        var statusStop = function statusStop() {
          console.log('Status: Stop');
          clearInterval(WPStagingLegacyDatabase.processInfo.interval);
          WPStagingLegacyDatabase.processInfo.interval = null;
        };

        var status = function status() {
          if (WPStagingLegacyDatabase.processInfo.interval !== null) {
            return;
          }

          console.log('Status: Start');
          WPStagingLegacyDatabase.processInfo.interval = setInterval(function () {
            if (true === WPStagingLegacyDatabase.isCancelled) {
              statusStop();
              return;
            }

            if (WPStagingLegacyDatabase.status.hasResponse === false) {
              return;
            }

            WPStagingLegacyDatabase.status.hasResponse = false;
            fetch(ajaxurl + "?action=wpstg--backups--status&process=restore&accessToken=" + wpstg.accessToken + "&nonce=" + wpstg.nonce).then(function (res) {
              return res.json();
            }).then(function (res) {
              WPStagingLegacyDatabase.status.hasResponse = true;

              if (typeof res === 'undefined') {
                statusStop();
              }

              if (WPStagingLegacyDatabase.processInfo.title === res.currentStatusTitle) {
                return;
              }

              WPStagingLegacyDatabase.processInfo.title = res.currentStatusTitle;
              var $container = $(Swal.getContainer());
              $container.find('.wpstg--modal--process--title').text(res.currentStatusTitle);
              $container.find('.wpstg--modal--process--percent').text('0');
            })["catch"](function (e) {
              WPStagingLegacyDatabase.status.hasResponse = true;
              WPStagingLegacyDatabase.showAjaxFatalError(e, '', 'Submit an error report.');
            });
          }, 5000);
        };

        WPStaging.ajax({
          action: 'wpstg--backups--database-legacy-restore',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
          reset: reset,
          wpstg: requestData
        }, function (response) {
          if (typeof response === 'undefined') {
            setTimeout(function () {
              restoreSiteBackup(data);
            }, wpstg.delayReq);
            return;
          }

          WPStagingLegacyDatabase.processResponse(response, true);

          if (!WPStagingLegacyDatabase.processInfo.interval) {
            status();
          }

          if (response.status === false) {
            restoreSiteBackup(data);
          } else if (response.status === true) {
            $('#wpstg--progress--status').text('Backup successfully restored!');
            WPStagingLegacyDatabase.type = null;

            if (WPStagingLegacyDatabase.messages.shouldWarn()) {
              // noinspection JSIgnoredPromiseFromCall
              WPStagingLegacyDatabase.fetchListing();
              WPStagingLegacyDatabase.logsModal();
              return;
            }

            statusStop();
            var logEntries = $('.wpstg--modal--process--logs').get(1).innerHTML;
            var html = '<div class="wpstg--modal--process--logs">' + logEntries + '</div>';
            var issueFound = html.includes('wpstg--modal--process--msg--warning') || html.includes('wpstg--modal--process--msg--error') ? 'Issues(s) found! ' : '';
            console.log('errors found: ' + issueFound); // var errorMessage = html.includes('wpstg--modal--process--msg--error') ? 'Errors(s) found! ' : '';
            // var Message = warningMessage + errorMessage;
            // Swal.close();

            Swal.fire({
              icon: 'success',
              title: 'Finished',
              html: 'System restored from backup. <br/><span class="wpstg--modal--process--msg-found">' + issueFound + '</span><button class="wpstg--modal--process--logs--tail" data-txt-bad="">Show Logs</button><br/>' + html
            }); // noinspection JSIgnoredPromiseFromCall

            WPStagingLegacyDatabase.fetchListing();
          } else {
            setTimeout(function () {
              restoreSiteBackup(data);
            }, wpstg.delayReq);
          }
        }, 'json', false, 0, // Don't retry upon failure
        1.25);
      };

      if (!WPStagingLegacyDatabase.modal["import"].html) {
        var $modal = $('#wpstg--modal--backup--import'); // Search & Replace Form

        var $form = $modal.find('.wpstg--modal--backup--import--search-replace--input--container');
        WPStagingLegacyDatabase.modal["import"].searchReplaceForm = $form.html();
        $form.find('.wpstg--modal--backup--import--search-replace--input-group').remove();
        $form.html(WPStagingLegacyDatabase.modal["import"].searchReplaceForm.replace(/{i}/g, 0));
        WPStagingLegacyDatabase.modal["import"].html = $modal.html();
        WPStagingLegacyDatabase.modal["import"].baseDirectory = $modal.attr('data-baseDirectory');
        WPStagingLegacyDatabase.modal["import"].btnTxtNext = $modal.attr('data-nextButtonText');
        WPStagingLegacyDatabase.modal["import"].btnTxtConfirm = $modal.attr('data-confirmButtonText');
        WPStagingLegacyDatabase.modal["import"].btnTxtCancel = $modal.attr('data-cancelButtonText');
        $modal.remove();
      }

      WPStagingLegacyDatabase.modal["import"].data.search = [];
      WPStagingLegacyDatabase.modal["import"].data.replace = [];
      var $btnConfirm = null;
      Swal.mixin({
        customClass: {
          confirmButton: 'wpstg--btn--confirm wpstg-blue-primary wpstg-button wpstg-link-btn',
          cancelButton: 'wpstg--btn--cancel wpstg-blue-primary wpstg-link-btn',
          actions: 'wpstg--modal--actions'
        },
        buttonsStyling: false // progressSteps: ['1', '2']

      }).queue([{
        html: WPStagingLegacyDatabase.modal["import"].html,
        confirmButtonText: WPStagingLegacyDatabase.modal["import"].btnTxtNext,
        showCancelButton: false,
        showConfirmButton: true,
        showLoaderOnConfirm: true,
        width: 650,
        onRender: function onRender() {
          $btnConfirm = $('.wpstg--modal--actions .swal2-confirm');
          $btnConfirm.prop('disabled', true);
          WPStagingLegacyDatabase.modal["import"].containerUpload = $('.wpstg--modal--backup--import--upload');
          WPStagingLegacyDatabase.modal["import"].containerFilesystem = $('.wpstg--modal--backup--import--filesystem');
        },
        preConfirm: function preConfirm() {
          var body = new FormData();
          body.append('accessToken', wpstg.accessToken);
          body.append('nonce', wpstg.nonce);
          body.append('filePath', WPStagingLegacyDatabase.modal["import"].data.file);
          WPStagingLegacyDatabase.modal["import"].data.search.forEach(function (item, index) {
            body.append("search[" + index + "]", item);
          });
          WPStagingLegacyDatabase.modal["import"].data.replace.forEach(function (item, index) {
            body.append("replace[" + index + "]", item);
          });
          return fetch(ajaxurl + "?action=wpstg--backups--import--file-info", {
            method: 'POST',
            body: body
          }).then(WPStagingLegacyDatabase.handleFetchErrors).then(function (res) {
            return res.json();
          }).then(function (html) {
            return Swal.insertQueueStep({
              html: html,
              confirmButtonText: WPStagingLegacyDatabase.modal["import"].btnTxtConfirm,
              cancelButtonText: WPStagingLegacyDatabase.modal["import"].btnTxtCancel,
              showCancelButton: true
            });
          })["catch"](function (e) {
            return WPStagingLegacyDatabase.showAjaxFatalError(e, '', 'Submit an error report.');
          });
        }
      }]).then(function (res) {
        if (!res || !res.value || !res.value[1] || res.value[1] !== true) {
          return;
        }

        WPStagingLegacyDatabase.isCancelled = false;
        var data = WPStagingLegacyDatabase.modal["import"].data;
        data['file'] = WPStagingLegacyDatabase.modal["import"].baseDirectory + data['file'];
        data['reset'] = true;
        WPStagingLegacyDatabase.process({
          execute: function execute() {
            WPStagingLegacyDatabase.messages.reset();
            restoreSiteBackup(data);
          }
        });
      });
    }
  };
})(jQuery);