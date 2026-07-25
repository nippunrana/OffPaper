# WordPress JavaScript Coding Standards

**Load this file before writing any JavaScript** — `view.js` Interactivity API stores,
`index.js` non-reactive scripts (animations, observers), WooCommerce checkout JS, or any
`@wordpress/abilities` client-side registration.

**Scope: modern block JS only.** This skill targets WP 7.0 ES-module JS: the Interactivity API,
Script Modules (`wp_register_script_module()`), and ES2015+ syntax. The official WordPress JS
Coding Standards handbook also covers legacy delivery mechanics — jQuery
`( function( $ ) {…} )( jQuery )` wrappers, Underscore type-checks (`_.isArray`, `_.each`),
`var` single-var patterns, and JSHint/Grunt linting workflow — that **do not apply here**. WP 7.0
moved JS linting to Espree (see `SKILL.md §7.4`). Ignore those legacy sections; use the
standards below.

Source: WordPress JavaScript Coding Standards (official handbook), evergreen formatting and
naming conventions subset.

---

## Table of Contents

1. [Indentation](#1-indentation)
2. [Strings](#2-strings)
3. [Spacing — when in doubt, space it out](#3-spacing--when-in-doubt-space-it-out)
4. [Semicolons](#4-semicolons)
5. [Equality](#5-equality)
6. [Control flow — braces everywhere](#6-control-flow--braces-everywhere)
7. [Naming conventions](#7-naming-conventions)
8. [Variable declarations](#8-variable-declarations)
9. [Hygiene](#9-hygiene)
10. [Comments](#10-comments)

---

## 1. Indentation

Use **tabs** for indentation — never spaces. This matches the CSS and PHP conventions used
elsewhere in the skill, and lets each developer adjust their tab-width preference without
touching the file.

```js
// ✅ correct
store( 'example/hero', {
	actions: {
		toggleSave: () => {
			context.isSaved = ! context.isSaved;
		},
	},
} );

// ❌ wrong — spaces
store( 'example/hero', {
  actions: {
    toggleSave: () => {
      context.isSaved = ! context.isSaved;
    },
  },
} );
```

---

## 2. Strings

Use **single quotes** for string literals. Reserve double quotes for strings containing a
literal single quote (to avoid backslash-escaping).

```js
// ✅ correct
const label = 'Save for Later';
const html = "<span class='active'>";

// ❌ wrong
const label = "Save for Later";
```

---

## 3. Spacing — when in doubt, space it out

WordPress JS uses liberal spacing to make expressions easy to scan. The guiding principle:
**if it groups things, add spaces inside it.**

### 3.1 Function calls and declarations

Space inside the opening and closing parens:

```js
// ✅ correct
store( 'example/hero', { … } );
registerAbility( { id: 'example/do-thing', … } );
document.addEventListener( 'DOMContentLoaded', function () { … } );

// ❌ wrong — no spaces inside parens
store('example/hero', { … });
document.addEventListener('DOMContentLoaded', function() { … });
```

For arrow functions, wrap the parameter list in parens even for a single parameter:

```js
// ✅ correct
document.querySelectorAll( '.card' ).forEach( ( card ) => {
	card.addEventListener( 'mouseenter', () => {
		card.style.transform = 'translateY(-4px)';
	} );
} );

// ❌ wrong — no parens around solo param, no spaces inside
document.querySelectorAll('.card').forEach(card => {
	card.addEventListener('mouseenter', () => card.style.transform = 'translateY(-4px)');
});
```

### 3.2 Arrays and objects

Spaces inside square brackets and curly braces:

```js
// ✅ correct
const tags = [ 'hero', 'cta', 'faq' ];
const opts = { threshold: 0.15 };

// ❌ wrong
const tags = ['hero', 'cta', 'faq'];
const opts = {threshold: 0.15};
```

### 3.3 Negation — space after `!`

A space between `!` and its operand keeps the negation visible and avoids confusion with `!=`:

```js
// ✅ correct
if ( ! wrapper ) return;
if ( ! entry.isIntersecting ) return;

// ❌ wrong
if (!wrapper) return;
```

### 3.4 Ternary

Spaces on both sides of `?` and `:`:

```js
// ✅ correct
const label = isSaved ? 'Saved!' : 'Save for Later';

// ❌ wrong
const label = isSaved?'Saved!':'Save for Later';
```

### 3.5 Unary operators — no space

Unary operators (`++`, `--`, unary `-`/`+`) attach directly to their operand:

```js
// ✅ correct
i++;
--count;
const neg = -value;
```

---

## 4. Semicolons

**Always use semicolons.** JavaScript's Automatic Semicolon Insertion (ASI) has edge cases —
e.g. a line beginning with `(` or `[` can silently merge with the previous line. Make statement
boundaries explicit.

```js
// ✅ correct
const wrapper = document.querySelector( '.is-style-example-features' );
if ( ! wrapper ) return;

// ❌ wrong — relies on ASI
const wrapper = document.querySelector( '.is-style-example-features' )
if ( ! wrapper ) return
```

---

## 5. Equality

Always use strict equality `===` and `!==`. The loose operators (`==`, `!=`) perform type
coercion — `0 == ''` is `true` — which is almost never the intent.

```js
// ✅ correct
if ( entry.target === null ) return;
if ( status !== 'done' ) return;

// ❌ wrong
if ( entry.target == null ) return;
```

---

## 6. Control flow — braces everywhere

Use curly braces on all `if`/`else`/`for`/`while` blocks, even single-statement bodies.
This prevents bugs when a second statement is added later.

```js
// ✅ correct
if ( ! wrapper ) {
	return;
}

entries.forEach( ( entry ) => {
	if ( entry.isIntersecting ) {
		entry.target.classList.add( 'in-view' );
		observer.unobserve( entry.target );
	}
} );

// ❌ wrong — brace-free body
if ( ! wrapper )
	return;
```

Guard/early-return patterns on a single line are acceptable when the body is a bare `return`
or `return;`:

```js
// ✅ acceptable guard clause
if ( ! wrapper ) return;
if ( window.frameElement ) return;
```

---

## 7. Naming conventions

| Identifier type | Style | Example |
|---|---|---|
| Variables and functions | `camelCase` | `const isSaved`, `function toggleSave()` |
| Classes | `UpperCamelCase` | `class ProductCard` |
| `@wordpress/element` components | `UpperCamelCase` | `function SaveButton( { label } )` |
| Constants (module-level, never reassigned) | `SCREAMING_SNAKE_CASE` | `const MAX_RETRIES = 3;` |
| Interactivity API store namespaces | kebab-case string | `store( 'example/hero', … )` |

---

## 8. Variable declarations

- Use `const` by default — declare at the point of first use, not at the top of the scope.
- Use `let` only when the variable will be reassigned.
- Never use `var` — it has function scope and hoisting behaviour that causes bugs.

```js
// ✅ correct
const wrapper = document.querySelector( '.is-style-example-features' );
if ( ! wrapper ) return;

let count = 0;
count++;

// ❌ wrong — var, declared ahead of use
var wrapper, count;
wrapper = document.querySelector( '.is-style-example-features' );
```

---

## 9. Hygiene

- **No trailing whitespace** on any line.
- **File ends in a single newline** — one `\n` after the last line, nothing more.

Both are standard editor settings (`trimTrailingWhitespace`, `insertFinalNewline` in
`.editorconfig`). The WP repo enforces them in CI; your editor should enforce them locally.

---

## 10. Comments

- Use `//` for inline and single-line comments.
- Use `/** … */` JSDoc for functions and modules that are part of a public API.
- Leave a space between `//` and the comment text.

```js
// ✅ correct — space after //
const observer = new IntersectionObserver( ( entries ) => {
	// Skip elements already animated.
	entries.forEach( ( entry ) => {
		if ( ! entry.isIntersecting ) return;
		entry.target.classList.add( 'in-view' );
		observer.unobserve( entry.target );
	} );
}, { threshold: 0.15 } );

/**
 * Toggles the saved state for the current item.
 *
 * @param {Object} context - The Interactivity API context object.
 */
function toggleSave( context ) {
	context.isSaved = ! context.isSaved;
}
```
