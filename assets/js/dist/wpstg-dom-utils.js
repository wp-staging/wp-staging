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
 * Shortcut for document.querySelector() or jQuery's $()
 * Return multiple elements
 */

export function qsAll(selector) {
  return document.querySelectorAll(selector);
}
/**
 * alternative of jQuery - $(parent).on(event, selector, handler)
 */

export function addEvent(parent, evt, selector, handler) {
  parent.addEventListener(evt, function (event) {
    if (event.target.matches(selector + ', ' + selector + ' *')) {
      handler(event.target.closest(selector));
    }
  }, false);
}