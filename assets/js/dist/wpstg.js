/**
 * version: 3.3.2
 */ 
 (function () {
  'use strict';

  function _regeneratorRuntime() {
    _regeneratorRuntime = function () {
      return exports;
    };
    var exports = {},
      Op = Object.prototype,
      hasOwn = Op.hasOwnProperty,
      defineProperty = Object.defineProperty || function (obj, key, desc) {
        obj[key] = desc.value;
      },
      $Symbol = "function" == typeof Symbol ? Symbol : {},
      iteratorSymbol = $Symbol.iterator || "@@iterator",
      asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator",
      toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag";
    function define(obj, key, value) {
      return Object.defineProperty(obj, key, {
        value: value,
        enumerable: !0,
        configurable: !0,
        writable: !0
      }), obj[key];
    }
    try {
      define({}, "");
    } catch (err) {
      define = function (obj, key, value) {
        return obj[key] = value;
      };
    }
    function wrap(innerFn, outerFn, self, tryLocsList) {
      var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator,
        generator = Object.create(protoGenerator.prototype),
        context = new Context(tryLocsList || []);
      return defineProperty(generator, "_invoke", {
        value: makeInvokeMethod(innerFn, self, context)
      }), generator;
    }
    function tryCatch(fn, obj, arg) {
      try {
        return {
          type: "normal",
          arg: fn.call(obj, arg)
        };
      } catch (err) {
        return {
          type: "throw",
          arg: err
        };
      }
    }
    exports.wrap = wrap;
    var ContinueSentinel = {};
    function Generator() {}
    function GeneratorFunction() {}
    function GeneratorFunctionPrototype() {}
    var IteratorPrototype = {};
    define(IteratorPrototype, iteratorSymbol, function () {
      return this;
    });
    var getProto = Object.getPrototypeOf,
      NativeIteratorPrototype = getProto && getProto(getProto(values([])));
    NativeIteratorPrototype && NativeIteratorPrototype !== Op && hasOwn.call(NativeIteratorPrototype, iteratorSymbol) && (IteratorPrototype = NativeIteratorPrototype);
    var Gp = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(IteratorPrototype);
    function defineIteratorMethods(prototype) {
      ["next", "throw", "return"].forEach(function (method) {
        define(prototype, method, function (arg) {
          return this._invoke(method, arg);
        });
      });
    }
    function AsyncIterator(generator, PromiseImpl) {
      function invoke(method, arg, resolve, reject) {
        var record = tryCatch(generator[method], generator, arg);
        if ("throw" !== record.type) {
          var result = record.arg,
            value = result.value;
          return value && "object" == typeof value && hasOwn.call(value, "__await") ? PromiseImpl.resolve(value.__await).then(function (value) {
            invoke("next", value, resolve, reject);
          }, function (err) {
            invoke("throw", err, resolve, reject);
          }) : PromiseImpl.resolve(value).then(function (unwrapped) {
            result.value = unwrapped, resolve(result);
          }, function (error) {
            return invoke("throw", error, resolve, reject);
          });
        }
        reject(record.arg);
      }
      var previousPromise;
      defineProperty(this, "_invoke", {
        value: function (method, arg) {
          function callInvokeWithMethodAndArg() {
            return new PromiseImpl(function (resolve, reject) {
              invoke(method, arg, resolve, reject);
            });
          }
          return previousPromise = previousPromise ? previousPromise.then(callInvokeWithMethodAndArg, callInvokeWithMethodAndArg) : callInvokeWithMethodAndArg();
        }
      });
    }
    function makeInvokeMethod(innerFn, self, context) {
      var state = "suspendedStart";
      return function (method, arg) {
        if ("executing" === state) throw new Error("Generator is already running");
        if ("completed" === state) {
          if ("throw" === method) throw arg;
          return doneResult();
        }
        for (context.method = method, context.arg = arg;;) {
          var delegate = context.delegate;
          if (delegate) {
            var delegateResult = maybeInvokeDelegate(delegate, context);
            if (delegateResult) {
              if (delegateResult === ContinueSentinel) continue;
              return delegateResult;
            }
          }
          if ("next" === context.method) context.sent = context._sent = context.arg;else if ("throw" === context.method) {
            if ("suspendedStart" === state) throw state = "completed", context.arg;
            context.dispatchException(context.arg);
          } else "return" === context.method && context.abrupt("return", context.arg);
          state = "executing";
          var record = tryCatch(innerFn, self, context);
          if ("normal" === record.type) {
            if (state = context.done ? "completed" : "suspendedYield", record.arg === ContinueSentinel) continue;
            return {
              value: record.arg,
              done: context.done
            };
          }
          "throw" === record.type && (state = "completed", context.method = "throw", context.arg = record.arg);
        }
      };
    }
    function maybeInvokeDelegate(delegate, context) {
      var methodName = context.method,
        method = delegate.iterator[methodName];
      if (undefined === method) return context.delegate = null, "throw" === methodName && delegate.iterator.return && (context.method = "return", context.arg = undefined, maybeInvokeDelegate(delegate, context), "throw" === context.method) || "return" !== methodName && (context.method = "throw", context.arg = new TypeError("The iterator does not provide a '" + methodName + "' method")), ContinueSentinel;
      var record = tryCatch(method, delegate.iterator, context.arg);
      if ("throw" === record.type) return context.method = "throw", context.arg = record.arg, context.delegate = null, ContinueSentinel;
      var info = record.arg;
      return info ? info.done ? (context[delegate.resultName] = info.value, context.next = delegate.nextLoc, "return" !== context.method && (context.method = "next", context.arg = undefined), context.delegate = null, ContinueSentinel) : info : (context.method = "throw", context.arg = new TypeError("iterator result is not an object"), context.delegate = null, ContinueSentinel);
    }
    function pushTryEntry(locs) {
      var entry = {
        tryLoc: locs[0]
      };
      1 in locs && (entry.catchLoc = locs[1]), 2 in locs && (entry.finallyLoc = locs[2], entry.afterLoc = locs[3]), this.tryEntries.push(entry);
    }
    function resetTryEntry(entry) {
      var record = entry.completion || {};
      record.type = "normal", delete record.arg, entry.completion = record;
    }
    function Context(tryLocsList) {
      this.tryEntries = [{
        tryLoc: "root"
      }], tryLocsList.forEach(pushTryEntry, this), this.reset(!0);
    }
    function values(iterable) {
      if (iterable) {
        var iteratorMethod = iterable[iteratorSymbol];
        if (iteratorMethod) return iteratorMethod.call(iterable);
        if ("function" == typeof iterable.next) return iterable;
        if (!isNaN(iterable.length)) {
          var i = -1,
            next = function next() {
              for (; ++i < iterable.length;) if (hasOwn.call(iterable, i)) return next.value = iterable[i], next.done = !1, next;
              return next.value = undefined, next.done = !0, next;
            };
          return next.next = next;
        }
      }
      return {
        next: doneResult
      };
    }
    function doneResult() {
      return {
        value: undefined,
        done: !0
      };
    }
    return GeneratorFunction.prototype = GeneratorFunctionPrototype, defineProperty(Gp, "constructor", {
      value: GeneratorFunctionPrototype,
      configurable: !0
    }), defineProperty(GeneratorFunctionPrototype, "constructor", {
      value: GeneratorFunction,
      configurable: !0
    }), GeneratorFunction.displayName = define(GeneratorFunctionPrototype, toStringTagSymbol, "GeneratorFunction"), exports.isGeneratorFunction = function (genFun) {
      var ctor = "function" == typeof genFun && genFun.constructor;
      return !!ctor && (ctor === GeneratorFunction || "GeneratorFunction" === (ctor.displayName || ctor.name));
    }, exports.mark = function (genFun) {
      return Object.setPrototypeOf ? Object.setPrototypeOf(genFun, GeneratorFunctionPrototype) : (genFun.__proto__ = GeneratorFunctionPrototype, define(genFun, toStringTagSymbol, "GeneratorFunction")), genFun.prototype = Object.create(Gp), genFun;
    }, exports.awrap = function (arg) {
      return {
        __await: arg
      };
    }, defineIteratorMethods(AsyncIterator.prototype), define(AsyncIterator.prototype, asyncIteratorSymbol, function () {
      return this;
    }), exports.AsyncIterator = AsyncIterator, exports.async = function (innerFn, outerFn, self, tryLocsList, PromiseImpl) {
      void 0 === PromiseImpl && (PromiseImpl = Promise);
      var iter = new AsyncIterator(wrap(innerFn, outerFn, self, tryLocsList), PromiseImpl);
      return exports.isGeneratorFunction(outerFn) ? iter : iter.next().then(function (result) {
        return result.done ? result.value : iter.next();
      });
    }, defineIteratorMethods(Gp), define(Gp, toStringTagSymbol, "Generator"), define(Gp, iteratorSymbol, function () {
      return this;
    }), define(Gp, "toString", function () {
      return "[object Generator]";
    }), exports.keys = function (val) {
      var object = Object(val),
        keys = [];
      for (var key in object) keys.push(key);
      return keys.reverse(), function next() {
        for (; keys.length;) {
          var key = keys.pop();
          if (key in object) return next.value = key, next.done = !1, next;
        }
        return next.done = !0, next;
      };
    }, exports.values = values, Context.prototype = {
      constructor: Context,
      reset: function (skipTempReset) {
        if (this.prev = 0, this.next = 0, this.sent = this._sent = undefined, this.done = !1, this.delegate = null, this.method = "next", this.arg = undefined, this.tryEntries.forEach(resetTryEntry), !skipTempReset) for (var name in this) "t" === name.charAt(0) && hasOwn.call(this, name) && !isNaN(+name.slice(1)) && (this[name] = undefined);
      },
      stop: function () {
        this.done = !0;
        var rootRecord = this.tryEntries[0].completion;
        if ("throw" === rootRecord.type) throw rootRecord.arg;
        return this.rval;
      },
      dispatchException: function (exception) {
        if (this.done) throw exception;
        var context = this;
        function handle(loc, caught) {
          return record.type = "throw", record.arg = exception, context.next = loc, caught && (context.method = "next", context.arg = undefined), !!caught;
        }
        for (var i = this.tryEntries.length - 1; i >= 0; --i) {
          var entry = this.tryEntries[i],
            record = entry.completion;
          if ("root" === entry.tryLoc) return handle("end");
          if (entry.tryLoc <= this.prev) {
            var hasCatch = hasOwn.call(entry, "catchLoc"),
              hasFinally = hasOwn.call(entry, "finallyLoc");
            if (hasCatch && hasFinally) {
              if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0);
              if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc);
            } else if (hasCatch) {
              if (this.prev < entry.catchLoc) return handle(entry.catchLoc, !0);
            } else {
              if (!hasFinally) throw new Error("try statement without catch or finally");
              if (this.prev < entry.finallyLoc) return handle(entry.finallyLoc);
            }
          }
        }
      },
      abrupt: function (type, arg) {
        for (var i = this.tryEntries.length - 1; i >= 0; --i) {
          var entry = this.tryEntries[i];
          if (entry.tryLoc <= this.prev && hasOwn.call(entry, "finallyLoc") && this.prev < entry.finallyLoc) {
            var finallyEntry = entry;
            break;
          }
        }
        finallyEntry && ("break" === type || "continue" === type) && finallyEntry.tryLoc <= arg && arg <= finallyEntry.finallyLoc && (finallyEntry = null);
        var record = finallyEntry ? finallyEntry.completion : {};
        return record.type = type, record.arg = arg, finallyEntry ? (this.method = "next", this.next = finallyEntry.finallyLoc, ContinueSentinel) : this.complete(record);
      },
      complete: function (record, afterLoc) {
        if ("throw" === record.type) throw record.arg;
        return "break" === record.type || "continue" === record.type ? this.next = record.arg : "return" === record.type ? (this.rval = this.arg = record.arg, this.method = "return", this.next = "end") : "normal" === record.type && afterLoc && (this.next = afterLoc), ContinueSentinel;
      },
      finish: function (finallyLoc) {
        for (var i = this.tryEntries.length - 1; i >= 0; --i) {
          var entry = this.tryEntries[i];
          if (entry.finallyLoc === finallyLoc) return this.complete(entry.completion, entry.afterLoc), resetTryEntry(entry), ContinueSentinel;
        }
      },
      catch: function (tryLoc) {
        for (var i = this.tryEntries.length - 1; i >= 0; --i) {
          var entry = this.tryEntries[i];
          if (entry.tryLoc === tryLoc) {
            var record = entry.completion;
            if ("throw" === record.type) {
              var thrown = record.arg;
              resetTryEntry(entry);
            }
            return thrown;
          }
        }
        throw new Error("illegal catch attempt");
      },
      delegateYield: function (iterable, resultName, nextLoc) {
        return this.delegate = {
          iterator: values(iterable),
          resultName: resultName,
          nextLoc: nextLoc
        }, "next" === this.method && (this.arg = undefined), ContinueSentinel;
      }
    }, exports;
  }
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
    }

    // Public methods
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

  /**
   * Detect memory exhaustion and show warning.
   */
  var WpstgDetectMemoryExhaust = /*#__PURE__*/function () {
    function WpstgDetectMemoryExhaust(wpstgObject) {
      if (wpstgObject === void 0) {
        wpstgObject = wpstg;
      }
      this.wpstgObject = wpstgObject;
    }
    var _proto = WpstgDetectMemoryExhaust.prototype;
    _proto.sendRequest = /*#__PURE__*/function () {
      var _sendRequest = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(requestType) {
        var response, data;
        return _regeneratorRuntime().wrap(function _callee$(_context) {
          while (1) switch (_context.prev = _context.next) {
            case 0:
              _context.prev = 0;
              _context.next = 3;
              return fetch(this.wpstgObject.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: new URLSearchParams({
                  action: 'wpstg--detect-memory-exhaust',
                  requestType: requestType,
                  accessToken: this.wpstgObject.accessToken,
                  nonce: this.wpstgObject.nonce
                }),
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded'
                }
              });
            case 3:
              response = _context.sent;
              _context.next = 6;
              return response.json();
            case 6:
              data = _context.sent;
              if (!('undefined' !== typeof data.status && data.status && data.error)) {
                _context.next = 9;
                break;
              }
              return _context.abrupt("return", data);
            case 9:
              console.warn(data.message);
              _context.next = 15;
              break;
            case 12:
              _context.prev = 12;
              _context.t0 = _context["catch"](0);
              console.warn(this.wpstgObject.i18n['somethingWentWrong'], _context.t0);
            case 15:
              return _context.abrupt("return", false);
            case 16:
            case "end":
              return _context.stop();
          }
        }, _callee, this, [[0, 12]]);
      }));
      function sendRequest(_x) {
        return _sendRequest.apply(this, arguments);
      }
      return sendRequest;
    }();
    return WpstgDetectMemoryExhaust;
  }();

  var wpstg$1 = (function ($) {
    var WPStagingCommon = {
      continueErrorHandle: true,
      retry: {
        currentDelay: 0,
        count: 0,
        max: 10,
        retryOnErrors: [401, 403, 404, 429, 502, 503, 504],
        performingRequest: false,
        incrementRetry: function incrementRetry(incrementRatio) {
          if (incrementRatio === void 0) {
            incrementRatio = 1.25;
          }
          WPStagingCommon.retry.performingRequest = true;
          if (WPStagingCommon.retry.currentDelay === 0) {
            // start with a delay of 1sec
            WPStagingCommon.retry.currentDelay = 1000;
            WPStagingCommon.retry.count = 1;
          }
          WPStagingCommon.retry.currentDelay += 500 * WPStagingCommon.retry.count * incrementRatio;
          WPStagingCommon.retry.count++;
        },
        canRetry: function canRetry() {
          return WPStagingCommon.retry.count < WPStagingCommon.retry.max;
        },
        reset: function reset() {
          WPStagingCommon.retry.currentDelay = 0;
          WPStagingCommon.retry.count = 0;
          WPStagingCommon.retry.performingRequest = false;
        }
      },
      memoryExhaustArticleLink: 'https://wp-staging.com/docs/php-fatal-error-allowed-memory-size-of-134217728-bytes-exhausted/',
      cache: {
        elements: [],
        get: function get(selector) {
          // It is already cached!
          if ($.inArray(selector, this.elements) !== -1) {
            return this.elements[selector];
          }

          // Create cache and return
          this.elements[selector] = $(selector);
          return this.elements[selector];
        },
        refresh: function refresh(selector) {
          selector.elements[selector] = $(selector);
        }
      },
      setJobId: function setJobId(jobId) {
        localStorage.setItem('jobIdBeingProcessed', jobId);
      },
      getJobId: function getJobId() {
        return localStorage.getItem('jobIdBeingProcessed');
      },
      checkMemoryExhaustion: function checkMemoryExhaustion(requestType) {
        return _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
          var detectMemoryExhaust, response;
          return _regeneratorRuntime().wrap(function _callee$(_context) {
            while (1) switch (_context.prev = _context.next) {
              case 0:
                detectMemoryExhaust = new WpstgDetectMemoryExhaust();
                _context.next = 3;
                return detectMemoryExhaust.sendRequest(requestType);
              case 3:
                response = _context.sent;
                if (response) {
                  _context.next = 6;
                  break;
                }
                return _context.abrupt("return", false);
              case 6:
                return _context.abrupt("return", response);
              case 10:
              case "end":
                return _context.stop();
            }
          }, _callee);
        }))();
      },
      listenTooltip: function listenTooltip() {
        wpstgHoverIntent(document, '.wpstg--tooltip', function (target, event) {
          target.querySelector('.wpstg--tooltiptext').style.visibility = 'visible';
        }, function (target, event) {
          target.querySelector('.wpstg--tooltiptext').style.visibility = 'hidden';
        });
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
          popup: isContentCentered ? 'wpstg-swal-popup wpstg-centered-modal' : 'wpstg-swal-popup'
        };

        // If an attribute exists in both default and additional attributes,
        // The class(es) of the additional attribute will overwrite the default one.
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
       * @return {string}
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
        // If retry request no need to show Error;
        if (WPStagingCommon.retry.performingRequest) {
          return;
        }
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
       * @param {function} errorCallback
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
            }

            // Default error handler
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

  return wpstg$1;

})();
//# sourceMappingURL=wpstg.js.map
