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
  function _extends() {
    _extends = Object.assign ? Object.assign.bind() : function (target) {
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
    var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"];
    if (it) return (it = it.call(o)).next.bind(it);
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

  /**
   * Polyfills the `Element.prototype.closest` function if not available in the browser.
   *
   * @return {Function} A function that will return the closest element, by selector, to this element.
   */
  function polyfillClosest() {
    if (Element.prototype.closest) {
      if (!Element.prototype.matches) {
        Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
      }
      Element.prototype.closest = function (s) {
        var el = this;
        do {
          if (Element.prototype.matches.call(el, s)) return el;
          el = el.parentElement || el.parentNode;
        } while (el !== null && el.nodeType === 1);
        return null;
      };
    }
    return function (element, selector) {
      return element instanceof Element ? element.closest(selector) : null;
    };
  }
  var closest = polyfillClosest();

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

  var WPStagingCommon = (function ($) {
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
    if (!parent instanceof Element) {
      return;
    }
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
  function fadeOut(element, duration) {
    if (duration === void 0) {
      duration = 300;
    }
    element.style.opacity = 1;
    element.style.transitionProperty = 'opacity';
    element.style.transitionDuration = duration + 'ms';
    setTimeout(function () {
      element.style.opacity = 0;
      window.setTimeout(function () {
        element.style.display = 'none';
        element.style.removeProperty('opacity');
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
  function getPreviousSibling(element, selector) {
    var sibling = element.previousElementSibling;
    while (sibling) {
      if (sibling.matches(selector)) {
        return sibling;
      }
      sibling = sibling.previousElementSibling;
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
   * Dispatches a change on an element that will trigger, depending on the element type,
   * cascading changes on elements dependant on the one that triggered the change and that
   * belong in the same container.
   *
   * @param {Element} element A reference to the Element the change was triggered from.
   *
   * @return {void} The function does not return any value and will have the side-effect of
   *                hiding or showing dependant elements.
   */
  function handleDisplayDependencies(element) {
    if (!element instanceof Element || !element.id) {
      return;
    }
    var containerSelector = '.wpstg-container';
    // Use the default WordPress CSS class to hide and show the objects.
    var hiddenClass = 'hidden';
    var elementType = element.getAttribute('type');
    switch (elementType) {
      case 'checkbox':
        // Go as high as the container that contains this element.
        var container = closest(element, containerSelector);
        if (container === null) {
          return;
        }
        var showIfChecked = container.querySelectorAll("[data-show-if-checked=\"" + element.id + "\"]");
        var showIfUnchecked = container.querySelectorAll("[data-show-if-unchecked=\"" + element.id + "\"]");
        var checked = element.checked;
        if (showIfChecked.length) {
          for (var _iterator = _createForOfIteratorHelperLoose(showIfChecked), _step; !(_step = _iterator()).done;) {
            var el = _step.value;
            if (checked) {
              el.classList.remove(hiddenClass);
            } else {
              el.classList.add(hiddenClass);
            }
          }
        }
        if (showIfUnchecked.length) {
          for (var _iterator2 = _createForOfIteratorHelperLoose(showIfUnchecked), _step2; !(_step2 = _iterator2()).done;) {
            var _el = _step2.value;
            if (checked) {
              _el.classList.add(hiddenClass);
            } else {
              _el.classList.remove(hiddenClass);
            }
          }
        }
        return;
      default:
        // Not a type we handle.
        return;
    }
  }

  /**
   * Toggle target element set in data-wpstg-target of the given element
   *
   * @param {Element} element A reference to the Element the change was triggered from.
   *
   * @return {void} The function does not return any value and will have the side-effect of
   *                hiding or showing dependant elements.
   */
  function handleToggleElement(element) {
    if (!element instanceof Element || !element.getAttribute('data-wpstg-target')) {
      return;
    }
    var containerSelector = '.wpstg_admin';
    // Use the default WordPress CSS class to hide and show the objects.
    var hiddenClass = 'hidden';

    // Go as high as the container that contains this element.
    var container = closest(element, containerSelector);
    if (container === null) {
      return;
    }
    var elements = container.querySelectorAll(element.getAttribute('data-wpstg-target'));
    if (elements.length) {
      for (var _iterator4 = _createForOfIteratorHelperLoose(elements), _step4; !(_step4 = _iterator4()).done;) {
        var el = _step4.value;
        el.classList.toggle(hiddenClass);
      }
    }
  }

  /**
   * Copy text in data-wpstg-copy to element(s) in data-wpstg-target
   *
   * @param {Element} element
   *
   * @return {void}
   */
  function handleCopyPaste(element) {
    if (!element instanceof Element || !element.getAttribute('data-wpstg-target') || !element.getAttribute('data-wpstg-copy')) {
      return;
    }
    var containerSelector = '.wpstg_admin';

    // Go as high as the container that contains this element.
    var container = closest(element, containerSelector);
    if (container === null) {
      return;
    }
    navigator.clipboard.writeText(element.getAttribute('data-wpstg-copy'));
    var elements = container.querySelectorAll(element.getAttribute('data-wpstg-target'));
    if (elements.length) {
      for (var _iterator5 = _createForOfIteratorHelperLoose(elements), _step5; !(_step5 = _iterator5()).done;) {
        var el = _step5.value;
        el.value = element.getAttribute('data-wpstg-copy', '');
      }
    }
  }

  /**
   * Copy text in data-wpstg-source to clipboard
   *
   * @param {Element} element
   *
   * @return {void}
   */
  function handleCopyToClipboard(element) {
    if (!element instanceof Element || !element.getAttribute('data-wpstg-source')) {
      return;
    }
    var containerSelector = '.wpstg_admin';

    // Go as high as the container that contains this element.
    var container = closest(element, containerSelector);
    if (container === null) {
      return;
    }
    var el = container.querySelector(element.getAttribute('data-wpstg-source'));
    if (el) {
      navigator.clipboard.writeText(el.value);
    }
  }

  /**
   * Hides elements in the DOM that match the given selector by setting their display style to 'none'.
   *
   * @param {string} selector - CSS selector for the elements to be hidden.
   * @return {void}
   */
  function hide(selector) {
    var elements = document.querySelectorAll(selector);
    elements.forEach(function (element) {
      element.style.display = 'none';
    });
  }

  /**
   * Displays elements in the DOM that match the given selector by setting their display style to 'block'.
   *
   * @param {string} selector - CSS selector for the elements to be displayed.
   * @return {void}
   */
  function show(selector) {
    var elements = document.querySelectorAll(selector);
    elements.forEach(function (element) {
      element.style.display = 'block';
    });
  }

  /**
   * @param visibility
   * @return {void}
   */
  function loadingBar(visibility) {
    if (visibility === void 0) {
      visibility = 'visible';
    }
    var loader = document.querySelectorAll('.wpstg-loading-bar-container');
    loader.forEach(function (element) {
      if (element) {
        element.style.visibility = visibility;
      }
    });
  }

  /**
   * Enable side bar menu and set url on tab click
   */
  var WpstgSidebarMenu = /*#__PURE__*/function () {
    function WpstgSidebarMenu() {
      this.init();
    }
    var _proto = WpstgSidebarMenu.prototype;
    _proto.init = function init() {
      this.wpstdStagingTab = document.querySelector('#wpstg--tab--toggle--staging');
      this.wpstdBackupTab = document.querySelector('#wpstg--tab--toggle--backup');
      this.wpstdSidebarMenu = document.querySelector('#toplevel_page_wpstg_clone');
      this.addEvents();
    };
    _proto.addEvents = function addEvents() {
      var _this = this;
      if (this.wpstdStagingTab !== null) {
        this.wpstdStagingTab.addEventListener('click', function () {
          _this.setPageUrl('wpstg_clone');
          _this.setSidebarMenu('wpstg_clone');
        });
      }
      if (this.wpstdBackupTab !== null) {
        this.wpstdBackupTab.addEventListener('click', function () {
          _this.setPageUrl('wpstg_backup');
          _this.setSidebarMenu('wpstg_backup');
        });
      }
    };
    _proto.setPageUrl = function setPageUrl(page) {
      window.history.pushState(null, null, window.location.pathname + '?page=' + page);
    };
    _proto.setSidebarMenu = function setSidebarMenu(page) {
      var wpstgSidebarMenuElements = this.wpstdSidebarMenu.querySelector('ul').querySelectorAll('li');
      if (wpstgSidebarMenuElements.length > 0) {
        for (var i = 0; i < wpstgSidebarMenuElements.length; i++) {
          wpstgSidebarMenuElements[i].classList.remove('current');
          if (wpstgSidebarMenuElements[i].querySelector('a') !== null) {
            if (wpstgSidebarMenuElements[i].querySelector('a').getAttribute('href') === 'admin.php?page=' + page) {
              wpstgSidebarMenuElements[i].classList.add('current');
            }
          }
        }
      }
    };
    return WpstgSidebarMenu;
  }();

  /**
   * Handle toggle of contact us modal
   */
  var WpstgContactUs = /*#__PURE__*/function () {
    function WpstgContactUs(modalType, wpstgObject) {
      if (modalType === void 0) {
        modalType = 'contact-us';
      }
      if (wpstgObject === void 0) {
        wpstgObject = wpstg;
      }
      this.wpstgObject = wpstgObject;
      this.characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
      this.currentDate = new Date();
      this.modalType = modalType;
      this.init();
    }
    var _proto = WpstgContactUs.prototype;
    _proto.init = function init() {
      this.contactUsModal = '#wpstg-' + this.modalType + '-modal';
      this.askInTheForumForm = this.contactUsModal + ' #wpstg-' + this.modalType + '-report-issue-form';
      this.contactUsSuccessPopup = this.contactUsModal + ' #wpstg-' + this.modalType + '-success-form';
      this.contactUsLoader = this.contactUsModal + ' #wpstg-' + this.modalType + '-report-issue-loader';
      this.contactUsSupport = this.contactUsModal + ' #wpstg-' + this.modalType + '-support-forum';
      this.contactUsButton = document.querySelector('#wpstg-' + this.modalType + '-button');
      this.contactUsButtonBackupTab = document.querySelector('#wpstg--tab--backup  #wpstg-' + this.modalType + '-button');
      this.reportIssueButton = document.querySelector('#wpstg-contact-us-report-issue');
      this.successForm = document.querySelector(this.contactUsModal + ' #wpstg-' + this.modalType + '-success-form');
      this.contactUsReportIssueBtn = document.querySelector(this.contactUsModal + ' #wpstg-' + this.modalType + '-report-issue-btn');
      this.contactUsCloseButton = document.querySelector(this.contactUsModal + ' #wpstg-modal-close');
      this.contactUsSuccessPopupClose = document.querySelector(this.contactUsModal + ' #wpstg-' + this.modalType + '-success-modal-close');
      this.debugCodeCopyButton = document.querySelector(this.contactUsModal + ' #wpstg-' + this.modalType + '-debug-code-copy');
      this.contactUsResponse = document.querySelector(this.contactUsModal + ' #wpstg-' + this.modalType + '-debug-response');
      this.contactUsDebugCodeField = document.querySelector(this.contactUsModal + ' #wpstg-' + this.modalType + '-debug-code');
      this.isDebugWindowOpened = false;
      this.addEvents();
      this.notyf = new Notyf({
        duration: 6000,
        position: {
          x: 'center',
          y: 'bottom'
        },
        dismissible: true,
        types: [{
          type: 'warning',
          background: 'orange',
          icon: true
        }]
      });
    };
    _proto.addEvents = function addEvents() {
      var _this = this;
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          hide(_this.contactUsModal);
          hide(_this.askInTheForumForm);
          hide(_this.contactUsSuccessPopup);
          hide(_this.contactUsSupport);
          _this.isDebugWindowOpened = false;
        }
      });
      if (this.contactUsButton !== null) {
        this.contactUsButton.addEventListener('click', function () {
          show(_this.contactUsModal);
        });
      }
      if (this.contactUsButtonBackupTab !== null) {
        this.contactUsButtonBackupTab.addEventListener('click', function () {
          show(_this.contactUsModal);
        });
      }
      if (this.reportIssueButton !== null) {
        this.reportIssueButton.addEventListener('click', function () {
          if (_this.isDebugWindowOpened) {
            hide(_this.askInTheForumForm);
          } else {
            show(_this.askInTheForumForm);
          }
          _this.isDebugWindowOpened = !_this.isDebugWindowOpened;
        });
      }
      if (this.contactUsReportIssueBtn !== null) {
        this.contactUsReportIssueBtn.addEventListener('click', function () {
          _this.sendDebugInfo();
        });
      }
      if (this.contactUsSuccessPopupClose !== null) {
        this.contactUsSuccessPopupClose.addEventListener('click', function () {
          hide(_this.contactUsSuccessPopup);
        });
      }
      if (this.contactUsCloseButton !== null) {
        this.contactUsCloseButton.addEventListener('click', function () {
          hide(_this.contactUsModal);
          hide(_this.contactUsSupport);
          hide(_this.askInTheForumForm);
          _this.isDebugWindowOpened = false;
        });
      }
      if (this.debugCodeCopyButton !== null) {
        this.debugCodeCopyButton.addEventListener('click', function () {
          _this.copyDebugCode();
          _this.notyf.success('Debug code copied to clipboard');
        });
      }
    };
    _proto.copyDebugCode = function copyDebugCode() {
      this.contactUsDebugCodeField.select();
      this.contactUsDebugCodeField.setSelectionRange(0, 99999);
      navigator.clipboard.writeText(this.contactUsDebugCodeField.value);
    };
    _proto.generateDebugCode = function generateDebugCode(length) {
      var result = '';
      for (var i = 0; i < length; i++) {
        result += this.characters.charAt(Math.floor(Math.random() * this.characters.length));
      }
      return 'wpstg-' + result + '-' + this.currentDate.getHours() + this.currentDate.getMinutes() + this.currentDate.getSeconds();
    };
    _proto.sendDebugInfo = function sendDebugInfo() {
      var _this2 = this;
      show(this.contactUsLoader);
      this.contactUsReportIssueBtn.disabled = true;
      var debugCode = this.generateDebugCode(8);
      fetch(this.wpstgObject.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new URLSearchParams({
          action: 'wpstg_send_debug_log_report',
          accessToken: this.wpstgObject.accessToken,
          nonce: this.wpstgObject.nonce,
          debugCode: debugCode
        }),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      }).then(function (response) {
        _this2.contactUsReportIssueBtn.disabled = false;
        if (response.ok) {
          return response.json();
        }
      }).then(function (data) {
        if (data.response && data.response.sent === true) {
          _this2.contactUsDebugCodeField.value = debugCode;
          show(_this2.contactUsSuccessPopup);
          window.debugCode = debugCode;
        } else {
          _this2.contactUsDebugCodeField.value = window.debugCode === undefined ? debugCode : window.debugCode;
          show(_this2.contactUsSuccessPopup);
        }
        hide(_this2.contactUsLoader);
      })["catch"](function (error) {
        hide(_this2.contactUsLoader);
        show(_this2.contactUsSupport);
        console.warn(_this2.wpstgObject.i18n['somethingWentWrong'], error);
      });
    };
    return WpstgContactUs;
  }();

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
      new WpstgSidebarMenu();
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
        }

        // There will be message probably in case of error
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
        wpstgObject = WPStagingCommon;
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
      this.fetchChildrenAction = 'wpstg_fetch_dir_children';
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
        console.log('Error: Unable to add directory navigation events.');
        return;
      }
      addEvent(this.directoryListingContainer, 'change', this.dirCheckboxSelector, function (element, event) {
        event.preventDefault();
      });
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
      addEvent(this.directoryListingContainer, 'click', '.wpstg-expand-dirs', function (target, event) {
        event.preventDefault();
        _this.toggleDirectoryNavigation(target);
      });
      addEvent(this.directoryListingContainer, 'change', 'input.wpstg-check-dir', function (target) {
        _this.updateDirectorySelection(target);
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
     */;
    _proto.toggleDirExpand = function toggleDirExpand(element) {
      this.currentParentDiv = element.parentElement;
      this.currentCheckboxElement = getPreviousSibling(element, 'input');
      this.currentLoader = this.currentParentDiv.querySelector('.wpstg-is-dir-loading');
      if (this.currentCheckboxElement.getAttribute('wpstg-data-navigateable', 'false') === 'false') {
        return false;
      }
      if (this.currentCheckboxElement.getAttribute('wpstg-data-scanned', 'false') === 'false') {
        return true;
      }
      return false;
    };
    _proto.sendRequest = function sendRequest(action) {
      var _this2 = this;
      if (this.currentLoader !== null) {
        this.currentLoader.style.display = 'inline-block';
      }
      var changed = this.currentCheckboxElement.getAttribute('wpstg-data-changed');
      var path = this.currentCheckboxElement.getAttribute('wpstg-data-path');
      var prefix = this.currentCheckboxElement.getAttribute('wpstg-data-prefix');
      fetch(this.wpstgObject.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new URLSearchParams({
          action: action,
          accessToken: this.wpstgObject.accessToken,
          nonce: this.wpstgObject.nonce,
          dirPath: path,
          prefix: prefix,
          isChecked: this.currentCheckboxElement.checked,
          forceDefault: changed === 'true'
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
          _this2.currentCheckboxElement.setAttribute('wpstg-data-scanned', true);
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
      var _this3 = this,
        _this$wpstgObject$set;
      this.excludedDirectories = [];
      this.directoryListingContainer.querySelectorAll('.wpstg-dir input:not(:checked)').forEach(function (element) {
        if (!_this3.isParentExcluded(element.value)) {
          _this3.excludedDirectories.push(element.value);
        }
      });
      if (!this.existingExcludes) {
        this.existingExcludes = [];
      }
      this.existingExcludes.forEach(function (exclude) {
        if (!_this3.isParentExcluded(exclude) && !_this3.isScanned(exclude)) {
          _this3.excludedDirectories.push(exclude);
        }
      });
      return this.excludedDirectories.join((_this$wpstgObject$set = this.wpstgObject.settings) == null ? void 0 : _this$wpstgObject$set.directorySeparator);
    }

    /**
     * @param {string} path
     * @return {bool}
     */;
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
      });

      // Check if extra directories text area exists
      // TODO: remove extraCustomDirectories code if no one require extraCustomDirectories...
      var extraDirectoriesTextArea = qs('#wpstg_extraDirectories');
      if (extraDirectoriesTextArea === null || extraDirectoriesTextArea.value === '') {
        var _this$wpstgObject$set2;
        return extraDirectories.join((_this$wpstgObject$set2 = this.wpstgObject.settings) == null ? void 0 : _this$wpstgObject$set2.directorySeparator);
      }
      var extraCustomDirectories = extraDirectoriesTextArea.value.split(/\r?\n/);
      return extraDirectories.concat(extraCustomDirectories).join(this.wpstgObject.settings.directorySeparator);
    };
    _proto.unselectAll = function unselectAll() {
      this.directoryListingContainer.querySelectorAll('.wpstg-dir input').forEach(function (element) {
        element.checked = false;
      });
      this.countSelectedFiles();
    };
    _proto.selectDefault = function selectDefault() {
      // unselect all checkboxes
      this.unselectAll();

      // only select those checkboxes whose class is wpstg-wp-core-dir
      this.directoryListingContainer.querySelectorAll('.wpstg-dir input.wpstg-wp-core-dir').forEach(function (element) {
        element.checked = true;
      });

      // then unselect those checkboxes whose parent has wpstg extra checkbox
      this.directoryListingContainer.querySelectorAll('.wpstg-dir > .wpstg-wp-non-core-dir').forEach(function (element) {
        element.parentElement.querySelectorAll('input.wpstg-wp-core-dir').forEach(function (element) {
          element.checked = false;
        });
      });
      this.isDefaultSelected = true;
      this.countSelectedFiles();
    };
    _proto.parseExcludes = function parseExcludes() {
      this.existingExcludes = this.directoryListingContainer.getAttribute('wpstg-data-existing-excludes', []);
      if (typeof this.existingExcludes === 'undefined' || !this.existingExcludes) {
        this.existingExcludes = [];
        return;
      }
      if (this.existingExcludes.length === 0) {
        this.existingExcludes = [];
        return;
      }
      var existingExcludes = this.existingExcludes.split(',');
      this.existingExcludes = existingExcludes.map(function (exclude) {
        if (exclude.substr(0, 1) === '/') {
          return exclude.slice(1);
        }
        return exclude;
      });
    };
    _proto.isScanned = function isScanned(exclude) {
      var scanned = false;
      this.directoryListingContainer.querySelectorAll('.wpstg-dir>input').forEach(function (element) {
        if (element.value === exclude) {
          scanned = true;
        }
      });
      return scanned;
    };
    _proto.toggleDirectoryNavigation = function toggleDirectoryNavigation(element) {
      var cbElement = getPreviousSibling(element, 'input');
      if (cbElement.getAttribute('wpstg-data-navigateable', 'false') === 'false') {
        return;
      }
      if (cbElement.getAttribute('wpstg-data-scanned', 'false') === 'false') {
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
      element.setAttribute('wpstg-data-changed', 'true');
      if (element.checked) {
        getParents(parent, '.wpstg-dir').forEach(function (parElem) {
          for (var i = 0; i < parElem.children.length; i++) {
            if (parElem.children[i].matches('.wpstg-check-dir')) {
              parElem.children[i].checked = true;
            }
          }
        });
        parent.querySelectorAll('.wpstg-expand-dirs').forEach(function (x) {
          if (x.textContent === 'wp-admin' || x.textContent === 'wp-includes') {
            return;
          }
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
      this.countSelectedFiles();
    };
    _proto.countSelectedFiles = function countSelectedFiles() {
      var themesCount = this.directoryListingContainer.querySelectorAll('[wpstg-data-dir-type="theme"]:checked').length;
      var pluginsCount = this.directoryListingContainer.querySelectorAll('[wpstg-data-dir-type="plugin"]:checked').length;
      var filesCountElement = qs('#wpstg-files-count');
      if (themesCount === 0 && pluginsCount === 0) {
        filesCountElement.classList.add('danger');
        filesCountElement.innerHTML = this.wpstgObject.i18n['noFileSelected'];
      } else {
        filesCountElement.classList.remove('danger');
        filesCountElement.innerHTML = this.wpstgObject.i18n['filesSelected'].replace('{t}', themesCount).replace('{p}', pluginsCount);
      }
    };
    return WpstgDirectoryNavigation;
  }();

  /**
   * Database tables selection
   */
  var WpstgTableSelection = /*#__PURE__*/function () {
    function WpstgTableSelection(databaseTableSectionSelector, workflowSelector, networkCloneSelector, inputSelector, wpstgObject) {
      if (databaseTableSectionSelector === void 0) {
        databaseTableSectionSelector = '#wpstg-scanning-db';
      }
      if (workflowSelector === void 0) {
        workflowSelector = '#wpstg-workflow';
      }
      if (networkCloneSelector === void 0) {
        networkCloneSelector = '#wpstg_network_clone';
      }
      if (inputSelector === void 0) {
        inputSelector = '#wpstg_select_tables_cloning';
      }
      if (wpstgObject === void 0) {
        wpstgObject = wpstg;
      }
      this.databaseTableSection = qs(databaseTableSectionSelector);
      this.workflow = qs(workflowSelector);
      this.networkCloneSelector = networkCloneSelector;
      this.networkCloneCheckbox = qs(networkCloneSelector);
      this.wpstgObject = wpstgObject;
      this.isAllTablesChecked = true;
      this.isMultisite = this.wpstgObject.isMultisite === '1';
      this.isNetworkClone = false;
      this.inputSelector = inputSelector;
      this.input = qs(this.inputSelector);
      this.init();
    }
    var _proto = WpstgTableSelection.prototype;
    _proto.setNetworkClone = function setNetworkClone(isNetworkClone) {
      this.isNetworkClone = isNetworkClone;
    };
    _proto.addEvents = function addEvents() {
      var _this = this;
      if (this.workflow === null) {
        console.log('Error: database table section is null. Cannot register events');
        return;
      }
      addEvent(this.workflow, 'change', this.networkCloneSelector, function () {
        _this.selectDefaultTables();
      });
      addEvent(this.workflow, 'change', this.inputSelector, function () {
        _this.countSelectedTables();
      });
      addEvent(this.workflow, 'click', '.wpstg-button-select', function (target, event) {
        event.preventDefault();
        _this.selectDefaultTables();
      });
      addEvent(this.workflow, 'click', '.wpstg-button-unselect', function (target, event) {
        event.preventDefault();
        _this.toggleTableSelection();
      });
      addEvent(this.workflow, 'click', '.wpstg-button-unselect-wpstg', function (target, event) {
        event.preventDefault();
        _this.unselectWPSTGTables();
      });
    };
    _proto.init = function init() {
      this.addEvents();
    };
    _proto.getRegexPattern = function getRegexPattern() {
      var pattern = '^' + this.wpstgObject.tblprefix;
      var isNetwork = false;
      if (this.networkCloneCheckbox !== undefined && this.networkCloneCheckbox !== null) {
        isNetwork = this.networkCloneCheckbox.checked;
      }

      // Force network clone true if set explicitly
      if (this.isNetworkClone) {
        isNetwork = true;
      }
      if (this.isMultisite && !isNetwork) {
        pattern += '([^0-9])_*';
      }
      return pattern;
    };
    _proto.selectDefaultTables = function selectDefaultTables() {
      var options = this.databaseTableSection.querySelectorAll('#wpstg_select_tables_cloning .wpstg-db-table');
      var regexPattern = this.getRegexPattern();
      options.forEach(function (option) {
        var name = option.getAttribute('name', '');
        if (name.match(regexPattern)) {
          option.selected = true;
        } else {
          option.selected = false;
        }
      });
      this.countSelectedTables();
    };
    _proto.toggleTableSelection = function toggleTableSelection() {
      if (false === this.isAllTablesChecked) {
        this.databaseTableSection.querySelectorAll('#wpstg_select_tables_cloning .wpstg-db-table').forEach(function (option) {
          option.selected = true;
        });
        this.databaseTableSection.querySelector('.wpstg-button-unselect').innerHTML = 'Unselect All';
        // cache.get('.wpstg-db-table-checkboxes').prop('checked', true);
        this.isAllTablesChecked = true;
      } else {
        this.databaseTableSection.querySelectorAll('#wpstg_select_tables_cloning .wpstg-db-table').forEach(function (option) {
          option.selected = false;
        });
        this.databaseTableSection.querySelector('.wpstg-button-unselect').innerHTML = 'Select All';
        // cache.get('.wpstg-db-table-checkboxes').prop('checked', false);
        this.isAllTablesChecked = false;
      }
      this.countSelectedTables();
    };
    _proto.unselectWPSTGTables = function unselectWPSTGTables() {
      var _this2 = this;
      var options = this.databaseTableSection.querySelectorAll('#wpstg_select_tables_cloning .wpstg-db-table');
      var regexPattern = 'wpstg';
      options.forEach(function (option) {
        var name = option.getAttribute('name', '');
        if (name.match(regexPattern) && !name.match(_this2.wpstgObject.tblprefix + 'wpstg_queue')) {
          option.selected = false;
        }
      });
      this.countSelectedTables();
    };
    _proto.getSelectedTablesWithoutPrefix = function getSelectedTablesWithoutPrefix() {
      var selectedTablesWithoutPrefix = [];
      var options = this.databaseTableSection.querySelectorAll('#wpstg_select_tables_cloning option:checked');
      var regexPattern = this.getRegexPattern();
      options.forEach(function (option) {
        var name = option.getAttribute('name', '');
        if (!name.match(regexPattern)) {
          selectedTablesWithoutPrefix.push(option.value);
        }
      });
      return selectedTablesWithoutPrefix.join(this.wpstgObject.settings.directorySeparator);
    };
    _proto.getIncludedTables = function getIncludedTables() {
      var includedTables = [];
      var options = this.databaseTableSection.querySelectorAll('#wpstg_select_tables_cloning option:checked');
      var regexPattern = this.getRegexPattern();
      options.forEach(function (option) {
        var name = option.getAttribute('name', '');
        if (name.match(regexPattern)) {
          includedTables.push(option.value);
        }
      });
      return includedTables.join(this.wpstgObject.settings.directorySeparator);
    };
    _proto.getExcludedTables = function getExcludedTables() {
      var excludedTables = [];
      var options = this.databaseTableSection.querySelectorAll('#wpstg_select_tables_cloning option:not(:checked)');
      var regexPattern = this.getRegexPattern();
      options.forEach(function (option) {
        var name = option.getAttribute('name', '');
        if (name.match(regexPattern)) {
          excludedTables.push(option.value);
        }
      });
      return excludedTables.join(this.wpstgObject.settings.directorySeparator);
    };
    _proto.countSelectedTables = function countSelectedTables() {
      var tablesCount = this.input.querySelectorAll('option:checked').length;
      var tablesCountElement = qs('#wpstg-tables-count');
      if (tablesCount === 0) {
        tablesCountElement.classList.add('danger');
        tablesCountElement.innerHTML = this.wpstgObject.i18n['noTableSelected'];
      } else {
        tablesCountElement.classList.remove('danger');
        tablesCountElement.innerHTML = this.wpstgObject.i18n['tablesSelected'].replace('{d}', tablesCount);
      }
    };
    return WpstgTableSelection;
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
     */;
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
     */;
    _proto.cleanStringForGlob = function cleanStringForGlob(value) {
      // will replace character like * ^ / \ ! ? [ from the string
      return value.replace(/[*^//!\[?]/g, '');
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
      this.tableSelector = null;
      this.isNetworkClone = false;
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
    };
    _proto.init = function init() {
      this.addEvents();
    };
    _proto.setNetworkClone = function setNetworkClone(isNetworkClone) {
      this.isNetworkClone = isNetworkClone;
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
     */;
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
      this.error = null;
      // send ajax request and fetch preserved exclude settings
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
              popup: 'wpstg-swal-popup wpstg-centered-modal'
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
        modal.querySelector('.wpstg--swal2-popup').style.width = '540px';
        modal.querySelector('.wpstg--swal2-content').innerHTML = data.html;
        _this2.directoryNavigator = new WpstgDirectoryNavigation('#wpstg-directories-listing', wpstg, null);
        _this2.directoryNavigator.countSelectedFiles();
        _this2.excludeFilters = new WpstgExcludeFilters();
        _this2.tableSelector = new WpstgTableSelection('#wpstg-reset-excluded-tables', '.' + _this2.resetModalContainerClass);
        _this2.tableSelector.setNetworkClone(_this2.isNetworkClone);
        _this2.tableSelector.countSelectedTables();
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
    _proto.getTableSelector = function getTableSelector() {
      return this.tableSelector;
    };
    _proto.getAjaxLoader = function getAjaxLoader() {
      return '<div class="wpstg-swal2-ajax-loader"><img src="' + this.wpstgObject.wpstgIcon + '" /></div>';
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
     */;
    _proto.init = function init() {
      this.addEvents();
    }

    /**
     * Expand/Collapse checkbox content on change
     * @return {void}
     */;
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
      var tabHeader = qs('.wpstg--tab--header');
      // Early bail if tab header is not available
      if (tabHeader === null) {
        return;
      }
      var workflowContainer = qs('#wpstg-workflow');
      if (workflowContainer !== null) {
        addEvent(workflowContainer, 'click', '.wpstg-navigate-button', function (element) {
          var $this = element;
          var target = $this.getAttribute('data-target');
          if (target === '') {
            return;
          }
          if ('#wpstg--tab--backup' === target) {
            // trigger click for .wpsg--tab--header a[data-target="#wpstg--tab--backup"]
            qs('.wpstg--tab--header a[data-target="#wpstg--tab--backup"]').click();
          }
        });
      }
      addEvent(qs('.wpstg--tab--header'), 'click', '.wpstg-button', function (element) {
        var $this = element;
        var target = $this.getAttribute('data-target');
        if (target === '') {
          return;
        }
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

  /**
   * Copyright (c) 2014-present, Facebook, Inc.
   *
   * This source code is licensed under the MIT license found in the
   * LICENSE file in the root directory of this source tree.
   */

  var runtime = (function (exports) {

    var Op = Object.prototype;
    var hasOwn = Op.hasOwnProperty;
    var defineProperty = Object.defineProperty || function (obj, key, desc) { obj[key] = desc.value; };
    var undefined$1; // More compressible than void 0.
    var $Symbol = typeof Symbol === "function" ? Symbol : {};
    var iteratorSymbol = $Symbol.iterator || "@@iterator";
    var asyncIteratorSymbol = $Symbol.asyncIterator || "@@asyncIterator";
    var toStringTagSymbol = $Symbol.toStringTag || "@@toStringTag";

    function define(obj, key, value) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
      return obj[key];
    }
    try {
      // IE 8 has a broken Object.defineProperty that only works on DOM objects.
      define({}, "");
    } catch (err) {
      define = function(obj, key, value) {
        return obj[key] = value;
      };
    }

    function wrap(innerFn, outerFn, self, tryLocsList) {
      // If outerFn provided and outerFn.prototype is a Generator, then outerFn.prototype instanceof Generator.
      var protoGenerator = outerFn && outerFn.prototype instanceof Generator ? outerFn : Generator;
      var generator = Object.create(protoGenerator.prototype);
      var context = new Context(tryLocsList || []);

      // The ._invoke method unifies the implementations of the .next,
      // .throw, and .return methods.
      defineProperty(generator, "_invoke", { value: makeInvokeMethod(innerFn, self, context) });

      return generator;
    }
    exports.wrap = wrap;

    // Try/catch helper to minimize deoptimizations. Returns a completion
    // record like context.tryEntries[i].completion. This interface could
    // have been (and was previously) designed to take a closure to be
    // invoked without arguments, but in all the cases we care about we
    // already have an existing method we want to call, so there's no need
    // to create a new function object. We can even get away with assuming
    // the method takes exactly one argument, since that happens to be true
    // in every case, so we don't have to touch the arguments object. The
    // only additional allocation required is the completion record, which
    // has a stable shape and so hopefully should be cheap to allocate.
    function tryCatch(fn, obj, arg) {
      try {
        return { type: "normal", arg: fn.call(obj, arg) };
      } catch (err) {
        return { type: "throw", arg: err };
      }
    }

    var GenStateSuspendedStart = "suspendedStart";
    var GenStateSuspendedYield = "suspendedYield";
    var GenStateExecuting = "executing";
    var GenStateCompleted = "completed";

    // Returning this object from the innerFn has the same effect as
    // breaking out of the dispatch switch statement.
    var ContinueSentinel = {};

    // Dummy constructor functions that we use as the .constructor and
    // .constructor.prototype properties for functions that return Generator
    // objects. For full spec compliance, you may wish to configure your
    // minifier not to mangle the names of these two functions.
    function Generator() {}
    function GeneratorFunction() {}
    function GeneratorFunctionPrototype() {}

    // This is a polyfill for %IteratorPrototype% for environments that
    // don't natively support it.
    var IteratorPrototype = {};
    define(IteratorPrototype, iteratorSymbol, function () {
      return this;
    });

    var getProto = Object.getPrototypeOf;
    var NativeIteratorPrototype = getProto && getProto(getProto(values([])));
    if (NativeIteratorPrototype &&
        NativeIteratorPrototype !== Op &&
        hasOwn.call(NativeIteratorPrototype, iteratorSymbol)) {
      // This environment has a native %IteratorPrototype%; use it instead
      // of the polyfill.
      IteratorPrototype = NativeIteratorPrototype;
    }

    var Gp = GeneratorFunctionPrototype.prototype =
      Generator.prototype = Object.create(IteratorPrototype);
    GeneratorFunction.prototype = GeneratorFunctionPrototype;
    defineProperty(Gp, "constructor", { value: GeneratorFunctionPrototype, configurable: true });
    defineProperty(
      GeneratorFunctionPrototype,
      "constructor",
      { value: GeneratorFunction, configurable: true }
    );
    GeneratorFunction.displayName = define(
      GeneratorFunctionPrototype,
      toStringTagSymbol,
      "GeneratorFunction"
    );

    // Helper for defining the .next, .throw, and .return methods of the
    // Iterator interface in terms of a single ._invoke method.
    function defineIteratorMethods(prototype) {
      ["next", "throw", "return"].forEach(function(method) {
        define(prototype, method, function(arg) {
          return this._invoke(method, arg);
        });
      });
    }

    exports.isGeneratorFunction = function(genFun) {
      var ctor = typeof genFun === "function" && genFun.constructor;
      return ctor
        ? ctor === GeneratorFunction ||
          // For the native GeneratorFunction constructor, the best we can
          // do is to check its .name property.
          (ctor.displayName || ctor.name) === "GeneratorFunction"
        : false;
    };

    exports.mark = function(genFun) {
      if (Object.setPrototypeOf) {
        Object.setPrototypeOf(genFun, GeneratorFunctionPrototype);
      } else {
        genFun.__proto__ = GeneratorFunctionPrototype;
        define(genFun, toStringTagSymbol, "GeneratorFunction");
      }
      genFun.prototype = Object.create(Gp);
      return genFun;
    };

    // Within the body of any async function, `await x` is transformed to
    // `yield regeneratorRuntime.awrap(x)`, so that the runtime can test
    // `hasOwn.call(value, "__await")` to determine if the yielded value is
    // meant to be awaited.
    exports.awrap = function(arg) {
      return { __await: arg };
    };

    function AsyncIterator(generator, PromiseImpl) {
      function invoke(method, arg, resolve, reject) {
        var record = tryCatch(generator[method], generator, arg);
        if (record.type === "throw") {
          reject(record.arg);
        } else {
          var result = record.arg;
          var value = result.value;
          if (value &&
              typeof value === "object" &&
              hasOwn.call(value, "__await")) {
            return PromiseImpl.resolve(value.__await).then(function(value) {
              invoke("next", value, resolve, reject);
            }, function(err) {
              invoke("throw", err, resolve, reject);
            });
          }

          return PromiseImpl.resolve(value).then(function(unwrapped) {
            // When a yielded Promise is resolved, its final value becomes
            // the .value of the Promise<{value,done}> result for the
            // current iteration.
            result.value = unwrapped;
            resolve(result);
          }, function(error) {
            // If a rejected Promise was yielded, throw the rejection back
            // into the async generator function so it can be handled there.
            return invoke("throw", error, resolve, reject);
          });
        }
      }

      var previousPromise;

      function enqueue(method, arg) {
        function callInvokeWithMethodAndArg() {
          return new PromiseImpl(function(resolve, reject) {
            invoke(method, arg, resolve, reject);
          });
        }

        return previousPromise =
          // If enqueue has been called before, then we want to wait until
          // all previous Promises have been resolved before calling invoke,
          // so that results are always delivered in the correct order. If
          // enqueue has not been called before, then it is important to
          // call invoke immediately, without waiting on a callback to fire,
          // so that the async generator function has the opportunity to do
          // any necessary setup in a predictable way. This predictability
          // is why the Promise constructor synchronously invokes its
          // executor callback, and why async functions synchronously
          // execute code before the first await. Since we implement simple
          // async functions in terms of async generators, it is especially
          // important to get this right, even though it requires care.
          previousPromise ? previousPromise.then(
            callInvokeWithMethodAndArg,
            // Avoid propagating failures to Promises returned by later
            // invocations of the iterator.
            callInvokeWithMethodAndArg
          ) : callInvokeWithMethodAndArg();
      }

      // Define the unified helper method that is used to implement .next,
      // .throw, and .return (see defineIteratorMethods).
      defineProperty(this, "_invoke", { value: enqueue });
    }

    defineIteratorMethods(AsyncIterator.prototype);
    define(AsyncIterator.prototype, asyncIteratorSymbol, function () {
      return this;
    });
    exports.AsyncIterator = AsyncIterator;

    // Note that simple async functions are implemented on top of
    // AsyncIterator objects; they just return a Promise for the value of
    // the final result produced by the iterator.
    exports.async = function(innerFn, outerFn, self, tryLocsList, PromiseImpl) {
      if (PromiseImpl === void 0) PromiseImpl = Promise;

      var iter = new AsyncIterator(
        wrap(innerFn, outerFn, self, tryLocsList),
        PromiseImpl
      );

      return exports.isGeneratorFunction(outerFn)
        ? iter // If outerFn is a generator, return the full iterator.
        : iter.next().then(function(result) {
            return result.done ? result.value : iter.next();
          });
    };

    function makeInvokeMethod(innerFn, self, context) {
      var state = GenStateSuspendedStart;

      return function invoke(method, arg) {
        if (state === GenStateExecuting) {
          throw new Error("Generator is already running");
        }

        if (state === GenStateCompleted) {
          if (method === "throw") {
            throw arg;
          }

          // Be forgiving, per 25.3.3.3.3 of the spec:
          // https://people.mozilla.org/~jorendorff/es6-draft.html#sec-generatorresume
          return doneResult();
        }

        context.method = method;
        context.arg = arg;

        while (true) {
          var delegate = context.delegate;
          if (delegate) {
            var delegateResult = maybeInvokeDelegate(delegate, context);
            if (delegateResult) {
              if (delegateResult === ContinueSentinel) continue;
              return delegateResult;
            }
          }

          if (context.method === "next") {
            // Setting context._sent for legacy support of Babel's
            // function.sent implementation.
            context.sent = context._sent = context.arg;

          } else if (context.method === "throw") {
            if (state === GenStateSuspendedStart) {
              state = GenStateCompleted;
              throw context.arg;
            }

            context.dispatchException(context.arg);

          } else if (context.method === "return") {
            context.abrupt("return", context.arg);
          }

          state = GenStateExecuting;

          var record = tryCatch(innerFn, self, context);
          if (record.type === "normal") {
            // If an exception is thrown from innerFn, we leave state ===
            // GenStateExecuting and loop back for another invocation.
            state = context.done
              ? GenStateCompleted
              : GenStateSuspendedYield;

            if (record.arg === ContinueSentinel) {
              continue;
            }

            return {
              value: record.arg,
              done: context.done
            };

          } else if (record.type === "throw") {
            state = GenStateCompleted;
            // Dispatch the exception by looping back around to the
            // context.dispatchException(context.arg) call above.
            context.method = "throw";
            context.arg = record.arg;
          }
        }
      };
    }

    // Call delegate.iterator[context.method](context.arg) and handle the
    // result, either by returning a { value, done } result from the
    // delegate iterator, or by modifying context.method and context.arg,
    // setting context.delegate to null, and returning the ContinueSentinel.
    function maybeInvokeDelegate(delegate, context) {
      var methodName = context.method;
      var method = delegate.iterator[methodName];
      if (method === undefined$1) {
        // A .throw or .return when the delegate iterator has no .throw
        // method, or a missing .next mehtod, always terminate the
        // yield* loop.
        context.delegate = null;

        // Note: ["return"] must be used for ES3 parsing compatibility.
        if (methodName === "throw" && delegate.iterator["return"]) {
          // If the delegate iterator has a return method, give it a
          // chance to clean up.
          context.method = "return";
          context.arg = undefined$1;
          maybeInvokeDelegate(delegate, context);

          if (context.method === "throw") {
            // If maybeInvokeDelegate(context) changed context.method from
            // "return" to "throw", let that override the TypeError below.
            return ContinueSentinel;
          }
        }
        if (methodName !== "return") {
          context.method = "throw";
          context.arg = new TypeError(
            "The iterator does not provide a '" + methodName + "' method");
        }

        return ContinueSentinel;
      }

      var record = tryCatch(method, delegate.iterator, context.arg);

      if (record.type === "throw") {
        context.method = "throw";
        context.arg = record.arg;
        context.delegate = null;
        return ContinueSentinel;
      }

      var info = record.arg;

      if (! info) {
        context.method = "throw";
        context.arg = new TypeError("iterator result is not an object");
        context.delegate = null;
        return ContinueSentinel;
      }

      if (info.done) {
        // Assign the result of the finished delegate to the temporary
        // variable specified by delegate.resultName (see delegateYield).
        context[delegate.resultName] = info.value;

        // Resume execution at the desired location (see delegateYield).
        context.next = delegate.nextLoc;

        // If context.method was "throw" but the delegate handled the
        // exception, let the outer generator proceed normally. If
        // context.method was "next", forget context.arg since it has been
        // "consumed" by the delegate iterator. If context.method was
        // "return", allow the original .return call to continue in the
        // outer generator.
        if (context.method !== "return") {
          context.method = "next";
          context.arg = undefined$1;
        }

      } else {
        // Re-yield the result returned by the delegate method.
        return info;
      }

      // The delegate iterator is finished, so forget it and continue with
      // the outer generator.
      context.delegate = null;
      return ContinueSentinel;
    }

    // Define Generator.prototype.{next,throw,return} in terms of the
    // unified ._invoke helper method.
    defineIteratorMethods(Gp);

    define(Gp, toStringTagSymbol, "Generator");

    // A Generator should always return itself as the iterator object when the
    // @@iterator function is called on it. Some browsers' implementations of the
    // iterator prototype chain incorrectly implement this, causing the Generator
    // object to not be returned from this call. This ensures that doesn't happen.
    // See https://github.com/facebook/regenerator/issues/274 for more details.
    define(Gp, iteratorSymbol, function() {
      return this;
    });

    define(Gp, "toString", function() {
      return "[object Generator]";
    });

    function pushTryEntry(locs) {
      var entry = { tryLoc: locs[0] };

      if (1 in locs) {
        entry.catchLoc = locs[1];
      }

      if (2 in locs) {
        entry.finallyLoc = locs[2];
        entry.afterLoc = locs[3];
      }

      this.tryEntries.push(entry);
    }

    function resetTryEntry(entry) {
      var record = entry.completion || {};
      record.type = "normal";
      delete record.arg;
      entry.completion = record;
    }

    function Context(tryLocsList) {
      // The root entry object (effectively a try statement without a catch
      // or a finally block) gives us a place to store values thrown from
      // locations where there is no enclosing try statement.
      this.tryEntries = [{ tryLoc: "root" }];
      tryLocsList.forEach(pushTryEntry, this);
      this.reset(true);
    }

    exports.keys = function(val) {
      var object = Object(val);
      var keys = [];
      for (var key in object) {
        keys.push(key);
      }
      keys.reverse();

      // Rather than returning an object with a next method, we keep
      // things simple and return the next function itself.
      return function next() {
        while (keys.length) {
          var key = keys.pop();
          if (key in object) {
            next.value = key;
            next.done = false;
            return next;
          }
        }

        // To avoid creating an additional object, we just hang the .value
        // and .done properties off the next function object itself. This
        // also ensures that the minifier will not anonymize the function.
        next.done = true;
        return next;
      };
    };

    function values(iterable) {
      if (iterable) {
        var iteratorMethod = iterable[iteratorSymbol];
        if (iteratorMethod) {
          return iteratorMethod.call(iterable);
        }

        if (typeof iterable.next === "function") {
          return iterable;
        }

        if (!isNaN(iterable.length)) {
          var i = -1, next = function next() {
            while (++i < iterable.length) {
              if (hasOwn.call(iterable, i)) {
                next.value = iterable[i];
                next.done = false;
                return next;
              }
            }

            next.value = undefined$1;
            next.done = true;

            return next;
          };

          return next.next = next;
        }
      }

      // Return an iterator with no values.
      return { next: doneResult };
    }
    exports.values = values;

    function doneResult() {
      return { value: undefined$1, done: true };
    }

    Context.prototype = {
      constructor: Context,

      reset: function(skipTempReset) {
        this.prev = 0;
        this.next = 0;
        // Resetting context._sent for legacy support of Babel's
        // function.sent implementation.
        this.sent = this._sent = undefined$1;
        this.done = false;
        this.delegate = null;

        this.method = "next";
        this.arg = undefined$1;

        this.tryEntries.forEach(resetTryEntry);

        if (!skipTempReset) {
          for (var name in this) {
            // Not sure about the optimal order of these conditions:
            if (name.charAt(0) === "t" &&
                hasOwn.call(this, name) &&
                !isNaN(+name.slice(1))) {
              this[name] = undefined$1;
            }
          }
        }
      },

      stop: function() {
        this.done = true;

        var rootEntry = this.tryEntries[0];
        var rootRecord = rootEntry.completion;
        if (rootRecord.type === "throw") {
          throw rootRecord.arg;
        }

        return this.rval;
      },

      dispatchException: function(exception) {
        if (this.done) {
          throw exception;
        }

        var context = this;
        function handle(loc, caught) {
          record.type = "throw";
          record.arg = exception;
          context.next = loc;

          if (caught) {
            // If the dispatched exception was caught by a catch block,
            // then let that catch block handle the exception normally.
            context.method = "next";
            context.arg = undefined$1;
          }

          return !! caught;
        }

        for (var i = this.tryEntries.length - 1; i >= 0; --i) {
          var entry = this.tryEntries[i];
          var record = entry.completion;

          if (entry.tryLoc === "root") {
            // Exception thrown outside of any try block that could handle
            // it, so set the completion value of the entire function to
            // throw the exception.
            return handle("end");
          }

          if (entry.tryLoc <= this.prev) {
            var hasCatch = hasOwn.call(entry, "catchLoc");
            var hasFinally = hasOwn.call(entry, "finallyLoc");

            if (hasCatch && hasFinally) {
              if (this.prev < entry.catchLoc) {
                return handle(entry.catchLoc, true);
              } else if (this.prev < entry.finallyLoc) {
                return handle(entry.finallyLoc);
              }

            } else if (hasCatch) {
              if (this.prev < entry.catchLoc) {
                return handle(entry.catchLoc, true);
              }

            } else if (hasFinally) {
              if (this.prev < entry.finallyLoc) {
                return handle(entry.finallyLoc);
              }

            } else {
              throw new Error("try statement without catch or finally");
            }
          }
        }
      },

      abrupt: function(type, arg) {
        for (var i = this.tryEntries.length - 1; i >= 0; --i) {
          var entry = this.tryEntries[i];
          if (entry.tryLoc <= this.prev &&
              hasOwn.call(entry, "finallyLoc") &&
              this.prev < entry.finallyLoc) {
            var finallyEntry = entry;
            break;
          }
        }

        if (finallyEntry &&
            (type === "break" ||
             type === "continue") &&
            finallyEntry.tryLoc <= arg &&
            arg <= finallyEntry.finallyLoc) {
          // Ignore the finally entry if control is not jumping to a
          // location outside the try/catch block.
          finallyEntry = null;
        }

        var record = finallyEntry ? finallyEntry.completion : {};
        record.type = type;
        record.arg = arg;

        if (finallyEntry) {
          this.method = "next";
          this.next = finallyEntry.finallyLoc;
          return ContinueSentinel;
        }

        return this.complete(record);
      },

      complete: function(record, afterLoc) {
        if (record.type === "throw") {
          throw record.arg;
        }

        if (record.type === "break" ||
            record.type === "continue") {
          this.next = record.arg;
        } else if (record.type === "return") {
          this.rval = this.arg = record.arg;
          this.method = "return";
          this.next = "end";
        } else if (record.type === "normal" && afterLoc) {
          this.next = afterLoc;
        }

        return ContinueSentinel;
      },

      finish: function(finallyLoc) {
        for (var i = this.tryEntries.length - 1; i >= 0; --i) {
          var entry = this.tryEntries[i];
          if (entry.finallyLoc === finallyLoc) {
            this.complete(entry.completion, entry.afterLoc);
            resetTryEntry(entry);
            return ContinueSentinel;
          }
        }
      },

      "catch": function(tryLoc) {
        for (var i = this.tryEntries.length - 1; i >= 0; --i) {
          var entry = this.tryEntries[i];
          if (entry.tryLoc === tryLoc) {
            var record = entry.completion;
            if (record.type === "throw") {
              var thrown = record.arg;
              resetTryEntry(entry);
            }
            return thrown;
          }
        }

        // The context.catch method must only be called with a location
        // argument that corresponds to a known catch block.
        throw new Error("illegal catch attempt");
      },

      delegateYield: function(iterable, resultName, nextLoc) {
        this.delegate = {
          iterator: values(iterable),
          resultName: resultName,
          nextLoc: nextLoc
        };

        if (this.method === "next") {
          // Deliberately forget the last sent value so that we don't
          // accidentally pass it on to the delegate.
          this.arg = undefined$1;
        }

        return ContinueSentinel;
      }
    };

    // Regardless of whether this script is executing as a CommonJS module
    // or not, return the runtime object so that we can declare the variable
    // regeneratorRuntime in the outer scope, which allows this module to be
    // injected easily by `bin/regenerator --include-runtime script.js`.
    return exports;

  }(
    // If this script is executing as a CommonJS module, use module.exports
    // as the regeneratorRuntime namespace. Otherwise create a new empty
    // object. Either way, the resulting object will be used to initialize
    // the regeneratorRuntime variable at the top of this file.
    typeof module === "object" ? module.exports : {}
  ));

  try {
    regeneratorRuntime = runtime;
  } catch (accidentalStrictMode) {
    // This module should not be running in strict mode, so the above
    // assignment should always work unless something is misconfigured. Just
    // in case runtime.js accidentally runs in strict mode, in modern engines
    // we can explicitly access globalThis. In older engines we can escape
    // strict mode using a global Function call. This could conceivably fail
    // if a Content Security Policy forbids using Function, but in that case
    // the proper solution is to fix the accidental strict mode problem. If
    // you've misconfigured your bundler to force strict mode and applied a
    // CSP to forbid Function, and you're not willing to fix either of those
    // problems, please detail your unique predicament in a GitHub issue.
    if (typeof globalThis === "object") {
      globalThis.regeneratorRuntime = runtime;
    } else {
      Function("r", "regeneratorRuntime = r")(runtime);
    }
  }

  var WpstgReAuth = /*#__PURE__*/function () {
    function WpstgReAuth() {
      this.wrap = '';
      this.tempHidden = '';
      this.tempHiddenTimeout = '';
    }

    /**
     * @return {void}
     */
    var _proto = WpstgReAuth.prototype;
    _proto.shows = function shows() {
      this.wrap = document.getElementById('wpstg-auth-check-wrap');
      this.parentElement = document.getElementById('wpstg-auth-check');
      this.formElement = document.getElementById('wpstg-auth-check-form');
      this.wrapElement = document.getElementById('wpstg-auth-check-wrap');
      this.noFrameElement = this.wrapElement.querySelector('.wpstg-auth-fallback-expired');
      this.iframeElement = '';
      this.isIframeLoaded = false;
      if (this.formElement) {
        this.addUnloadEvent();
        this.iframeElement = this.createIframeElement();
        this.formElement.appendChild(this.iframeElement);
      }
      this.openModal();
      if (this.iframeElement) {
        this.focusIframe();
        this.setIframeLoadTimeout();
      } else {
        this.focusFallbackElement();
      }
    }

    /**
     * @return {void}
     */;
    _proto.addUnloadEvent = function addUnloadEvent() {
      window.addEventListener('beforeunload', function (event) {
        event.returnValue = 'Your session has expired. You can log in again from this page or go to the login page.';
      });
    }

    /**
    * @return {void}
    */;
    _proto.createIframeElement = function createIframeElement() {
      var _this = this;
      this.iframeElement = document.createElement('iframe');
      this.iframeElement.id = 'wpstg-auth-check-frame';
      this.iframeElement.style.border = '0px';
      this.iframeElement.title = this.noFrameElement.textContent;
      this.iframeElement.onload = function () {
        return _this.handleIframeLoad();
      };
      this.iframeElement.src = this.formElement.dataset.src;
      return this.iframeElement;
    }

    /**
     * @return {void}
     */;
    _proto.handleIframeLoad = function handleIframeLoad() {
      var height;
      var body;
      this.isIframeLoaded = true;
      this.formElement.classList.remove('loading');
      try {
        body = this.iframeElement.contentDocument.querySelector('body');
        height = body.offsetHeight;
      } catch (er) {
        this.handleIframeLoadError();
        return;
      }
      if (!body || !height) {
        this.handleIframeLoadError();
        return;
      }
      if (!body.classList.contains('interim-login-success')) {
        this.parentElement.style.maxHeight = height + 40 + 'px';
        return;
      }
      this.hide();
    }

    /**
     * @return {void}
     */;
    _proto.handleIframeLoadError = function handleIframeLoadError() {
      this.wrapElement.classList.add('fallback');
      this.parentElement.style.maxHeight = '';
      this.formElement.remove();
      this.noFrameElement.focus();
    }

    /**
     * @return {void}
     */;
    _proto.openModal = function openModal() {
      document.body.classList.add('modal-open');
      this.wrapElement.classList.remove('hidden');
    }

    /**
     * @return {void}
     */;
    _proto.focusIframe = function focusIframe() {
      this.iframeElement.focus();
    }

    /**
     * @return {void}
     */;
    _proto.setIframeLoadTimeout = function setIframeLoadTimeout() {
      var _this2 = this;
      setTimeout(function () {
        if (!_this2.isIframeLoaded) {
          _this2.wrapElement.classList.add('fallback');
          _this2.formElement.remove();
          _this2.noFrameElement.focus();
        }
      }, 2000);
    }

    /**
     * @return {void}
     */;
    _proto.focusFallbackElement = function focusFallbackElement() {
      this.noFrameElement.focus();
    }

    /**
     * @return {void}
     */;
    _proto.hide = function hide() {
      window.removeEventListener('beforeunload', function (event) {
        event.returnValue = null;
      });
      fadeOut(this.wrap, 200);
      wpstgAuthCheck.checkUserAuthentication();
    };
    return WpstgReAuth;
  }();
  var wpstgReAuth = new WpstgReAuth();

  var WpstgAuthCheck = /*#__PURE__*/function () {
    function WpstgAuthCheck(wpstgObject) {
      if (wpstgObject === void 0) {
        wpstgObject = wpstg;
      }
      this.wpstgObject = wpstgObject;
      this.count = 0;
      this.status = true;
      this.isAuthenticated = true;
    }

    /**
     * Start checking user authentication
     *
     * @return {void}
     */
    var _proto = WpstgAuthCheck.prototype;
    _proto.start = function start() {
      var _this = this;
      if (this.status) {
        setTimeout(function () {
          _this.checkUserAuthentication();
        }, 1000);
      }
    }

    /**
     * @return {void}
     */;
    _proto.stop = function stop() {
      this.resetCount();
      this.status = false;
    }

    /**
     * @return {void}
     */;
    _proto.resetCount = function resetCount() {
      this.count = 0;
    }

    /**
    * @return {void}
    */;
    _proto.checkUserAuthentication =
    /*#__PURE__*/
    function () {
      var _checkUserAuthentication = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee() {
        var _this2 = this;
        return _regeneratorRuntime().wrap(function _callee$(_context) {
          while (1) switch (_context.prev = _context.next) {
            case 0:
              fetch(this.wpstgObject.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: new URLSearchParams({
                  action: 'wpstg_check_user_is_authenticated',
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
                if (data.wpAuthCheck === false) {
                  _this2.count++;
                }
                _this2.isAuthenticated = data.wpAuthCheck;
                if (_this2.count === 2) {
                  _this2.stop();
                  _this2.createAuthCheckElements(data.redirectUrl);
                  return;
                }
                _this2.start();
              })["catch"](function (error) {
                console.error('Error:', error);
              });
            case 1:
            case "end":
              return _context.stop();
          }
        }, _callee, this);
      }));
      function checkUserAuthentication() {
        return _checkUserAuthentication.apply(this, arguments);
      }
      return checkUserAuthentication;
    }()
    /**
     * @param redirectUrl
     * @return {void}
     */
    ;
    _proto.createAuthCheckElements = function createAuthCheckElements(redirectUrl) {
      var isAlreadyAppended = document.getElementById('wpstg-auth-check-wrap') !== null;
      if (isAlreadyAppended) {
        document.getElementById('wpstg-auth-check-wrap').remove();
      }
      var wpAuthCheckWrap = document.createElement('div');
      wpAuthCheckWrap.id = 'wpstg-auth-check-wrap';
      wpAuthCheckWrap.classList.add('hidden');
      var wpAuthCheckBg = document.createElement('div');
      wpAuthCheckBg.id = 'wpstg-auth-check-bg';
      var wpAuthCheckDialog = document.createElement('div');
      wpAuthCheckDialog.id = 'wpstg-auth-check';
      var wpAuthCheckForm = document.createElement('div');
      wpAuthCheckForm.id = 'wpstg-auth-check-form';
      wpAuthCheckForm.classList.add('loading');
      wpAuthCheckForm.dataset.src = redirectUrl + '?interim-login=1&wp_lang=en_US';
      var wpAuthCheckFallback = document.createElement('div');
      wpAuthCheckFallback.classList.add('wpstg-auth-fallback');
      var wpAuthCheckExpired = document.createElement('p');
      wpAuthCheckExpired.innerHTML = '<b class="wpstg-auth-fallback-expired" tabindex="0">Session expired</b>';
      var wpAuthCheckLoginLink = document.createElement('p');
      wpAuthCheckLoginLink.innerHTML = "<a href=\"" + redirectUrl + "\" target=\"_blank\">Please log in again.</a> The login page will open in a new tab. After logging in you can close it and return to this page.";
      wpAuthCheckDialog.append(wpAuthCheckForm, wpAuthCheckFallback);
      wpAuthCheckFallback.append(wpAuthCheckExpired, wpAuthCheckLoginLink);
      wpAuthCheckWrap.append(wpAuthCheckBg, wpAuthCheckDialog);
      document.body.appendChild(wpAuthCheckWrap);
      wpstgReAuth.shows();
    };
    return WpstgAuthCheck;
  }();
  var wpstgAuthCheck = new WpstgAuthCheck();

  /**
   * Represents a class for managing and displaying process and success modals in WPStaging.
   * This class provides methods to start, open, initialize, update, and stop process modals.
   *
   * @class
   */
  var WpstgProcessModal = /*#__PURE__*/function () {
    function WpstgProcessModal(wpstgObject) {
      if (wpstgObject === void 0) {
        wpstgObject = wpstg;
      }
      this.wpstgObject = wpstgObject;
      this.action = '';
      this.isProcessCancelled = false;
      this.isProcessFinished = false;
      this.processTime = 0;
      this.processInterval = '';
      this.modal = null;
      this.disableCancelButton = false;
      this.title = '';
      this.showCancelButton = false;
      this.percentage = 0;
      this.cloneId = '';
      this.successModal = '';
      this.showProcessLogs = true;
      this.stagingSiteUrl = '';
    }

    /**
     * Starts the process, initializes intervals, and modifies UI elements as needed.
     *
     * @return {void}
     */
    var _proto = WpstgProcessModal.prototype;
    _proto.start = function start() {
      var _this = this;
      this.showCancelButton = true;
      this.processInterval = setInterval(function () {
        if (wpstgAuthCheck.isAuthenticated || _this.action === 'wpstg_push_processing') {
          _this.processTime++;
          _this.updateElapsedTime();
        }
      }, 1000);
      if (this.action === 'wpstg_delete_clone') {
        hide('.wpstg--modal--process--logs--tail');
        this.showCancelButton = false;
        var container = WPStagingCommon.getSwalContainer();
        var processPercentageElement = container.querySelector('.wpstg--modal--process--percent');
        processPercentageElement.textContent = 100;
      }
      if (this.action === 'wpstg_cloning' || this.action === 'wpstg_push_processing') {
        wpstgAuthCheck.status = true;
        wpstgAuthCheck.start();
      }
      hide('.wpstg-prev-step-link, #wpstg-start-updating, .wpstg-loader, #wpstg-cancel-cloning, .wpstg-log-details, .wpstg-progress-bar, #wpstg-processing-header');
      hide('.wpstg-processing-timer, #wpstg-cancel-pushing, .wpstg-progress-bar-wrapper, #wpstg-cancel-cloning-update, #wpstg-removing-clone');
    }

    /**
     * Stops the ongoing process, resets values, and clears intervals.
     *
     * @return {void}
     */;
    _proto.stop = function stop() {
      this.processTime = 0;
      this.modal = null;
      this.percentage = '';
      this.resetCancelChecks();
      clearInterval(this.processInterval);
      WPStagingCommon.closeSwalModal();
      loadingBar();
      hide('#wpstg-try-again, #wpstg-home-link, #wpstg-show-log-button, .wpstg-loader');
      if (this.action === 'wpstg_cloning' || this.action === 'wpstg_push_processing') {
        wpstgAuthCheck.stop();
      }
    };
    _proto.resetCancelChecks = function resetCancelChecks() {
      var _this2 = this;
      var element = document.getElementById('wpstg-new-clone');
      if (element === null) {
        setTimeout(function () {
          _this2.resetCancelChecks();
        }, 1000);
        return;
      }
      setTimeout(function () {
        _this2.isProcessCancelled = false;
        WPStaging.isCancelled = false;
      }, 2000);
      loadingBar('hidden');
    }

    /**
     * Cancels the ongoing process if confirmed by the user.
     *
     * @return {void}
     */;
    _proto.cancelProcess = function cancelProcess() {
      if (confirm('Are you sure you want to cancel cloning process?')) {
        WPStaging.isCancelled = true;
        this.isProcessCancelled = true;
        if (this.action === 'wpstg_push_processing') {
          var container = WPStagingCommon.getSwalContainer();
          var processTitleElement = container.querySelector('.wpstg--modal--process--title');
          processTitleElement.textContent = 'Canceling Please wait....';
          this.stop();
          show('#wpstg-workflow');
          WPStaging.messages = [];
        } else {
          this.cancelCloneAction();
        }
      }
    }

    /**
     * Sets the title and cancellation status based on the response job or status.
     *
     * @param {Object} response - The response object containing information about the process job and status.
     * @return {void}
     */;
    _proto.setTitle = function setTitle(response) {
      this.title = response.job;
      if (response.job === 'database' || response.job === 'jobCopyDatabaseTmp') {
        this.title = 'Copying Database';
        this.disableCancelButton = false;
      } else if (response.job === 'SearchReplace') {
        this.title = 'Processing Data';
        this.disableCancelButton = false;
      } else if (response.job === 'directories' || response.job === 'jobFileScanning') {
        this.title = 'Scanning Files';
        this.disableCancelButton = false;
      } else if (response.job === 'files' || response.job === 'data' || response.job === 'jobCopy' || response.job === 'jobSearchReplace') {
        this.title = 'Copying Files';
        this.disableCancelButton = false;
      } else if (response.job === 'Backup') {
        this.title = 'Backup Files Scanning';
        this.disableCancelButton = false;
      } else if (response.job === 'finish' || response.status === 'finished') {
        this.title = 'Process Finished';
        this.disableCancelButton = false;
      } else if (response.job === 'jobDatabaseRename') {
        this.title = 'Renaming Database';
        this.disableCancelButton = true;
      } else if (response.job === 'jobData') {
        this.title = 'Updating Database Data';
        this.disableCancelButton = true;
      }
    }

    /**
     * Sets the stored percentage value if the provided parameter is not null.
     *
     * @param {Object} params - Parameters containing the percentage value.
     * @return {void}
     */;
    _proto.setPercentage = function setPercentage(params) {
      if (params.percentage !== null) {
        this.percentage = params.percentage;
      }
    }

    /**
     * Initializes the process modal with specific behavior and appearance configurations.
     *
     * @return {void}
     */;
    _proto.initializeProcessModal = function initializeProcessModal() {
      var _this3 = this;
      var modal = document.querySelector('#wpstg--modal--backup--process');
      var html = modal.innerHTML;
      modal.parentNode.removeChild(modal);
      this.modal = {
        html: null,
        cancelBtnTxt: null,
        processTime: 0,
        instance: WPStagingCommon.getSwalModal(true, {
          content: 'wpstg--process--content'
        }).fire({
          html: html,
          cancelButtonText: 'Cancel',
          showCancelButton: this.showCancelButton,
          showConfirmButton: false,
          showCloseButton: false,
          showLoaderOnConfirm: false,
          allowEscapeKey: false,
          allowOutsideClick: false,
          onRender: function onRender() {
            var cancelButton = WPStagingCommon.getSwalContainer().getElementsByClassName('wpstg--swal2-cancel wpstg--btn--cancel')[0];
            var btnCancel = cancelButton.cloneNode(true);
            cancelButton.parentNode.replaceChild(btnCancel, cancelButton);
            btnCancel.addEventListener('click', function () {
              _this3.cancelProcess();
            });
          }
        })
      };
      this.start();
    }

    /**
     * Sets the content of the process modal based on the stored title, percentage, and cancellation status.
     *
     * @return {void}
     */;
    _proto.setProcessModal = function setProcessModal() {
      var container = WPStagingCommon.getSwalContainer();
      var cancelButton = container.getElementsByClassName('wpstg--swal2-cancel wpstg--btn--cancel')[0];
      var processTitleElement = container.querySelector('.wpstg--modal--process--title');
      var processPercentageElement = container.querySelector('.wpstg--modal--process--percent');
      processTitleElement.textContent = this.title;
      processPercentageElement.textContent = this.percentage;
      cancelButton.disabled = this.disableCancelButton;
    }

    /**
     * Opens and initializes a process modal with the provided data and parameters.
     *
     * @param {Object|undefined} data - Data related to the process.
     * @param {Object} params - Parameters for configuring the process modal.
     * @param {boolean} [showLogs=false] - Whether to display logs in the modal.
     * @param {boolean} [showCancelButton=true] - Whether to show the cancel button in the modal.
     * @return {void}
     */;
    _proto.openProcessModal = function openProcessModal(data, params, showLogs, showCancelButton) {
      if (showLogs === void 0) {
        showLogs = false;
      }
      if (showCancelButton === void 0) {
        showCancelButton = true;
      }
      if (!this.isProcessCancelled && !WPStaging.isCancelled) {
        if (data !== undefined) {
          this.action = data.action;
          this.cloneId = data.cloneID;
        }
        this.showCancelButton = showCancelButton;
        this.showLogs = showLogs;
        if (!this.modal) {
          this.initializeProcessModal();
        } else {
          if (params.job) {
            this.setTitle(params);
            this.setPercentage(params);
            this.setProcessModal();
            if (params.job === 'finish') {
              this.stop();
            }
          } else {
            if (params.status === 'finished') {
              this.stop();
            }
          }
        }
      }
    };
    /**
     * Formats the given time duration (in seconds) into a string representation (HH:mm:ss).
     *
     * @return {string} The formatted time duration string.
     */
    _proto.formatTimeDuration = function formatTimeDuration() {
      return new Date(this.processTime * 1000).toISOString().slice(11, 19) + 's';
    }

    /**
     * Updates the displayed elapsed time in the modal if a process interval is set.
     *
     * @return {void}
     */;
    _proto.updateElapsedTime = function updateElapsedTime() {
      if (this.processInterval !== null) {
        var container = WPStagingCommon.getSwalContainer();
        var elapsedTimeElement = container.querySelector('.wpstg--modal--process--elapsed-time');
        elapsedTimeElement.textContent = this.formatTimeDuration();
      }
    };
    /**
     * Determines the corresponding cancellation action name based on the current action.
     *
     * @return {string|undefined} The cancellation action name or undefined if no match is found.
     */
    _proto.getCancelCloneAction = function getCancelCloneAction() {
      var _this4 = this;
      var actions = [{
        name: 'wpstg_cloning',
        cancel: 'wpstg_cancel_clone'
      }, {
        name: 'wpstg_reset',
        cancel: 'wpstg_cancel_update'
      }, {
        name: 'wpstg_update',
        cancel: 'wpstg_cancel_update'
      }, {
        name: 'wpstg_push_processing',
        cancel: 'wpstg_cancel_push_processing'
      }];
      if (this.action !== '') {
        var result = actions.find(function (action) {
          return action.name === _this4.action;
        });
        return result.cancel;
      }
    }

    /**
     * Cancels the ongoing clone action if there is one, using a fetch request.
     * If the cancellation is successful, it stops the action and optionally reloads the page.
     * If there are further actions or the cancellation is not successful, it retries the cancellation process recursively.
     *
     * @return {void}
     */;
    _proto.cancelCloneAction = function cancelCloneAction() {
      var _this5 = this;
      if (this.action) {
        fetch(this.wpstgObject.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          body: new URLSearchParams({
            action: this.getCancelCloneAction(),
            accessToken: this.wpstgObject.accessToken,
            nonce: this.wpstgObject.nonce,
            clone: this.cloneId
          }),
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        }).then(function (response) {
          if (response.ok) {
            return response.json();
          }
          return Promise.reject(response);
        }).then(function (response) {
          if (response && 'undefined' !== typeof response["delete"] && response["delete"] === 'finished' || response === true) {
            _this5.stop();
            WPStaging.loadOverview();
            WPStaging.messages = [];
          }
          if (response !== true) {
            _this5.cancelCloneAction();
          }
        })["catch"](function (error) {
          console.log(_this5.wpstgObject.i18n['somethingWentWrong'], error);
        });
      }
    }

    /**
     * Initializes the success modal with the provided parameters and displays it.
     *
     * @param {Object} params - The parameters for configuring the success modal content.
     * @return {void}
     */;
    _proto.initializeSuccessModal = function initializeSuccessModal(params) {
      var _this6 = this;
      this.successModal = this.setSuccessModalContent(params);
      WPStagingCommon.getSwalModal(true, {
        confirmButton: 'wpstg--btn--confirm wpstg-green-button wpstg-button wpstg-link-btn wpstg-100-width'
      }).fire({
        'icon': 'success',
        'html': this.successModal.innerHTML,
        'confirmButtonText': 'Close',
        'showCancelButton': false,
        'showConfirmButton': true,
        'allowOutsideClick': false
      }).then(function (result) {
        if (result.value) {
          WPStagingCommon.closeSwalModal();
          hide('.wpstg-loader');
          WPStaging.messages = [];
          if (_this6.action === 'wpstg_push_processing') {
            location.reload();
          }
          _this6.action = '';
        }
      });
      if (this.showProcessLogs) {
        show('.wpstg--modal--download--logs--wrapper');
      }
      hide('.wpstg-loader');
      if (this.action !== 'wpstg_delete_clone') {
        WPStaging.loadOverview();
      }
    }

    /**
     * Sets the content of the success modal based on the provided parameters.
     *
     * @param {Object} params - The parameters for configuring the modal content.
     * @param {string|null} params.title - The title to be displayed in the modal.
     * @param {string|null|undefined} params.body - The body content of the modal.
     * @returns {HTMLElement} The modified modal element.
     */;
    _proto.setSuccessModalContent = function setSuccessModalContent(params) {
      var modal = document.getElementById('wpstg--modal--backup--download');
      if (params.title !== null) {
        modal.innerHTML = modal.innerHTML.replace('{title}', params.title);
      }
      if (this.showProcessLogs) {
        modal.innerHTML = modal.innerHTML.replace('{btnTxtLog}', '<span style="text-decoration: underline">Show Logs</span>');
      }
      if (params.body !== null && typeof params.body !== 'undefined') {
        var messageBody = params.body;
        if (this.stagingSiteUrl !== null) {
          messageBody += '<br><strong><a href="' + this.stagingSiteUrl + '" target="_blank" id="wpstg-clone-url">' + this.stagingSiteUrl + '</a></strong>';
        }
        modal.innerHTML = modal.innerHTML.replace('{text}', messageBody);
      } else {
        modal.innerHTML = modal.innerHTML.replace('{text}', '');
      }
      return modal;
    }

    /**
     * Opens a success modal after successfully cloning process.
     *
     * @param {Object} params - The parameters for initializing the success modal.
     * @param {boolean} [showLogs=false] - Whether to display process logs in the modal.
     * @param {string|null} [siteUrl=null] - The URL of the staging site, if applicable.
     * @return {void}
     */;
    _proto.openSuccessModal = function openSuccessModal(params, showLogs, siteUrl) {
      if (showLogs === void 0) {
        showLogs = false;
      }
      if (siteUrl === void 0) {
        siteUrl = null;
      }
      this.stagingSiteUrl = siteUrl;
      this.showProcessLogs = showLogs;
      this.initializeSuccessModal(params);
    };
    return WpstgProcessModal;
  }();

  var BackupPluginsNotice = /*#__PURE__*/function () {
    function BackupPluginsNotice(wpstgObject) {
      if (wpstgObject === void 0) {
        wpstgObject = wpstg;
      }
      this.wpstgObject = wpstgObject;
      this.wpstgBackupNoticeCloseButton = null;
      this.wpstgBackupNoticeReminderButton = null;
      this.init();
    }
    var _proto = BackupPluginsNotice.prototype;
    _proto.init = function init() {
      var _this = this;
      document.addEventListener('DOMContentLoaded', function () {
        _this.wpstgBackupNoticeCloseButton = document.getElementById('wpstg-backup-plugin-notice-close');
        _this.wpstgBackupNoticeReminderButton = document.getElementById('wpstg-backup-plugin-notice-remind-me');
        _this.addEvent();
      });
    };
    _proto.addEvent = function addEvent() {
      var _this2 = this;
      if (this.wpstgBackupNoticeCloseButton !== null) {
        this.wpstgBackupNoticeCloseButton.addEventListener('click', function () {
          _this2.closeBackupPluginNotice();
        });
      }
      if (this.wpstgBackupNoticeReminderButton !== null) {
        this.wpstgBackupNoticeReminderButton.addEventListener('click', function () {
          _this2.closeBackupPluginNotice('wpstg_backup_plugin_notice_remind_me');
        });
      }
    };
    _proto.closeBackupPluginNotice = function closeBackupPluginNotice(action) {
      if (action === void 0) {
        action = 'wpstg_backup_plugin_notice_close';
      }
      fetch(this.wpstgObject.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new URLSearchParams({
          action: action,
          nonce: this.wpstgObject.nonce
        }),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        }
      }).then(function (response) {
        if (response.ok) {
          return response.json();
        }
      }).then(function (data) {
        if (data.success) {
          qs('.wpstg-backup-plugin-notice-container').style.opacity = 0;
        }
      })["catch"](function (error) {
        qs('.wpstg-backup-plugin-notice-container').style.opacity = 0;
      });
    };
    return BackupPluginsNotice;
  }();
  new BackupPluginsNotice();

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
      tableSelector: null,
      notyf: null,
      areAllTablesChecked: true,
      handleDisplayDependencies: handleDisplayDependencies,
      handleToggleElement: handleToggleElement,
      handleCopyPaste: handleCopyPaste,
      handleCopyToClipboard: handleCopyToClipboard,
      messages: [],
      requestType: '',
      modal: {
        instance: '',
        processTime: 0,
        processInterval: '',
        currentJob: ''
      }
    };
    that.wpstgProcessModal = new WpstgProcessModal();
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
      }

      // Create cache and return
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
      cache.get('.wpstg-loading-bar-container').css('visibility', 'hidden');
      cache.get('.wpstg-loader').hide();
      $('.wpstg--modal--process--generic-problem').show().html(message);

      // Error event information for Staging
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
          'action': 'wpstg_staging_job_error',
          'accessToken': wpstg.accessToken,
          'nonce': wpstg.nonce,
          'error_message': message
        }
      });
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
      $workFlow
      // Check / Un-check All Database Tables New
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

      // Check the max length of the clone name and if the clone name already exists
      .on('keyup', '#wpstg-new-clone-id', function () {
        // Hide previous errors
        document.getElementById('wpstg-error-details').style.display = 'none';

        // This request was already sent, clear it up!
        if ('number' === typeof timer) {
          clearInterval(timer);
        }

        // Early bail if site name is empty
        if (this.value === undefined || this.value === '') {
          cache.get('#wpstg-new-clone-id').removeClass('wpstg-error-input');
          cache.get('#wpstg-start-cloning').removeAttr('disabled');
          cache.get('#wpstg-clone-id-error').text('').hide();
          return;
        }

        // Convert the site name to directory name (slugify the site name to create directory name)
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
      })
      // Restart cloning process
      .on('click', '#wpstg-start-cloning', function () {
        resetErrors();
        that.isCancelled = false;
        that.progressBar = 0;
      }).on('input', '#wpstg-new-clone-id', function () {
        if ($('#wpstg-clone-directory').length < 1) {
          return;
        }
        var slug = WPStagingCommon.slugify(this.value).substring(0, 16);
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
      $workFlow
      // Cancel cloning
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
      })
      // Resume cloning
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
      })
      // Cancel update cloning
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
      })
      // Restart cloning
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
      })
      // Delete clone - confirmation
      .on('click', '.wpstg-remove-clone[data-clone]', function (e) {
        resetErrors();
        e.preventDefault();
        $workFlow.removeClass('active');
        cache.get('.wpstg-loader').show();
        var cloneData = $(this).data('clone');
        var stagingSiteName = $(this).data('name');
        ajax({
          action: 'wpstg_confirm_delete_clone',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
          clone: $(this).data('clone')
        }, function (response) {
          WPStagingCommon.getSwalModal(true, {
            popup: 'wpstg-swal-popup wpstg-centered-modal wpstg-delete-staging-site-modal',
            content: 'wpstg--process--content'
          }).fire({
            title: 'Delete staging site "' + stagingSiteName + '"',
            icon: 'error',
            html: response,
            width: '100%',
            focusConfirm: false,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#e01e5a',
            showCancelButton: true,
            onRender: function onRender() {
              var wpstgUnselectAllTables = document.getElementById('wpstg-unselect-all-tables');
              var wpstgUnselectAllTablesId = document.getElementById('wpstg-unselect-all-tables-id');
              if (wpstgUnselectAllTables !== null) {
                wpstgUnselectAllTables.addEventListener('click', function (e) {
                  if (that.areAllTablesChecked === false) {
                    cache.get('#wpstg_select_tables_cloning .wpstg-db-table').prop('selected', 'selected');
                    cache.get('#wpstgUnselectAllTablesId').text('Unselect All');
                    wpstgUnselectAllTablesId.textContent = 'Unselect All';
                    cache.get('.wpstg-db-table-checkboxes').prop('checked', true);
                    that.areAllTablesChecked = true;
                  } else {
                    cache.get('#wpstg_select_tables_cloning .wpstg-db-table').prop('selected', false);
                    cache.get('#wpstgUnselectAllTablesId').text('Select All');
                    wpstgUnselectAllTablesId.textContent = 'Select All';
                    cache.get('.wpstg-db-table-checkboxes').prop('checked', false);
                    that.areAllTablesChecked = false;
                  }
                });
              }
              var swalContainer = document.querySelector('.wpstg--swal2-html-container');
              swalContainer.classList.remove('wpstg--swal2-html-container');
              var _deleteButton = document.querySelector('.wpstg--swal2-confirm');
              _deleteButton.style.background = '#e01e5a';
              document.querySelector('.wpstg--swal2-x-mark-line-left').style.background = '#e01e5a';
              document.querySelector('.wpstg--swal2-x-mark-line-right').style.background = '#e01e5a';
              document.querySelector('.wpstg--swal2-icon.wpstg--swal2-error').style.borderColor = '#e01e5a';
              var _btnCancel = WPStagingCommon.getSwalContainer().getElementsByClassName('wpstg--swal2-cancel wpstg--btn--cancel')[0];
              var btnCancel = _btnCancel.cloneNode(true);
              _btnCancel.parentNode.replaceChild(btnCancel, _btnCancel);
              btnCancel.addEventListener('click', function (e) {
                WPStagingCommon.closeSwalModal();
                that.modal.instance = null;
                cache.get('.wpstg-loader').removeClass('wpstg-finished');
                cache.get('.wpstg-loader').hide();
              });
            }
          }).then(function (result) {
            if (result.value) {
              var deleteDirectory = document.querySelector('#deleteDirectory:checked');
              var deleteDir = '';
              var excludedTables = [];
              if (deleteDirectory !== null) {
                deleteDir = deleteDirectory.getAttribute('data-deletepath');
              }
              $('.wpstg-db-table input:not(:checked)').each(function () {
                excludedTables.push(this.name);
              });

              // WPStagingCommon.closeSwalModal();
              that.modal.instance = null;
              var params = {
                'job': 'delete'
              };
              that.wpstgProcessModal.openProcessModal({
                'action': 'wpstg_delete_clone',
                'cloneID': cloneData
              }, params, false, false);
              deleteClone(cloneData, deleteDir, excludedTables);
            }
          });
        }, 'HTML');
      })
      // Delete clone - confirmed
      .on('click', '#wpstg-remove-clone', function (e) {
        resetErrors();
        e.preventDefault();
        cache.get('.wpstg-loader').show();
        deleteClone($(this).data('clone'));
      })
      // Cancel deleting clone
      .on('click', '#wpstg-cancel-removing', function (e) {
        e.preventDefault();
        $('.wpstg-clone').removeClass('active');
        cache.get('#wpstg-removing-clone').html('');
      })
      // Update
      .on('click', '.wpstg-execute-clone', function (e) {
        e.preventDefault();
        var clone = $(this).data('clone');
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
            showErrorModal(jsonResponse);
            return;
          }
          $workFlow.html(response);
          // register check disk space function for clone update process.
          checkDiskSpace();
          that.directoryNavigator = new WpstgDirectoryNavigation('#wpstg-directories-listing', wpstg, that.notyf);
          that.directoryNavigator.countSelectedFiles();
          that.tableSelector = new WpstgTableSelection('#wpstg-scanning-db', '#wpstg-workflow', '#wpstg_network_clone', '#wpstg_select_tables_cloning', wpstg, that.notyf);
          that.tableSelector.countSelectedTables();
          that.cloneExcludeFilters = new WpstgExcludeFilters();
          that.switchStep(2);
        }, 'HTML');
      }).on('click', '#wpstg-update-cloning-site-button', function (e) {
        window.open(that.data.cloneHostname, '_blank');
      })
      // Reset Clone
      .on('click', '.wpstg-reset-clone', function (e) {
        e.preventDefault();
        var clone = $(this).data('clone');
        var resetModal = new WpstgResetModal(clone);
        resetModal.setNetworkClone($(this).data('network') === 'yes');
        var promise = resetModal.showModal();
        that.areAllTablesChecked = true;
        promise.then(function (result) {
          if (result.value) {
            var dirNavigator = resetModal.getDirectoryNavigator();
            var tableSelector = resetModal.getTableSelector();
            var exclFilters = resetModal.getExcludeFilters().getExcludeFilters();
            var includedTables = '';
            var excludedTables = '';
            var selectedTablesWithoutPrefix = '';
            var allTablesExcluded = false;
            if (tableSelector !== null) {
              includedTables = tableSelector.getIncludedTables();
              excludedTables = tableSelector.getExcludedTables();
              selectedTablesWithoutPrefix = tableSelector.getSelectedTablesWithoutPrefix();
            }
            if (includedTables.length > excludedTables.length) {
              includedTables = '';
            } else if (excludedTables.length > includedTables.length) {
              excludedTables = '';
              allTablesExcluded = includedTables === '';
            }
            resetClone(clone, {
              includedTables: includedTables,
              excludedTables: excludedTables,
              allTablesExcluded: allTablesExcluded,
              selectedTablesWithoutPrefix: selectedTablesWithoutPrefix,
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
      var retryLimit = 5;
      var retryTimeout = 10000 * tryCount;
      incrementRatio = parseInt(incrementRatio);
      if (!isNaN(incrementRatio)) {
        retryTimeout *= incrementRatio;
      }
      var errorMsgFooter = 'Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP STAGING Small Server Settings</a> or submit an error report and contact us. <br/><br/><strong>Tip:</strong> If you get this error while pushing, you can also use the <strong>BACKUP & MIGRATION</strong> feature to move your staging site to live. <a href=\'https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/\' target=\'_blank\'>Read more.</a>';
      $.ajax({
        url: ajaxurl + '?action=wpstg_processing&_=' + Date.now() / 1000,
        type: 'POST',
        dataType: dataType,
        cache: false,
        data: data,
        error: function () {
          var _error = _asyncToGenerator( /*#__PURE__*/_regeneratorRuntime().mark(function _callee(xhr, textStatus, errorThrown) {
            var result, helpContent, errorCode;
            return _regeneratorRuntime().wrap(function _callee$(_context) {
              while (1) switch (_context.prev = _context.next) {
                case 0:
                  if (!(WPStaging$1.requestType === 'wpstg_cloning')) {
                    _context.next = 13;
                    break;
                  }
                  _context.next = 3;
                  return WPStagingCommon.checkMemoryExhaustion(WPStaging$1.requestType);
                case 3:
                  result = _context.sent;
                  helpContent = '<br/><br/><button class="wpstg-btn wpstg-primary-btn wpstg-report-issue-button" type="button">CONTACT US</button> for help in solving this issue.';
                  if (wpstg.isPro) {
                    helpContent = '<br/><br/>Read <a target="_blank" href="' + WPStagingCommon.memoryExhaustArticleLink + '">this article</a> for solving this issue.<br/><br/>Please contact WP Staging support if you need further assistance.';
                  }
                  if (!(result !== false)) {
                    _context.next = 13;
                    break;
                  }
                  window.removeEventListener('beforeunload', WPStaging$1.warnIfClosingDuringProcess);
                  WPStaging$1.requestType = '';
                  // Refetch staging sites and stop all processing
                  cache.get('.wpstg-loader').hide();
                  loadOverview();
                  WPStagingCommon.showErrorModal(result.message + '.' + helpContent);
                  return _context.abrupt("return");
                case 13:
                  // try again after 10 seconds
                  tryCount++;
                  if (tryCount <= retryLimit) {
                    console.log('RETRYING ' + tryCount + '/' + retryLimit);
                    setTimeout(function () {
                      ajax(data, callback, dataType, showErrors, tryCount, incrementRatio);
                      return;
                    }, retryTimeout);
                  } else {
                    WPStaging$1.requestType = '';
                    console.log('RETRYING LIMIT');
                    errorCode = 'undefined' === typeof xhr.status ? 'Unknown' : xhr.status;
                    showError('Fatal Error:  ' + errorCode + ' ' + errorMsgFooter);
                  }
                case 15:
                case "end":
                  return _context.stop();
              }
            }, _callee);
          }));
          function error(_x, _x2, _x3) {
            return _error.apply(this, arguments);
          }
          return error;
        }(),
        success: function success(data) {
          if ('function' === typeof callback) {
            callback(data);
          }
        },
        statusCode: {
          404: function _() {
            if (tryCount >= retryLimit) {
              showError('Error 404 - Can\'t find ajax request URL! ' + errorMsgFooter);
            }
          },
          500: function _() {
            if (tryCount >= retryLimit) {
              showError('Fatal Error 500 - Internal server error while processing the request! ' + errorMsgFooter);
            }
          },
          504: function _() {
            if (tryCount > retryLimit) {
              showError('Error 504 - It seems your server is rate limiting ajax requests. Please try to resume after a minute. ' + errorMsgFooter);
            }
          },
          502: function _() {
            if (tryCount >= retryLimit) {
              showError('Error 502 - It seems your server is rate limiting ajax requests. Please try to resume after a minute. ' + errorMsgFooter);
            }
          },
          503: function _() {
            if (tryCount >= retryLimit) {
              showError('Error 503 - It seem your server is rate limiting ajax requests. Please try to resume after a minute. ' + errorMsgFooter);
            }
          },
          429: function _() {
            if (tryCount >= retryLimit) {
              showError('Error 429 - It seems your server is rate limiting ajax requests. Please try to resume after a minute. ' + errorMsgFooter);
            }
          },
          403: function _() {
            if (tryCount >= retryLimit) {
              showError('Refresh page or login again! The process should be finished successfully. \n\ ');
            }
          },
          400: function _() {
            if (tryCount >= retryLimit) {
              showError('Error 400: ' + '<strong>It looks like you have been logged out.</strong><br/><br/> <a href=\'/wp-login.php\' target=\'_blank\' id="wpstg-login-link">Log in again and try again!</a>');
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
      $workFlow
      // Next Button
      .on('click', '.wpstg-next-step-link', function (e) {
        e.preventDefault();
        var $this = $(this);
        if ($('#wpstg_clone_hostname').length && !validateTargetHost()) {
          $('#wpstg_clone_hostname').focus();
          return false;
        }
        if ($this.data('action') === 'wpstg_update' || $this.data('action') === 'wpstg_reset') {
          // Update / Reset Clone - confirmed
          if ($this.data('action') === 'wpstg_update') {
            var selectedSite = $this;
            var selectedSiteWorkFlow = $workFlow;
            var selectedAction = $this.data('action');
            WPStagingCommon.getSwalModal(false, {
              popup: 'wpstg-update-staging-site-modal wpstg-swal-popup wpstg-centered-modal',
              content: 'wpstg--process--content'
            }).fire({
              title: 'Do you want to update the staging site?',
              icon: 'warning',
              html: '<div class="wpstg-confirm-text-line-height wpstg-mt-10px">This will overwrite the staging site with all the selected data from the live site.<br>Use this only if you want to clone the live site again.<br><br> ' + 'Unselect all tables and folders that you do not want to overwrite.<br>Do not cancel the update process! This could destroy the staging site.<br>' + 'If you are unsure, create a backup of the staging site before proceeding.<br>' + 'There is no automatic merging of database data!</div>',
              width: '610px',
              focusConfirm: false,
              confirmButtonText: 'Update',
              showCancelButton: true,
              onRender: function onRender() {
                var swalContainer = document.querySelector('.wpstg--swal2-html-container');
                swalContainer.classList.remove('wpstg--swal2-html-container');
                var _btnCancel = WPStagingCommon.getSwalContainer().getElementsByClassName('wpstg--swal2-cancel wpstg--btn--cancel')[0];
                var btnCancel = _btnCancel.cloneNode(true);
                _btnCancel.parentNode.replaceChild(btnCancel, _btnCancel);
                btnCancel.addEventListener('click', function (e) {
                  WPStagingCommon.closeSwalModal();
                });
              }
            }).then(function (result) {
              if (result.value) {
                var selectedSiteDbCheck = document.querySelector('#wpstg-ext-db');
                if (selectedAction === 'wpstg_cloning') {
                  if (selectedSiteDbCheck.checked) {
                    verifyExternalDatabase(selectedSite, selectedSiteWorkFlow);
                    return;
                  }
                }
                WPStagingCommon.closeSwalModal();
                proceedCloning(selectedSite, selectedSiteWorkFlow);
              }
            });
            return false;
          }
        }
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
      })
      // Previous Button
      .on('click', '.wpstg-prev-step-link', function (e) {
        e.preventDefault();
        cache.get('.wpstg-loader').hide();
        cache.get('.wpstg-loader').removeClass('wpstg-finished');
        loadOverview();
      });
    };

    /**
     * Check if clone destination dir is writable
     */
    var isWritableCloneDestinationDir = function isWritableCloneDestinationDir() {
      return new Promise(function (resolve) {
        ajax({
          action: 'wpstg_is_writable_clone_destination_dir',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
          cloneDir: $('#wpstg_clone_dir').val()
        }, function (response) {
          if (response.success) {
            cache.get('.wpstg-loader').hide();
            resolve(true);
          } else {
            var _response$data;
            showError('Something went wrong! Error: ' + ((_response$data = response.data) == null ? void 0 : _response$data.message));
            cache.get('.wpstg-loader').hide();
            document.getElementById('wpstg-error-wrapper').scrollIntoView();
            resolve(false);
          }
        }, 'json', false);
      });
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
        databaseDatabase: cache.get('#wpstg_db_database').val(),
        databaseSsl: cache.get('#wpstg_db_ssl').is(':checked')
      }, function (response) {
        // Undefined Error
        if (false === response) {
          showError('Something went wrong! Error: No response.' + 'Please try again. If that does not help, ' + '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a> ');
          cache.get('.wpstg-loader').hide();
          return;
        }

        // Throw Error
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
          var render = '<table class="wpstg-db-comparison-table"><thead><tr><th>Property</th><th>Production DB</th><th>Staging DB</th><th>Status</th></tr></thead><tbody>';
          response.checks.forEach(function (x) {
            var icon = '<span class="wpstg-css-tick"></span>';
            if (x.production !== x.staging) {
              icon = '<span class="wpstg-css-cross"></span>';
            }
            render += '<tr><td>' + x.name + '</td><td>' + x.production + '</td><td>' + x.staging + '</td><td>' + icon + '</td></tr>';
          });
          render += '</tbody></table><p>Note: Some MySQL/MariaDB properties do not match. You may proceed but the staging site may not work as expected.</p>';
          WPStagingCommon.getSwalModal(true, {
            popup: 'wpstg-swal-popup wpstg-db-comparison-modal wpstg-centered-modal'
          }).fire({
            title: 'Different Database Properties',
            icon: 'warning',
            html: render,
            width: '650px',
            focusConfirm: false,
            confirmButtonText: 'Proceed',
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
      that.data.cloneName = $('#wpstg-new-clone-id').val() || that.data.cloneID;
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
      that.data.includedTables = '';
      that.data.excludedTables = '';
      that.data.allTablesExcluded = false;
      if (that.tableSelector !== null) {
        that.data.includedTables = that.tableSelector.getIncludedTables();
        that.data.excludedTables = that.tableSelector.getExcludedTables();
        that.data.selectedTablesWithoutPrefix = that.tableSelector.getSelectedTablesWithoutPrefix();
      }
      if (that.data.includedTables.length > that.data.excludedTables.length) {
        that.data.includedTables = '';
      } else if (that.data.excludedTables.length > that.data.includedTables.length) {
        that.data.excludedTables = '';
        that.data.allTablesExcluded = that.data.includedTables === '';
      }
      that.data.databaseServer = $('#wpstg_db_server').val();
      that.data.databaseUser = $('#wpstg_db_username').val();
      that.data.databasePassword = $('#wpstg_db_password').val();
      that.data.databaseDatabase = $('#wpstg_db_database').val();
      that.data.databasePrefix = $('#wpstg_db_prefix').val();
      that.data.databaseSsl = $('#wpstg_db_ssl').is(':checked');
      var cloneDir = $('#wpstg_clone_dir').val();
      that.data.cloneDir = encodeURIComponent($.trim(cloneDir));
      that.data.cloneHostname = $('#wpstg_clone_hostname').val();
      that.data.cronDisabled = $('#wpstg_disable_cron').is(':checked');
      that.data.emailsAllowed = $('#wpstg_allow_emails').is(':checked');
      that.data.networkClone = $('#wpstg_network_clone').is(':checked');
      that.data.uploadsSymlinked = $('#wpstg_symlink_upload').is(':checked');
      that.data.cleanPluginsThemes = $('#wpstg-clean-plugins-themes').is(':checked');
      that.data.cleanUploadsDir = $('#wpstg-clean-uploads').is(':checked');
    };
    var proceedCloning = function proceedCloning($this, workflow) {
      if ($this.data('action') === 'wpstg_cloning') {
        isWritableCloneDestinationDir().then(function (result) {
          if (!result) {
            return;
          }
          runCloningSteps($this, workflow);
        });
      } else {
        runCloningSteps($this, workflow);
      }
    };
    var runCloningSteps = function runCloningSteps($this, workflow) {
      var animatedLoader = cache.get('.wpstg-loading-bar-container');
      animatedLoader.css('visibility', 'visible');

      // Prepare data
      that.data = {
        action: $this.data('action'),
        accessToken: wpstg.accessToken,
        nonce: wpstg.nonce
      };

      // Cloning data
      getCloningData();
      sendCloningAjax(workflow);
    };
    var sendCloningAjax = function sendCloningAjax(workflow) {
      var animatedLoader = cache.get('.wpstg-loading-bar-container');

      // Send ajax request
      ajax(that.data, function (response) {
        // Undefined Error
        if (false === response) {
          showError('Something went wrong!<br/><br/> Go to WP Staging > Settings and lower \'File Copy Limit\' and \'DB Query Limit\'. Also set \'CPU Load Priority to low \'' + 'and try again. If that does not help, ' + '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a> ');
        }
        if (response.length < 1) {
          animatedLoader.css('visibility', 'hidden');
          showError('Something went wrong! No response.  Go to WP Staging > Settings and lower \'File Copy Limit\' and \'DB Query Limit\'. Also set \'CPU Load Priority to low \'' + 'and try again. If that does not help, ' + '<a href=\'https://wp-staging.com/support/\' target=\'_blank\'>open a support ticket</a> ');
        }
        var jsonResponse = tryParseJson(response);
        if (jsonResponse !== false && jsonResponse.success === false) {
          animatedLoader.css('visibility', 'hidden');
          showErrorModal(jsonResponse);
          return;
        }
        animatedLoader.css('visibility', 'hidden');
        workflow.html(response);
        that.cloneExcludeFilters = null;
        if (that.data.action === 'wpstg_scanning') {
          that.areAllTablesChecked = true;
          that.directoryNavigator = new WpstgDirectoryNavigation('#wpstg-directories-listing', wpstg, that.notyf);
          that.tableSelector = new WpstgTableSelection('#wpstg-scanning-db', '#wpstg-workflow', '#wpstg_network_clone', '#wpstg_select_tables_cloning', wpstg);
          that.switchStep(2);
          that.cloneExcludeFilters = new WpstgExcludeFilters();
          that.directoryNavigator.countSelectedFiles();
          that.tableSelector.countSelectedTables();
        } else if (that.data.action === 'wpstg_cloning' || that.data.action === 'wpstg_update' || that.data.action === 'wpstg_reset') {
          that.switchStep(3);
        }

        // Start cloning
        that.startCloning();
      }, 'HTML');
    };
    var showErrorModal = function showErrorModal(response) {
      var errorModal = new WpstgModal('wpstg_modal_error', wpstg);
      errorModal.show(Object.assign({
        title: 'Error',
        icon: 'error',
        html: wpstg.i18n['somethingWentWrong'] + (response.message !== undefined ? '<br/>' + response.message : ''),
        width: '500px',
        confirmButtonText: 'Ok',
        showCancelButton: false,
        customClass: {
          confirmButton: 'wpstg--btn--confirm wpstg-blue-primary wpstg-button wpstg-link-btn',
          cancelButton: 'wpstg--btn--cancel wpstg-blue-primary wpstg-link-btn',
          actions: 'wpstg--modal--actions',
          popup: 'wpstg-swal-popup wpstg-centered-modal'
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
      } catch (e) {
        // do nothing on catch
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
      var animatedLoader = cache.get('.wpstg-loading-bar-container');
      animatedLoader.css('visibility', 'visible');
      ajax({
        action: 'wpstg_overview',
        accessToken: wpstg.accessToken,
        nonce: wpstg.nonce
      }, function (response) {
        if (response.length < 1) {
          showError('Something went wrong! No response. Please try the <a href=\'https://wp-staging.com/docs/wp-staging-settings-for-small-servers/\' target=\'_blank\'>WP Staging Small Server Settings</a> or submit an error report.');
        }
        cache.get('.wpstg-current-step');
        animatedLoader.css('visibility', 'hidden');
        $workFlow.html(response);
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
     * @param {String} deleteDir
     * @param {array} excludedTables
     */
    var deleteClone = function deleteClone(clone, deleteDir, excludedTables) {
      ajax({
        action: 'wpstg_delete_clone',
        clone: clone,
        accessToken: wpstg.accessToken,
        nonce: wpstg.nonce,
        excludedTables: excludedTables,
        deleteDir: deleteDir
      }, function (response) {
        if (response) {
          showAjaxFatalError(response);

          // Finished
          if ('undefined' !== typeof response["delete"] && (response["delete"] === 'finished' || response["delete"] === 'unfinished')) {
            if (response["delete"] === 'finished' && response.error === undefined) {
              $('.wpstg-clone[data-clone-id="' + clone + '"]').remove();
            }

            // No staging site message is also of type/class .wpstg-class but hidden
            // We have just excluded that from search when counting no of clones
            if ($('#wpstg-existing-clones .wpstg-clone').length < 1) {
              cache.get('#wpstg-existing-clones').find('h3').text('');
              cache.get('#wpstg-no-staging-site-results').show();
            }
            cache.get('.wpstg-loader').hide();
            // finish modal popup
            if (wpstg.i18n.wpstg_delete_clone !== null && response["delete"] === 'finished') {
              var params = {
                'status': 'finished'
              };
              that.wpstgProcessModal.openProcessModal({
                'action': 'wpstg_delete_clone',
                'cloneID': clone
              }, params, false, false);
              WPStagingCommon.getSwalModal(true, {
                confirmButton: 'wpstg--btn--confirm wpstg-green-button wpstg-button wpstg-link-btn wpstg-100-width'
              }).fire({
                'icon': 'success',
                'html': '<div style="display:block"><h2 style="color: #565656;">' + wpstg.i18n.wpstg_delete_clone.title + '</h2></div>',
                'confirmButtonText': 'Close',
                'showCancelButton': false,
                'showConfirmButton': true
              }).then(function (result) {
                if (result.value) {
                  WPStagingCommon.closeSwalModal();
                  loadingBar('hidden');
                }
              });
            }
            return;
          }
        }
        // continue
        if (true !== response) {
          deleteClone(clone, deleteDir, excludedTables);
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
          cache.get('.wpstg-loader').hide();
          // Load overview
          loadOverview();
          return;
        }
        if (true !== response) {
          // continue
          cancelCloning();
          return;
        }

        // Load overview
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
        }

        // Load overview
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
        }

        // Load overview
        loadOverview();
      });
    };

    /**
     * Append the log to the logging window
     * @param string log
     * @return void
     */
    var getLogs = function getLogs(log) {
      if (Array.isArray(log)) {
        log.forEach(function (logMessage) {
          if (logMessage !== null) {
            if (!that.messages.find(function (_ref) {
              var message = _ref.message;
              return message === logMessage.message;
            })) {
              that.messages.push(logMessage);
              var $logsContainer = $('.wpstg--modal--process--logs');
              var msgClass = "wpstg--modal--process--msg--" + (logMessage.type.toLowerCase() || 'info');
              $logsContainer.append("<p class=\"" + msgClass + "\">[" + (logMessage.type || 'info') + "] - [" + logMessage.date + "] - " + logMessage.message + "</p>");
            }
          }
        });
      } else {
        if (!that.messages.find(function (_ref2) {
          var message = _ref2.message;
          return message === log.message;
        })) {
          that.messages.push(log);
          var $logsContainer = $('.wpstg--modal--process--logs');
          var msgClass = "wpstg--modal--process--msg--" + (log.type.toLowerCase() || 'info');
          $logsContainer.append("<p class=\"" + msgClass + "\">[" + (log.type || 'info') + "] - [" + log.date + "] - " + log.message + "</p>");
        }
      }
      document.querySelectorAll('.wpstg--modal--process--logs').forEach(function (element) {
        element.scrollTop = element.scrollHeight;
      });
    };

    /**
     * Check disk space
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
          }

          // Show required disk space
          cache.get('#wpstg-clone-id-error').html('<strong>Estimated necessary disk space: ' + response.requiredSpace + '</strong>' + (response.errorMessage !== null ? '<br>' + response.errorMessage : '') + '<br> <span style="color:#444;">Before you proceed ensure your account has enough free disk space to hold the entire instance of the production site. You can check the available space from your hosting account (e.g. cPanel).</span>').show();
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
      resetErrors();

      // Register function for checking disk space
      checkDiskSpace();
      if ('wpstg_cloning' !== that.data.action && 'wpstg_update' !== that.data.action && 'wpstg_reset' !== that.data.action) {
        return;
      }
      that.isCancelled = false;

      // Start the process
      start();

      // Functions
      // Start
      function start() {
        cache.get('.wpstg-loader').show();
        cache.get('#wpstg-cancel-cloning').text('Cancel');
        cache.get('#wpstg-resume-cloning').hide();
        cache.get('#wpstg-error-details').hide();

        // Clone Database
        setTimeout(function () {
          // cloneDatabase();
          window.addEventListener('beforeunload', WPStaging$1.warnIfClosingDuringProcess);
          processing();
        }, wpstg.delayReq);
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
        }

        // Show logging window

        WPStaging$1.requestType = 'wpstg_cloning';
        WPStaging$1.ajax({
          action: 'wpstg_processing',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
          excludedTables: getExcludedTables(),
          excludedDirectories: encodeURIComponent(excludedDirectories),
          extraDirectories: encodeURIComponent(extraDirectories)
        }, function (response) {
          showAjaxFatalError(response);

          // Add Log messages
          if ('undefined' !== typeof response.last_msg && response.last_msg) {
            getLogs(response.last_msg);
          }
          // Continue processing
          if (false === response.status) {
            progressBar(response);
            setTimeout(function () {
              processing();
            }, wpstg.delayReq);
          } else if (true === response.status && 'finished' !== response.status) {
            cache.get('#wpstg-error-details').hide();
            cache.get('#wpstg-error-wrapper').hide();
            progressBar(response);
            processing();
          } else if ('finished' === response.status || 'undefined' !== typeof response.job_done && response.job_done) {
            window.removeEventListener('beforeunload', WPStaging$1.warnIfClosingDuringProcess);
            WPStaging$1.requestType = '';
            finish(response);
          }
        }, 'json', false);
      };

      // Finish
      function finish(response) {
        if (true === that.getLogs) {
          getLogs();
        }
        progressBar(response);

        // Add Log
        if ('undefined' !== typeof response.last_msg) {
          getLogs(response.last_msg);
        }
        if (wpstg.i18n[that.data.action] !== null) {
          that.wpstgProcessModal.openSuccessModal(wpstg.i18n[that.data.action], true, response.url);
        }
      }

      /**
       * Add percentage progress bar
       * @param object response
       * @return {Boolean}
       */
      var progressBar = function progressBar(response, restart) {
        if (response !== null) {
          that.wpstgProcessModal.openProcessModal(that.data, response, true);
        }
      };
    };
    that.switchStep = function (step) {
      cache.get('.wpstg-current-step').removeClass('wpstg-current-step');
      cache.get('.wpstg-step' + step).addClass('wpstg-current-step');
    };
    that.checkUserDbPermissions = function checkUserDbPermissions(operationType) {
      return new Promise(function (resolve) {
        WPStaging$1.ajax({
          action: 'wpstg_check_user_permissions',
          accessToken: wpstg.accessToken,
          nonce: wpstg.nonce,
          type: operationType
        }, function (response) {
          if (response.success) {
            resolve(true);
          } else {
            WPStagingCommon.getSwalModal(true, {
              container: 'wpstg-swal-push-container'
            }).fire({
              title: '',
              icon: 'warning',
              html: response.data.message,
              width: '750px',
              focusConfirm: false,
              confirmButtonText: 'Proceed',
              showCancelButton: true
            }).then(function (result) {
              if (result.isConfirmed) {
                resolve(true);
              }
            });
            $('#wpstg-db-permission-show-full-message').click(function () {
              $('#wpstg-permission-info-output').html($('#wpstg-permission-info-data').html());
            });
          }
        }, 'json', false);
      });
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
      new WpstgProcessModal();
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
    WPStaging$1.init();
    // This is necessary to make WPStaging var accessible in WP Staging PRO js script
    window.WPStaging = WPStaging$1;
  });

  /**
   * Report Issue modal
   */
  jQuery(document).ready(function ($) {
    $('body').on('click', '.wpstg-report-issue-button', function (e) {
      var contactUsModal = document.querySelector('#wpstg-contact-us-modal');
      if (contactUsModal != null && typeof contactUsModal !== 'undefined') {
        show('#wpstg-contact-us-modal');
        new WpstgContactUs();
      }
      $('.wpstg-report-issue-form').toggleClass('wpstg-report-show');
      e.preventDefault();
    });
    $('body').on('click', '.wpstg-backups-report-issue-button', function (e) {
      $('.wpstg-report-issue-form').toggleClass('wpstg-report-show');
      e.preventDefault();
    });
    $('body').on('click', '#wpstg-report-cancel', function (e) {
      $('.wpstg-report-issue-form').removeClass('wpstg-report-show');
      e.preventDefault();
    });
    $('body').on('click', '#wpstg-report-submit', function (e) {
      var self = $(this);
      sendIssueReport(self, 'false');
      e.preventDefault();
    });
    $('body').on('click', '.wpstg--modal--process--logs--tail', function (e) {
      showProcessLogs();
      var logBtn = $(this);
      e.preventDefault();
      var container = WPStagingCommon.getSwalContainer();
      var $logs = $(container).find('.wpstg--modal--process--logs');
      $logs.toggle();
      if ($logs.is(':visible')) {
        logBtn.text(wpstg.i18n.hideLogs);
        container.childNodes[0].style.width = '97%';
        container.style['z-index'] = 9999;
      } else {
        logBtn.text(wpstg.i18n.showLogs);
        container.childNodes[0].style.width = '600px';
      }
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
      var email = $('.wpstg--tab--header .wpstg-report-email').val();
      var hosting_provider = $('.wpstg--tab--header .wpstg-report-hosting-provider').val();
      var message = $('.wpstg--tab--header .wpstg-report-description').val();
      var syslog = $('.wpstg--tab--header .wpstg-report-syslog').is(':checked');
      var terms = $('.wpstg--tab--header .wpstg-report-terms').is(':checked');
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
              // TODO: remove default custom classes
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
          $('.wpstg-report-issue-form').prepend(errorMessage);
        } else {
          var successMessage = $('<div />').addClass('wpstg-message wpstg-success-message');
          successMessage.append('<p>Thanks for submitting your request! You should receive an auto reply mail with your ticket ID immediately for confirmation!<br><br>If you do not get that mail please contact us directly at <strong>support@wp-staging.com</strong></p>');
          $('.wpstg--tab--header .wpstg-report-issue-form').html(successMessage);
          if (document.getElementById('wpstg-modal-close') === null || document.getElementById('wpstg-modal-close') === undefined) {
            $('.wpstg--tab--header .wpstg-success-message').append('<div class="wpstg-mt-10px wpstg-float-right"><a id="wpstg-success-button" href="#" class="wpstg--red"><span class="wpstg-ml-8px">Close</span></a></div>');
          }

          // Hide message
          setTimeout(function () {
            $('.wpstg--tab--header .wpstg-report-issue-form').removeClass('wpstg-report-active');
          }, 2000);
        }
      });
    }

    // Open/close actions drop down menu
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
    });

    // Close action drop down menu if clicked anywhere outside
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

    // "Event info" for backup errors
    window.addEventListener('finishedProcessWithError', function (customEvent) {
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
          'action': 'wpstg_job_error',
          'accessToken': wpstg.accessToken,
          'nonce': wpstg.nonce,
          'error_message': customEvent.detail.error,
          'job_id': WPStagingCommon.getJobId()
        }
      });
    });

    /**
     * Display logs in a modal.
     * and avoid mixing of different processes logs
     * @return {void}
     */
    function showProcessLogs() {
      if (window.WPStaging.messages.length !== 0) {
        var container = WPStagingCommon.getSwalContainer();
        var logsContainer = container.querySelector('.wpstg--modal--process--logs');
        logsContainer.innerHTML = '';
        window.WPStaging.messages.forEach(function (message) {
          var msgClass = "wpstg--modal--process--msg--" + message.type.toLowerCase();
          var pElement = document.createElement('p');
          pElement.className = msgClass;
          pElement.textContent = "[" + message.type + "] - [" + message.date + "] - " + message.message;
          logsContainer.appendChild(pElement);
        });
      }
    }
    document.addEventListener('click', function (event) {
      if (event.target.id !== 'wpstg-login-link') {
        return;
      }
      event.preventDefault();
      window.open('/wp-login.php', '_blank');
      if (window.WPStaging && window.WPStaging.wpstgProcessModal) {
        window.WPStaging.wpstgProcessModal.stop();
        window.WPStaging.loadOverview();
      }
    });
  });

})();
//# sourceMappingURL=wpstg-admin.js.map
