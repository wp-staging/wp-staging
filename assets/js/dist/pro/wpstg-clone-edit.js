import * as dom from './../wpstg-dom-utils.js'; // Delay time in millisecond after which the request is send to check db connection
// This is to make sure we have not many ajax request even when they were not required i.e. while typing.

export var DELAY_TIME_DB_CHECK = 300;

var WpstgCloneEdit = /*#__PURE__*/function () {
  function WpstgCloneEdit(workflowSelector, dbCheckTriggerClass, wpstgObject, databaseCheckAction) {
    if (workflowSelector === void 0) {
      workflowSelector = '#wpstg-workflow';
    }

    if (dbCheckTriggerClass === void 0) {
      dbCheckTriggerClass = '.wpstg-edit-clone-db-inputs';
    }

    if (wpstgObject === void 0) {
      wpstgObject = wpstg;
    }

    if (databaseCheckAction === void 0) {
      databaseCheckAction = 'wpstg_database_connect';
    }

    this.workflow = dom.qs(workflowSelector);
    this.dbCheckTriggerClass = dbCheckTriggerClass;
    this.wpstgObject = wpstgObject;
    this.databaseCheckAction = databaseCheckAction;
    this.dbCheckTimer = null;
    this.abortDbCheckController = null;
    this.dbCheckCallStatus = false;
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

  var _proto = WpstgCloneEdit.prototype;

  _proto.addEvents = function addEvents() {
    var _this = this;

    // early bail if workflow object not available.
    if (this.workflow === null) {
      return;
    }

    ['paste', 'input'].forEach(function (evt) {
      dom.addEvent(_this.workflow, evt, _this.dbCheckTriggerClass, function () {
        // abort previous database check call if it was running
        if (_this.dbCheckCallStatus === true) {
          _this.abortDbCheckController.abort();

          _this.abortDbCheckController = null;
          _this.dbCheckCallStatus = false;
        } // check for db connection after specific delay but reset the timer if these event occur again


        clearTimeout(_this.dbCheckTimer);
        _this.dbCheckTimer = setTimeout(function () {
          _this.checkDatabase();
        }, DELAY_TIME_DB_CHECK);
      });
    });
  };

  _proto.init = function init() {
    this.addEvents();
  };

  _proto.checkDatabase = function checkDatabase() {
    var _this2 = this;

    var idPrefix = '#wpstg-edit-clone-data-';
    var externalDBUser = dom.qs(idPrefix + 'database-user').value;
    var externalDBPassword = dom.qs(idPrefix + 'database-password').value;
    var externalDBDatabase = dom.qs(idPrefix + 'database-database').value;
    var externalDBHost = dom.qs(idPrefix + 'database-server').value;
    var externalDBPrefix = dom.qs(idPrefix + 'database-prefix').value;

    if (externalDBUser === '' && externalDBPassword === '' && externalDBDatabase === '' && externalDBPrefix === '') {
      dom.qs('#wpstg-save-clone-data').disabled = false;
      return;
    }

    this.abortDbCheckController = new AbortController();
    this.dbCheckCallStatus = true;
    fetch(this.wpstgObject.ajaxUrl, {
      method: 'POST',
      signal: this.abortDbCheckController.signal,
      credentials: 'same-origin',
      body: new URLSearchParams({
        action: this.databaseCheckAction,
        accessToken: this.wpstgObject.accessToken,
        nonce: this.wpstgObject.nonce,
        databaseUser: externalDBUser,
        databasePassword: externalDBPassword,
        databaseServer: externalDBHost,
        databaseDatabase: externalDBDatabase,
        databasePrefix: externalDBPrefix,
        databaseEnsurePrefixTableExist: true
      }),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    }).then(function (response) {
      _this2.dbCheckCallStatus = false;

      if (response.ok) {
        return response.json();
      }

      return Promise.reject(response);
    }).then(function (data) {
      // dismiss previous toasts
      _this2.notyf.dismissAll(); // failed request


      if (false === data) {
        _this2.notyf.error(_this2.wpstgObject.i18n['dbConnectionFailed']);

        dom.qs('#wpstg-save-clone-data').disabled = true;
        return;
      } // failed db connection


      if ('undefined' !== typeof data.errors && data.errors && 'undefined' !== typeof data.success && data.success === 'false') {
        _this2.notyf.error(_this2.wpstgObject.i18n['dbConnectionFailed'] + '! <br/> Error: ' + data.errors);

        dom.qs('#wpstg-save-clone-data').disabled = true;
        return;
      } // prefix warning


      if ('undefined' !== typeof data.errors && data.errors && 'undefined' !== typeof data.success && data.success === 'true') {
        _this2.notyf.open({
          type: 'warning',
          message: 'Warning: ' + data.errors
        });

        dom.qs('#wpstg-save-clone-data').disabled = true;
        return;
      } // db connection successful


      if ('undefined' !== typeof data.success && data.success) {
        _this2.notyf.success(_this2.wpstgObject.i18n['dbConnectionSuccess']);

        dom.qs('#wpstg-save-clone-data').disabled = false;
      }
    })["catch"](function (error) {
      _this2.dbCheckCallStatus = false;
      console.warn(_this2.wpstgObject.i18n['somethingWentWrong'], error);
      dom.qs('#wpstg-save-clone-data').disabled = true;
    });
  };

  return WpstgCloneEdit;
}();

export { WpstgCloneEdit as default };