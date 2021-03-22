/**
 * WP Staging basic jQuery replacement
 */

/**
 * Shortcut for document.querySelector() or jQuery's $()
 * Return single element only
 */
export function qs(selector) {
  return document.querySelector(selector);
}
/**
 * alternative of jQuery - $(parent).on(event, selector, handler)
 */

export function addEvent(parent, evt, selector, handler) {
  for (var _len = arguments.length, args = new Array(_len > 4 ? _len - 4 : 0), _key = 4; _key < _len; _key++) {
    args[_key - 4] = arguments[_key];
  }

  parent.addEventListener(evt, function (event) {
    if (event.target.matches(selector + ', ' + selector + ' *')) {
      handler.apply(event.target.closest(selector), args);
    }
  }, false);
}