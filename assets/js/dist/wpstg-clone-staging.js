import * as dom from './wpstg-dom-utils.js';
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

    this.pageWrapper = dom.qs(pageWrapperId);
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

    dom.addEvent(this.pageWrapper, 'click', this.enableButtonId, function () {
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

export { WpstgCloneStaging as default };