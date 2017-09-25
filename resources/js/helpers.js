/**
 * ## Debounce
 *
 * A function, that, as long as it continues to be invoked, will not
 * be triggered. The function will be called after it stops being called for
 * N milliseconds.
 *
 * If `immediate` is passed, trigger the function on the leading edge,
 * instead of the trailing.
 *
 * ```jsx
 *
 * // ...
 *
 * <input onInput={this.handleInput}>
 *
 * // ...
 *
 * handleInput = debounce(e => { /* ... *\/ });
 *
 * ```
 *
 * @param {function} func - The function to debounce
 * @param {number=} wait - How long, in milliseconds, to delay between attempts
 * @param {boolean=} immediate - Fire on the leading edge
 * @returns {Function}
 */
export function debounce (func, wait = 300, immediate = false) {
	let timeout;
	
	if (wait === 0) {
		return function () {
			func.apply(this, arguments);
		};
	}
	
	return function () {
		const context = this
			, args = arguments;
		
		if (args[0].constructor.name === "SyntheticEvent")
			args[0].persist();
		
		const later = function() {
			timeout = null;
			if (!immediate) func.apply(context, args);
		};
		const callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) func.apply(context, args);
	};
}

/**
 * ## Create Element
 * Quick and easy DOM element creation
 *
 * @param {string=} tag - The element tag
 * @param {object=} attributes - The attributes to add, mapping the key as
 *     the attribute name, and the value as its value. If the value is a
 *     function, it will be added as an event.
 * @param {(Array|*)=} children - An array of children (can be a mixture of
 *     Nodes to append, or other values to be stringified and appended
 *     as text).
 * @return {Element} - The created element
 */
export function t (tag = "div", attributes = {}, children = []) {
	const elem = document.createElement(tag);
	
	for (let [key, value] of Object.entries(attributes)) {
		if (!value) continue;
		
		if (typeof value === typeof (() => {})) {
			elem.addEventListener(key, value);
			continue;
		}
		
		if (key === "style")
			value = value.replace(/[\t\r\n]/g, " ").trim();
		
		elem.setAttribute(key, value);
	}
	
	if (!Array.isArray(children))
		children = [children];
	
	children.map(child => {
		if (!child) return;
		
		try {
			elem.appendChild(child);
		} catch (_) {
			elem.appendChild(document.createTextNode(child));
		}
	});
	
	return elem;
}


export function inViewport (el) {
	const rect = el.getBoundingClientRect();
	
	return (
		rect.bottom > 0 &&
		rect.right > 0 &&
		rect.left < (window.innerWidth || document.documentElement.clientWidth) &&
		rect.top < (window.innerHeight || document.documentElement.clientHeight)
	);
}
