# Liquid Allowlist — Anti-Hallucination Gate

This file lists verified Shopify Liquid objects, filters, and tags. Every name used
in theme code must appear here. If a name is not listed, verify at
`shopify.dev/docs/api/liquid` before using it. **Never invent Liquid names.**

## Table of Contents
1. [Global Objects](#global-objects)
2. [Verified Filters](#verified-filters)
3. [Verified Tags](#verified-tags)
4. [Names That Look Real But Are NOT Verified](#names-that-look-real-but-are-not-verified)

---

## Global Objects

These objects are available in layouts, templates, sections, and snippets.

### Store & Request
| Object | Description |
|---|---|
| `shop` | Current shop (name, url, currency, metafields, etc.) |
| `request` | Current HTTP request (locale, host, path, etc.) |
| `settings` | Global theme settings from settings_schema.json |
| `content_for_header` | Shopify-injected `<head>` scripts (mandatory in layout) |
| `content_for_layout` | Template output render point (mandatory in layout) |
| `content_for_index` | Homepage sections (used in `index.liquid` template) |
| `page_title` | Formatted title for the current page |
| `page_description` | Meta description |
| `canonical_url` | Canonical URL for SEO |
| `template` | Current template object (template.name, template.suffix, template.directory) |

### Commerce Objects
| Object | Description |
|---|---|
| `product` | Current product (available in product templates/sections) |
| `product.variants` | Array of product variants |
| `product.selected_or_first_available_variant` | First available variant (commonly used) |
| `product.images` | Array of product images |
| `product.media` | Array of product media |
| `product.metafields` | Product metafields |
| `collection` | Current collection (available in collection templates/sections) |
| `collection.products` | Products in collection |
| `collections` | All store collections |
| `cart` | Current cart object |
| `cart.items` | Array of cart line items |
| `cart.item_count` | Number of items in cart |
| `cart.total_price` | Cart total in cents (use `money` filter to format) |
| `customer` | Currently logged in customer (nil if not logged in) |
| `article` | Current article (in article templates) |
| `blog` | Current blog |
| `blogs` | All blogs |
| `page` | Current page object |
| `pages` | All pages |
| `gift_card` | Gift card object (in gift_card.liquid template) |
| `search` | Search results object |
| `linklists` | Navigation link lists |
| `localization` | Locale and currency options |
| `routes` | Store route URLs (cart_url, account_url, etc.) |
| `form` | Form error/success state (inside {% form %} tag) |
| `metaobject` | Metaobject entry (in metaobject templates) |
| `powered_by_link` | "Powered by Shopify" link HTML |
| `recommendations` | Product recommendations (in sections using Recommendations API) |

### Section & Block Objects (section-scoped)
| Object | Description |
|---|---|
| `section` | Current section (id, settings, blocks) |
| `section.settings` | Section settings defined in schema |
| `section.blocks` | Array of blocks configured for this section |
| `section.id` | Unique section identifier |
| `block` | Current block (within a for loop over section.blocks) |
| `block.id` | Block ID — dynamic, do NOT use in logic |
| `block.type` | Block type string — use this for logic branching |
| `block.settings` | Block settings |
| `block.shopify_attributes` | Required attribute for theme editor click-to-edit |

---

## Verified Filters

### Money Filters
| Filter | Usage | Notes |
|---|---|---|
| `money` | `{{ product.price \| money }}` | Formats based on store's currency setting |
| `money_with_currency` | `{{ product.price \| money_with_currency }}` | Includes currency symbol/code |
| `money_without_currency` | `{{ cart.total_price \| money_without_currency }}` | Number formatted without symbol |
| `money_without_trailing_zeros` | `{{ price \| money_without_trailing_zeros }}` | Omits cents if `.00` |

**⚠ `money_amount` is NOT a verified filter. Do not use it.**

### Asset & URL Filters
| Filter | Usage |
|---|---|
| `asset_url` | `{{ 'theme.css' \| asset_url }}` — CDN URL with cache-busting `?v=` |
| `asset_img_url` | `{{ 'icon.png' \| asset_img_url: '50x' }}` — sized image from assets/ |
| `image_url` | `{{ product.featured_image \| image_url: width: 800 }}` — Shopify CDN image |
| `file_url` | URL for files uploaded via Shopify admin (NOT asset_url) |
| `stylesheet_tag` | `{{ 'theme.css' \| asset_url \| stylesheet_tag }}` — `<link>` tag |
| `script_tag` | `{{ 'theme.js' \| asset_url \| script_tag }}` — `<script>` tag |
| `preload_tag` | `{{ 'critical.css' \| asset_url \| preload_tag: as: 'style' }}` |
| `image_tag` | `{{ image \| image_url: width: 800 \| image_tag }}` — responsive `<img>` |
| `url_for_type` | `{{ 'product' \| url_for_type }}` — collection-by-type URL |

### String Filters
| Filter | Usage |
|---|---|
| `handleize` | `{{ 'My Product' \| handleize }}` → `"my-product"` |
| `upcase` | `{{ string \| upcase }}` |
| `downcase` | `{{ string \| downcase }}` |
| `capitalize` | `{{ string \| capitalize }}` |
| `truncate` | `{{ string \| truncate: 100 }}` |
| `strip_html` | `{{ product.description \| strip_html }}` |
| `escape` | HTML-escape a string |
| `newline_to_br` | Convert `\n` to `<br>` |
| `split` | `{{ string \| split: ',' }}` |
| `join` | `{{ array \| join: ', ' }}` |
| `append` | `{{ string \| append: ' suffix' }}` |
| `prepend` | `{{ string \| prepend: 'prefix ' }}` |
| `replace` | `{{ string \| replace: 'old', 'new' }}` |
| `remove` | `{{ string \| remove: 'word' }}` |
| `size` | `{{ string \| size }}` or `{{ array \| size }}` |
| `t` | Translation: `{{ 'general.search' \| t }}` |

### Array Filters
| Filter | Usage |
|---|---|
| `first` | `{{ array \| first }}` |
| `last` | `{{ array \| last }}` |
| `where` | `{{ collection.products \| where: 'available', true }}` |
| `map` | `{{ products \| map: 'title' }}` |
| `sort` | `{{ array \| sort: 'price' }}` |
| `sort_natural` | Case-insensitive sort |
| `uniq` | Remove duplicates |
| `reverse` | Reverse an array |
| `compact` | Remove nil values |
| `concat` | Merge arrays |
| `sum` | `{{ cart.items \| sum: 'quantity' }}` |

### Math & Logic Filters
| Filter | Usage |
|---|---|
| `plus` | `{{ 3 \| plus: 2 }}` |
| `minus` | `{{ price \| minus: discount }}` |
| `times` | `{{ price \| times: 1.1 }}` |
| `divided_by` | `{{ total \| divided_by: 100 }}` |
| `modulo` | `{{ number \| modulo: 2 }}` |
| `abs` | Absolute value |
| `ceil` | Round up |
| `floor` | Round down |
| `round` | `{{ 3.7 \| round }}` |
| `at_least` | `{{ value \| at_least: 0 }}` (minimum) |
| `at_most` | `{{ value \| at_most: 100 }}` (maximum) |
| `default` | `{{ value \| default: 'fallback' }}` |

### Image & Media Filters
| Filter | Usage |
|---|---|
| `image_url` | `{{ image \| image_url: width: 400 }}` |
| `image_tag` | `{{ image \| image_url: width: 400 \| image_tag: loading: 'lazy' }}` |
| `placeholder_svg_tag` | `{{ 'product-1' \| placeholder_svg_tag }}` |

### Font Filters (for font_picker settings)
| Filter | Usage |
|---|---|
| `font_face` | `{{ settings.body_font \| font_face }}` — `@font-face` CSS |
| `font_url` | CDN URL for the font file |
| `font_modify` | `{{ settings.body_font \| font_modify: 'weight', 'bold' }}` |

---

## Verified Tags

### Iteration & Control
```liquid
{% for product in collection.products limit: 12 offset: 0 %}
{% endfor %}

{% if condition %}{% elsif condition %}{% else %}{% endif %}

{% unless condition %}{% endunless %}

{% case block.type %}{% when 'heading' %}{% when 'image' %}{% else %}{% endcase %}
```

### Rendering
```liquid
{% render 'snippet-name' %}
{% render 'snippet-name', product: product, show_vendor: true %}

{% section 'section-name' %}        ← static render (avoid; shares single settings instance)
{% sections 'group-name' %}         ← section group render (use in layout)

{% content_for 'blocks' %}          ← theme/app block rendering (supports nesting)
{% content_for 'block', type: 'my-block', id: 'static-id' %}  ← static block
```

### Layout
```liquid
{% layout 'checkout' %}   ← use alternate layout
{% layout none %}         ← opt out of layout entirely (NOT "layout false")
```

### Asset Bundling (section-scoped only — one per file)
```liquid
{% javascript %}
  // Section-scoped JS — bundled into scripts.js
  // No Liquid allowed inside this tag
{% endjavascript %}

{% stylesheet %}
  /* Section-scoped CSS — bundled into styles.css via subsetting */
  /* No Liquid allowed inside this tag */
{% endstylesheet %}
```

### Commerce Tags
```liquid
{% form 'product', product %}{% endform %}
{% form 'cart', cart %}{% endform %}
{% form 'customer' %}{% endform %}
{% form 'create_customer' %}{% endform %}
{% form 'recover_customer_password' %}{% endform %}
{% form 'login' %}{% endform %}

{% paginate collection.products by 24 %}
  {% for product in collection.products %}{% endfor %}
  {{ paginate | default_pagination }}
{% endpaginate %}
```

### Other Tags
```liquid
{% schema %}{ ... }{% endschema %}   ← section schema (one per file, no Liquid inside)
{% style %}/* CSS */{% endstyle %}   ← inline CSS (NOT subsetted; avoid for section CSS)
{% comment %}{% endcomment %}
{% raw %}{{ literal }}{% endraw %}
{% liquid %}{% endliquid %}          ← multi-line tag block
{% assign name = value %}
{% capture var %}content{% endcapture %}
{% increment counter %}
{% decrement counter %}
{% break %} / {% continue %}
```

---

## Names That Look Real But Are NOT Verified

Do not use these without first verifying on shopify.dev:

| Name | Status |
|---|---|
| `money_amount` | Not found in official docs — use `money` or `money_with_currency` |
| `product.selected_variant` | Unverified property name — use `product.selected_or_first_available_variant` |
| `{% layout false %}` | Invalid — correct syntax is `{% layout none %}` |
| `Shopify.actions.Cart.update` | Spelling unconfirmed — verify at shopify.dev changelog |
| `shopify:cart:add` | Not in verified list — verify full events list at shopify.dev |
| Any 8-level nesting guarantee | Nesting depth limit not confirmed in official docs |
| `max_blocks: 50` as system default | Platform maximum is 50; `max_blocks` in schema overrides to a lower value |

If you encounter any Liquid object, filter, or tag not listed here, verify it at
`shopify.dev/docs/api/liquid` and add it to this file before using it in code.
