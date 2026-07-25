# Theme Editor Compatibility Reference

## Table of Contents
1. [How the Theme Editor Works](#how-the-theme-editor-works)
2. [JavaScript Events — Verified List](#javascript-events--verified-list)
3. [Shopify.designMode](#shopifydesignmode)
4. [Section Re-initialization Pattern](#section-re-initialization-pattern)
5. [Block Selection Pattern](#block-selection-pattern)
6. [Visibility Requirement](#visibility-requirement)
7. [block.shopify_attributes](#blockshopifyattributes)
8. [Color Palette in the Editor](#color-palette-in-the-editor)
9. [Editor Compatibility Checklist](#editor-compatibility-checklist)

---

## How the Theme Editor Works

When a merchant changes a section setting in the theme editor:
1. Shopify re-renders the section HTML and injects the new DOM.
2. **`DOMContentLoaded` does NOT fire again** — only the section's HTML changes.
3. The theme editor emits JavaScript events that theme code must listen for.
4. Scripts bound only to `DOMContentLoaded` will break (sliders freeze, accordions stop, etc.).

This is the most common theme editor compatibility bug. Every interactive component
must re-initialize on the appropriate editor events.

---

## JavaScript Events — Verified List

These events are dispatched on the section's DOM element when the theme editor
triggers corresponding actions. All spellings below are verified from shopify.dev.

| Event | Triggered when | Target |
|---|---|---|
| `shopify:section:load` | Section is added or updated in the editor | Section DOM element |
| `shopify:section:unload` | Section is removed from the editor | Section DOM element |
| `shopify:section:select` | Section is clicked/selected in sidebar | Section DOM element |
| `shopify:section:deselect` | Section is deselected in sidebar | Section DOM element |
| `shopify:section:reorder` | Sections are reordered on the page | Section DOM element |
| `shopify:block:select` | A block is clicked/selected in sidebar | Section DOM element |
| `shopify:block:deselect` | A block is deselected | Section DOM element |
| `shopify:inspector:activate` | Theme editor inspector (section picker) activates | Document |
| `shopify:inspector:deactivate` | Inspector deactivates | Document |

**Event detail payload:**

`shopify:block:select` and `shopify:block:deselect` pass:
```javascript
event.detail.blockId   // the block's unique ID string
event.detail.load      // true if this is the initial editor load
```

`shopify:section:load`, `shopify:section:select`, etc. pass:
```javascript
event.detail.sectionId  // the section's unique ID
event.detail.load       // true if this is the initial editor load
```

---

## Shopify.designMode

```javascript
if (Shopify.designMode) {
  // Code that runs only inside the theme editor (never on the live storefront)
}
```

- `Shopify.designMode === true` when executing inside the theme editor.
- `Shopify.designMode === undefined` on the live storefront.
- Use this to disable features that would be confusing during editing (e.g., auto-playing
  videos, scroll-triggered animations, countdown timers).

---

## Section Re-initialization Pattern

The canonical pattern for editor-compatible JavaScript. Any interactive component
(slider, tabs, accordion, video player, etc.) must implement this:

```javascript
(function() {
  'use strict';

  function initSlider(sectionEl) {
    // Initialize or reinitialize the slider for this section
    const slider = sectionEl.querySelector('[data-slider]');
    if (!slider) return;

    // Your initialization logic here
    // Make sure to DESTROY any existing instance before reinitializing
    if (slider._sliderInstance) {
      slider._sliderInstance.destroy();
    }
    slider._sliderInstance = new Slider(slider);
  }

  // Initial page load (DOMContentLoaded or document.readyState check)
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-section-type="slideshow"]').forEach(initSlider);
  });

  // Theme editor re-initialization
  document.addEventListener('shopify:section:load', function(event) {
    const section = document.getElementById('shopify-section-' + event.detail.sectionId);
    if (section && section.querySelector('[data-section-type="slideshow"]')) {
      initSlider(section);
    }
  });

  document.addEventListener('shopify:section:unload', function(event) {
    // Clean up: pause animations, remove event listeners, destroy instances
    const section = document.getElementById('shopify-section-' + event.detail.sectionId);
    if (section) {
      const slider = section.querySelector('[data-slider]');
      if (slider && slider._sliderInstance) {
        slider._sliderInstance.destroy();
      }
    }
  });
})();
```

**Using section-scoped JS (`{% javascript %}` tag):**
```liquid
{% javascript %}
  (function() {
    const section = document.getElementById('shopify-section-{{ section.id }}');
    if (!section) return;

    function init() {
      // Initialize component for THIS section
    }

    // Editor re-init
    section.addEventListener('shopify:section:load', init);

    // Initial load
    init();
  })();
{% endjavascript %}
```

---

## Block Selection Pattern

When a block is selected in the theme editor sidebar, the selected block/content
should scroll into view and become visible/active. This is the "visibility requirement."

**Common use cases:**
- Carousel/slider: scroll to the selected slide
- Tab component: activate the selected tab
- Accordion: expand the selected item

```javascript
document.addEventListener('shopify:block:select', function(event) {
  const blockId = event.detail.blockId;
  const block = document.querySelector('[data-block-id="' + blockId + '"]');
  if (!block) return;

  // Scroll into view
  block.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

  // Example: activate tab
  block.click();

  // Example: activate carousel slide (assuming a slides array)
  const slideIndex = Array.from(block.parentElement.children).indexOf(block);
  carousel.goToSlide(slideIndex);
});

document.addEventListener('shopify:block:deselect', function(event) {
  // Resume auto-play, deactivate highlights, etc.
});
```

**Connecting `block.id` to DOM elements:**
Use `{{ block.shopify_attributes }}` in Liquid — this includes the block's ID as a
data attribute that the editor uses for identification. Do NOT hard-code `block.id`
in Liquid logic (it changes), but it can be used in JS event handling since the
event provides the current ID.

---

## Visibility Requirement

When a section or block is selected in the theme editor sidebar:
- The selected element **must be visible** (not hidden, not paused, not off-screen).
- If a carousel has the selected slide at position 5 but is showing slide 1, the
  merchant sees a disconnect between what they're editing and what's visible.

**Required behaviors on `shopify:block:select`:**
- Carousel/slider → scroll to the selected slide, pause autoplay
- Accordion → expand the selected accordion item
- Tab panel → activate the tab corresponding to the selected block
- Video → pause autoplay (don't start playing in editor unless merchant clicks play)

**Required behaviors on `shopify:section:select`:**
- Ensure the section is scrolled into view (browser does this automatically for the
  first render, but may need manual handling after DOM changes)
- Pause any animations that auto-advance

---

## block.shopify_attributes

This attribute provides the hooks the theme editor's JavaScript API uses for
"click to edit" functionality. It must be placed on the **outermost HTML element**
of each block iteration.

```liquid
{% for block in section.blocks %}
  <div class="block block--{{ block.type }}" {{ block.shopify_attributes }}>
    {# Block content #}
  </div>
{% endfor %}
```

What `{{ block.shopify_attributes }}` outputs:
```html
<div class="block" data-shopify-editor-block='{"id":"abc123","type":"text"}'>
```

**If you forget this attribute:** The theme editor can't identify which DOM element
corresponds to which block. Clicking a block in the sidebar won't highlight the
correct element, and `shopify:block:select` events may not target correctly.

---

## Color Palette in the Editor

The `color_palette` setting creates a reusable color picker in the theme editor.
When a merchant updates a palette color, all settings referencing it via cross-setting
references update automatically.

**Merchant deletes a palette color:**
Shopify requires the merchant to choose a replacement. The deleted color's ID is
then stored as a reference to the replacement, preventing broken color references
in settings that used the deleted color.

---

## Editor Compatibility Checklist

- [ ] All interactive components (sliders, carousels, tabs, accordions) re-initialize on `shopify:section:load`
- [ ] Sliders/carousels scroll to selected slide on `shopify:block:select`
- [ ] Autoplay paused on `shopify:block:select`, resumed on `shopify:block:deselect`
- [ ] `{{ block.shopify_attributes }}` on outermost element of every block loop iteration
- [ ] Components guard against double-initialization (destroy before re-init)
- [ ] `shopify:section:unload` cleans up event listeners and destroys instances
- [ ] Auto-playing or animated features gated with `if (!Shopify.designMode)` or paused in editor
- [ ] Selected blocks scroll into view (visibility requirement)
- [ ] No logic branches on `block.id` literal values — always use `block.type`
