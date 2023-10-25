(function () {
  'use strict';

  var AnalyticsConsentModal = /*#__PURE__*/function () {
    function AnalyticsConsentModal(wpstgObject) {
      if (wpstgObject === void 0) {
        wpstgObject = wpstg;
      }
      this.wpstgObject = wpstgObject;
      this.isLearnMoreLink = false;
      this.init();
    }
    var _proto = AnalyticsConsentModal.prototype;
    _proto.init = function init() {
      this.consentModalWrapper = document.querySelector('.wpstg-consent-modal-main-wrapper');
      this.consentModalLearnMoreLink = document.querySelector('#wpstg-admin-notice-learn-more');
      this.consentModalPermissionList = document.querySelector('#wpstg-consent-modal-permission-list');
      this.consentModalSkipLink = document.querySelector('#wpstg-skip-activate-notice');
      this.consentModalGiveConsentButton = document.querySelector('#wpstg-consent-modal-btn-success');
      this.addEvents();
    };
    _proto.addEvents = function addEvents() {
      var _this = this;
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          _this.hide(_this.consentModalWrapper);
          _this.hide(_this.consentModalPermissionList);
        }
      });
      this.consentModalSkipLink.addEventListener('click', function () {
        _this.hide(_this.consentModalWrapper);
        _this.hide(_this.consentModalPermissionList);
        location.href = _this.wpstgObject.analyticsConsentDeny;
      });
      this.consentModalLearnMoreLink.addEventListener('click', function () {
        _this.isLearnMoreLink = !_this.isLearnMoreLink;
        if (_this.isLearnMoreLink) {
          _this.consentModalLearnMoreLink.innerHTML = 'Read Less';
          _this.show(_this.consentModalPermissionList);
          return false;
        }
        _this.consentModalLearnMoreLink.innerHTML = 'Read More';
        _this.hide(_this.consentModalPermissionList);
      });
      this.consentModalGiveConsentButton.addEventListener('click', function () {
        _this.hide(_this.consentModalWrapper);
        _this.hide(_this.consentModalPermissionList);
        location.href = _this.wpstgObject.analyticsConsentAllow;
      });
    };
    _proto.show = function show(element) {
      element.style.display = 'block';
    };
    _proto.hide = function hide(element) {
      element.style.display = 'none';
    };
    return AnalyticsConsentModal;
  }();
  new AnalyticsConsentModal();

  return AnalyticsConsentModal;

})();
//# sourceMappingURL=analytics-consent-modal.js.map
