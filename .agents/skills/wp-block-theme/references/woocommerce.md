# WooCommerce Block Theme Development

This reference covers everything a block theme needs to integrate cleanly with WooCommerce: the block component registry, blockified template overrides, CSS architecture (BEM + container queries), the Cart/Checkout JS filter registry, conditional tags, the `wc_get_logger()` framework, and the core function/namespace map.

## 1. When to Load This File

Load this file **before writing any code** when the user asks to:

- Override the WooCommerce Shop, Single Product, Cart, Checkout, Order Confirmation, Search, or Coming Soon template.
- Compose, override, or customise any `woocommerce/*` block (Product Image, Add to Cart Button, Price, Rating, Sale Badge, Mini-Cart, Accordion, etc.).
- Style the Cart, Checkout, or Mini-Cart blocks.
- Extend Cart/Checkout JS via `registerCheckoutFilters()`.
- Use WooCommerce conditional tags (`is_shop()`, `is_product()`, `is_cart()`, etc.) in theme PHP.
- Call `wc_get_logger()` from theme or plugin code.
- Touch anything in the `Automattic\WooCommerce` namespace.

If the work is generic WP FSE with no WooCommerce surface, stay in the main `SKILL.md` and skip this file.

This file is the **rendering surface** (blocks, templates, CSS, conditional tags, logging). For deeper WooCommerce work, load the focused companion instead of guessing:

- **`woocommerce-checkout-fields.md`** — adding/modifying/validating custom checkout fields (`woocommerce_register_additional_checkout_field`, sanitize/validate, JSON-Schema conditional logic, meta-key prefixes, stale-data trap).
- **`woocommerce-checkout-lifecycle.md`** — client-side checkout JS: data stores, status machine, observers (`onCheckoutValidation` / `onPaymentSetup` / `onCheckoutSuccess`), SlotFill, native DOM events. (The static value filters in §6 below stay here; the *event* layer lives there.)
- **`woocommerce-performance.md`** — store caching/cookie exclusions, DB hygiene, GZIP, asset optimization, Core Web Vitals.
- **`woocommerce-standards.md`** — safe customization (child themes/hooks/snippets), PHP/JS naming, isolation mandates, Webpack dependency extraction, `is_ssl()` behind load balancers, QA.

## 2. Pre-Write Gates (WooCommerce-Specific)

These four gates apply **on top of** the four gates in `SKILL.md → How to approach a request → Pre-write gates`. All must pass before writing code.

- **Block name gate** — every `woocommerce/*` block name used in template markup must appear in §3 below. Common AI failure mode: inventing names like `woocommerce/cart-button`, `woocommerce/checkout-form`, `woocommerce/product-add-to-cart`. The real surface is composed of smaller blocks (e.g. Cart is a tree of nested blocks; the "Add to Cart Button" is `woocommerce/product-button`). When unsure, grep `wp-content/plugins/woocommerce/src/Blocks/` rather than guessing.
- **Ancestor gate** — product element blocks (price, image, button, rating, sale-badge, sku, stock, summary, title, etc.) only render when nested inside a declared Ancestor (e.g. `woocommerce/single-product`, `woocommerce/product-template`, `core/post-template`). Placing them at template root silently renders empty — no error, just blank output. Always check the Ancestor column in §3.
- **Internal privacy gate** — the HTML *inside* a WooCommerce block (class names on internal `<div>`s, the structure of `.wc-block-cart-item__row`, etc.) is private and changes between releases. CSS selectors targeting that internal DOM will break on update. Style via **block style variations** (`register_block_style()` — see main `SKILL.md`) or the **documented filter registry** (§6) only.
- **Conditional tag timing gate** — `is_shop()`, `is_product()`, `is_cart()`, etc. are only valid after the `posts_selection` action fires. The earliest reliable hook is `wp`. **Never call them at global scope in `functions.php`** — they return false-y noise and silently break logic. See §7 for the safe pattern.

## 3. Block Component Registry

### 3.1 Product Elements

These blocks must be nested inside one of their declared Ancestors. Category for all is `woocommerce-product-elements`.

| Block (Internal Name) | Ancestor(s) | Key Supports | Key Attributes |
|---|---|---|---|
| `woocommerce/product-button` (Add to Cart) | `woocommerce/all-products`, `woocommerce/single-product`, `core/post-template`, `woocommerce/product-template` | `align (full, wide)`, `color (background, text)`, `email`, `interactivity`, `spacing`, `typography`, `html: false` | `isDescendentOfQueryLoop`, `isDescendentOfSingleProductBlock`, `productId`, `textAlign`, `width` |
| `woocommerce/product-image` | `woocommerce/all-products`, `woocommerce/single-product`, `woocommerce/product-template`, `core/post-template` | `dimensions (aspectRatio)`, `email`, `interactivity (clientNavigation)`, `spacing`, `typography (fontSize)`, `html: false` | `aspectRatio`, `height`, `imageSizing`, `isDescendentOfQueryLoop`, `isDescendentOfSingleProductBlock`, `productId`, `saleBadgeAlign`, `scale`, `showProductLink`, `showSaleBadge`, `width` |
| `woocommerce/product-price` | `woocommerce/all-products`, `woocommerce/featured-product`, `woocommerce/single-product`, `woocommerce/product-template`, `core/post-template` | `color`, `email`, `interactivity`, `spacing`, `typography`, `html: false` | `isDescendentOfQueryLoop`, `isDescendentOfSingleProductBlock`, `isDescendentOfSingleProductTemplate`, `productId`, `textAlign` |
| `woocommerce/product-rating` | `woocommerce/all-products`, `woocommerce/single-product`, `woocommerce/product-template`, `core/post-template` | `color (text)`, `interactivity (clientNavigation)`, `spacing`, `typography (fontSize)` | `isDescendentOfQueryLoop`, `isDescendentOfSingleProductBlock`, `isDescendentOfSingleProductTemplate`, `productId`, `textAlign` |
| `woocommerce/product-rating-counter` | `woocommerce/single-product` | `color (link)`, `interactivity (clientNavigation)`, `spacing`, `typography`, `inserter: false` | `isDescendentOfQueryLoop`, `isDescendentOfSingleProductBlock`, `isDescendentOfSingleProductTemplate`, `productId`, `textAlign` |
| `woocommerce/product-rating-stars` | `woocommerce/single-product` | `color (text)`, `interactivity (clientNavigation)`, `spacing`, `typography`, `inserter: false` | `isDescendentOfQueryLoop`, `isDescendentOfSingleProductBlock`, `isDescendentOfSingleProductTemplate`, `productId`, `textAlign` |
| `woocommerce/product-average-rating` *(Beta)* | `woocommerce/single-product` | `color`, `interactivity (clientNavigation)`, `spacing`, `typography` | `textAlign` |
| `woocommerce/product-sale-badge` | `woocommerce/single-product`, `woocommerce/product-template`, `core/post-template`, `woocommerce/product-gallery` | `align`, `color (background, gradients, text)`, `email`, `interactivity (clientNavigation)`, `spacing (margin)`, `typography`, `html: false` | `isDescendentOfQueryLoop`, `isDescendentOfSingleProductBlock`, `isDescendentOfSingleProductTemplate`, `productId` |
| `woocommerce/product-sku` | `woocommerce/product-meta`, `woocommerce/all-products`, `woocommerce/single-product`, `woocommerce/product-template`, `core/post-template` | `color`, `interactivity (clientNavigation)`, `spacing`, `typography`, `html: false` | `isDescendantOfAllProducts`, `prefix`, `productId`, `showProductSelector`, `suffix` |
| `woocommerce/product-stock-indicator` | `woocommerce/all-products`, `woocommerce/single-product`, `woocommerce/product-template`, `core/post-template` | `color`, `interactivity`, `spacing`, `typography`, `html: false` | `isDescendantOfAllProducts` |
| `woocommerce/product-summary` | `woocommerce/all-products`, `woocommerce/featured-product`, `woocommerce/single-product`, `woocommerce/product-template`, `core/post-template` | `color (background, link, text)`, `interactivity (clientNavigation)`, `spacing`, `typography (fontSize, lineHeight, textAlign)` | `isDescendantOfAllProducts`, `isDescendentOfQueryLoop`, `isDescendentOfSingleProductBlock`, `isDescendentOfSingleProductTemplate`, `linkText`, `productId`, `showDescriptionIfEmpty`, `showLink`, `summaryLength` |
| `woocommerce/product-title` | `woocommerce/all-products` | `color (background, gradients, text)`, `spacing (margin)`, `typography (fontSize, lineHeight)`, `html: false` | `align`, `headingLevel`, `linkTarget`, `productId`, `showProductLink` |

### 3.2 Composite & Layout Blocks

| Block (Internal Name) | Category | Notes |
|---|---|---|
| `woocommerce/product-image-gallery` | `woocommerce-product-elements` | Supports `align`, `interactivity (clientNavigation)`, `multiple: false`. |
| `woocommerce/product-meta` | `woocommerce-product-elements` | Supports `align`, `interactivity (clientNavigation)`, `reusable: false`. Acts as an ancestor for `woocommerce/product-sku`. |
| `woocommerce/related-products` | `woocommerce` | Supports `align`, `interactivity (clientNavigation)`, `inserter: false`, `reusable: false`. |

### 3.3 Accordion

| Block | Parent | Supports | Attributes |
|---|---|---|---|
| `woocommerce/accordion-group` | — (top-level) | `align (full, wide)`, `background`, `color`, `interactivity`, `layout`, `shadow`, `spacing`, `html: false` | `allowedBlocks`, `autoclose`, `iconPosition` |
| `woocommerce/accordion-header` | `woocommerce/accordion-item` | `anchor`, `border`, `color`, `interactivity`, `layout`, `shadow`, `spacing`, `typography` | `icon`, `iconPosition`, `level`, `levelOptions`, `openByDefault`, `textAlignment`, `title` |

### 3.4 Mini-Cart Nesting Tree

Strict parent → child relationships. Inserting a block outside this tree silently renders empty:

```
woocommerce/mini-cart
└── woocommerce/mini-cart-contents
    ├── woocommerce/empty-mini-cart-contents-block
    └── woocommerce/filled-mini-cart-contents-block
        ├── woocommerce/mini-cart-items-block
        │   └── woocommerce/mini-cart-products-table-block
        └── woocommerce/mini-cart-footer-block
```

### 3.5 Order Confirmation Nesting Tree

```
woocommerce/order-confirmation-status
woocommerce/order-confirmation-summary
woocommerce/order-confirmation-totals-wrapper
└── woocommerce/order-confirmation-totals
woocommerce/order-confirmation-shipping-wrapper
woocommerce/order-confirmation-billing-wrapper
```

The wrappers exist to carry layout/spacing; the inner blocks own the data binding. Override at the wrapper level for layout work, the inner block level for data work.

## 4. Blockified Template System

WooCommerce ships block-native HTML templates that themes can override using the same mechanism as core WordPress (`templates/{name}.html` in the theme directory wins).

### 4.1 Overridable Templates

| Template | Purpose |
|---|---|
| `single-product.html` | Single product detail page |
| `archive-product.html` | Shop / product archive |
| `taxonomy-product_attribute.html` | Product attribute archive (e.g. `/color/red`) |
| `product-search-results.html` | Search results scoped to products |
| `page-cart.html` | Cart page |
| `page-checkout.html` | Checkout page |
| `order-confirmation.html` | Thank-you / order received page |
| `coming-soon.html` | Coming Soon page |

### 4.2 Architecture Rules

- **Override at the template level only.** The HTML structure *inside* a WooCommerce block is private (see §2 Internal privacy gate). Themes recompose templates from documented blocks; they do not patch internals.
- **Same precedence as core.** Drop a same-named `.html` file in the theme's `templates/` directory and it wins over the WooCommerce default — no registration needed.
- **Recommended pattern.** Wrap each overridden template's body in a master pattern under `patterns/{{THEME_SLUG}}/{template-slug}.php`, then call sub-patterns from there. The master-pattern + sub-pattern rules in `SKILL.md` Sections 0 and 6 apply unchanged. Example:

  ```html
  <!-- templates/single-product.html -->
  <!-- wp:template-part {"slug":"header","tagName":"header","area":"header"} /-->
  <!-- wp:pattern {"slug":"{{THEME_SLUG}}/single-product"} /-->
  <!-- wp:template-part {"slug":"footer","tagName":"footer","area":"footer"} /-->
  ```

  The `single-product.php` master then assembles WooCommerce blocks inside `woocommerce/single-product` — never bare at the top level.

- **Ancestor gate still applies.** When recomposing a Single Product template, wrap product elements in `<!-- wp:woocommerce/single-product -->` … `<!-- /wp:woocommerce/single-product -->` so the ancestor contract is satisfied.

## 5. CSS Styling Architecture

### 5.1 BEM Naming and Class Prefix Priority

| Prefix | Meaning | Use in theme CSS? |
|---|---|---|
| `.wc-block-*` | Block-specific class (deprecated when a `components-*` equivalent exists) | Only if no `components-*` equivalent exists |
| `.wc-block-components-*` | Reusable component class — **preferred** | Yes — target this when both prefixes are present |
| `.price`, `.star-rating`, `.button` (unprefixed) | Legacy classes | **Forbidden in new code** — they collide with the editor chrome and third-party plugins |

**Conflict rule:** if an element carries both `.wc-block-foo` and `.wc-block-components-foo`, write the rule against `.wc-block-components-foo`. The `.wc-block-*` variant is deprecated and may be removed.

### 5.2 Container Queries

Cart and Checkout block wrappers use `@container (inline-size)` rather than viewport media queries. This lets the same Cart render at full-page width on Cart Page and at narrow width in a sidebar widget without theme-side breakpoint hacks.

Mapping table (legacy class → modern container query):

| Container width | Legacy class | Modern `@container` query |
|---|---|---|
| > 700px | `.is-large` | `@container (min-width: 701px)` |
| 521–700px | `.is-medium` | `@container (min-width: 521px) and (max-width: 700px)` |
| 401–520px | `.is-small` | `@container (min-width: 401px) and (max-width: 520px)` |
| ≤ 400px | `.is-mobile` | `@container (max-width: 400px)` |

**Critical constraint:** do **not** define `container-type` on an intermediate wrapper between the WooCommerce block root and your target element. A second `container-type` resets the query context and the rule will silently never match. If you need a new container query inside a Cart/Checkout block, accept that you cannot — restructure to query against the same root.

### 5.3 Specificity and Semantic Elements

- **Resets.** WooCommerce applies CSS resets to semantic elements when they're used as functional UI (e.g. `<a>` styled as a button via `.wc-block-components-button`). Theme CSS should win with the lowest specificity that does the job. Aggressive `!important` chains override functional resets and break the block.
- **`[hidden]` attribute.** WooCommerce relies on the HTML `hidden` attribute for runtime visibility (notices, conditional sections). Theme CSS **must** include:

  ```css
  [hidden] { display: none !important; }
  ```

  Without this, dismissed notices and conditional sections stay visible.

## 6. Cart & Checkout Filter Registry

The JS extension API for Cart and Checkout. Lives in `@woocommerce/blocks-checkout` and is invoked via `registerCheckoutFilters( 'namespace', { filterName: callback } )`. Filter callbacks receive the value, the line item or cart context, and an extensions bag — return the modified value.

### 6.1 Filter Catalogue (grouped by target area)

| Area | Available filters |
|---|---|
| **Cart line items** | `cartItemClass`, `cartItemPrice`, `itemName`, `saleBadgePriceFormat`, `showRemoveItemLink`, `subtotalPriceFormat` |
| **Order summary items** (Checkout sidebar) | `cartItemClass`, `cartItemPrice`, **`cartItemScreenReaderPrice`** *(A11y-required when overriding `cartItemPrice`)*, `itemName`, `subtotalPriceFormat` |
| **Totals footer** | `totalLabel`, `totalValue` |
| **Checkout buttons** | `proceedToCheckoutButtonLabel`, `proceedToCheckoutButtonLink`, `placeOrderButtonLabel` |
| **Coupons** | `coupons`, `showApplyCouponNotice`, `showRemoveCouponNotice` |
| **Registry extensibility** | `additionalCartCheckoutInnerBlockTypes` |

### 6.2 Protocol

- **Combinable.** A single `registerCheckoutFilters()` call may set as many filters as needed — group them by extension namespace.
- **A11y pairing.** When overriding `cartItemPrice`, pair it with `cartItemScreenReaderPrice` on the order summary side. The visible price may format as currency; the screen reader version should expand units. Failing to pair them breaks audit tools.
- **Debugging.** Filter contract violations (wrong return type, mutated input objects) surface as a UI notice for store administrators **and** a typed error in the browser console. Always check the console before guessing.

### 6.3 Canonical Example

The API shape is identical across all filters — one example covers the whole surface:

```js
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';

const namespace = '{{THEME_SLUG}}';

registerCheckoutFilters( namespace, {
    cartItemPrice: ( defaultValue, extensions, args ) => {
        if ( args?.cartItem?.totals?.line_total === '0' ) {
            return '<price/> (free)';
        }
        return defaultValue;
    },
    cartItemScreenReaderPrice: ( defaultValue, extensions, args ) => {
        if ( args?.cartItem?.totals?.line_total === '0' ) {
            return 'Price: free';
        }
        return defaultValue;
    },
    proceedToCheckoutButtonLabel: ( defaultValue ) => 'Continue to secure checkout',
} );
```

## 7. Conditional Tags

### 7.1 Catalogue

| Tag | True when… |
|---|---|
| `is_woocommerce()` | On any page using WooCommerce block/PHP templates (**excludes** shortcode-based Cart/Checkout) |
| `is_shop()` | On the product archive (Shop page) |
| `is_product()` | On a single product detail page |
| `is_product_category( $slug )` | On a product category archive |
| `is_cart()` | On the Cart page |
| `is_checkout()` | On the Checkout page |
| `is_wc_endpoint_url( $endpoint )` | On a specific WC endpoint (e.g. `order-pay`, `order-received`) |
| `is_ajax()` | Inside an AJAX request |

### 7.2 Timing Constraint

Conditional tags are only valid **after** the `posts_selection` action. The earliest reliable hook is `wp`. Calling them at global scope in `functions.php` returns false-y noise and silently breaks logic — this is the single most common WooCommerce theme bug.

**Wrong** (global scope — `is_product()` is always false here):

```php
// functions.php — DO NOT DO THIS
if ( is_product() ) {
	add_filter( 'body_class', fn( array $c ): array => array_merge( $c, array( 'has-product' ) ) );
}
```

**Right** (hooked into `wp`):

```php
add_action( 'wp', static function (): void {
	if ( is_product() ) {
		add_filter(
			'body_class',
			static fn( array $classes ): array => array_merge( $classes, array( 'has-product' ) )
		);
	}
} );
```

## 8. Logging and Debugging

WooCommerce ships a structured logger accessible via `wc_get_logger()`. Use it for runtime diagnostics — never `error_log()` or `print_r()` in production paths.

### 8.1 Canonical Pattern

```php
$logger  = wc_get_logger();
$context = array(
	'source'    => '{{TEXT_DOMAIN}}',
	'backtrace' => true, // include stack trace in the log entry
);

$logger->error( 'Payment gateway returned non-200 status while finalising order.', $context );
```

### 8.2 Severity Levels

PSR-3 compatible. From most to least severe:

1. `emergency` — system is unusable
2. `alert` — immediate action required
3. `critical` — critical condition
4. `error` — runtime error
5. `warning` — exceptional but recoverable
6. `notice` — normal but significant event
7. `info` — informational
8. `debug` — debug-level message

### 8.3 Best Practices

- **Prefer typed wrappers** (`->info()`, `->error()`, `->warning()`, …) over the generic `->log( $level, … )`. Typed wrappers are easier to grep and harder to typo.
- **Messages are non-translatable English sentences.** Logs are diagnostic artifacts read by developers and support engineers — they are not user-facing strings. Skip `__()` / `_e()` here.
- **One line per message.** Structured payloads (objects, arrays, IDs) go in `$context`, not inside the message string.
- **Use `wc_doing_it_wrong()` — not the logger — for developer-facing API misuse.** Misuse warnings should surface during development; the logger is for runtime events.

## 9. Core Functions and Namespaces

### 9.1 Retrieval Functions

| Function | Returns |
|---|---|
| `wc_get_product( int\|WP_Post $product )` | `WC_Product` instance (or `null`) |
| `wc_get_order( int\|WP_Post $order )` | `WC_Order` instance (or `false`) |
| `wc_get_template( string $template_name, array $args = [] )` | Includes a WooCommerce template part, passing `$args` as extracted variables |

### 9.2 Core Interfaces (for plugin / extension work)

- `WC_Logger_Interface`
- `WC_Log_Handler_Interface`
- `WC_Product_Data_Store_Interface`
- `WC_Order_Data_Store_Interface`
- `WC_Customer_Data_Store_Interface`
- `WC_Order_Item_Data_Store_Interface`

### 9.3 Namespace Map

| Namespace | Purpose |
|---|---|
| `Automattic\WooCommerce` | Modern core (the canonical entry point) |
| `\WooCommerce` (global) | Legacy / global alias — avoid in new code |
| `Automattic\WooCommerce\Internal\Admin\Logging` | Logger internals — read-only reference |
| `Automattic\WooCommerce\DataStores` | Concrete data store implementations |
| `Automattic\WooCommerce\Blocks` | Block registration, Cart/Checkout integration |
| `Automattic\WooCommerce\RestApi` | REST controllers and schemas |
| `Automattic\WooCommerce\Abstracts` | Base classes (e.g. `WC_Abstract_Order`) |

These namespace paths map 1:1 to filesystem paths inside `wp-content/plugins/woocommerce/src/`. When a function or class is unclear, grep that tree rather than guessing — the source is the canonical reference.

## 10. Common WooCommerce Pitfalls

| Symptom | Cause | Fix |
|---|---|---|
| Cart/Checkout layout broken at certain widths | Used `@media` instead of `@container`, or nested a second `container-type` between the block root and the target | Use `@container (inline-size)` once, on the block root only |
| Product element block renders empty (no error) | Block placed outside its declared Ancestor (see §3.1) | Wrap in `woocommerce/single-product`, `woocommerce/product-template`, or `core/post-template` per the ancestor column |
| Theme styles overwritten on WooCommerce update | Selector targeted internal block DOM (Internal Privacy gate) | Re-implement via block style variation or a documented Cart/Checkout filter |
| `is_shop()` returns false in `functions.php` | Called at global scope, before `posts_selection` | Move logic into a callback hooked to `wp` (or any later action) |
| Cart filter throws console error / store-admin notice | Filter callback returned the wrong type or mutated the input | Read the typed console error; never mutate the input — return a new value |
| A11y audit fails on Cart line item price | Overrode `cartItemPrice` but not `cartItemScreenReaderPrice` | Always pair the two when customising line item prices |
| Legacy class `.price` styled in theme conflicts with editor chrome | Unprefixed class collides with editor + third-party plugins | Rename to `.wc-block-components-product-price__value` (or use a block style variation) |
| Mini-Cart customisation has no effect | Block placed outside the Mini-Cart nesting tree (§3.4) | Re-anchor inside the correct parent — Empty/Filled views and Footer have specific parents |
| Template override is ignored | Filename mismatch with the WooCommerce default | The file in `templates/` must match the WooCommerce template name exactly (e.g. `single-product.html`, not `single_product.html`) |
| `wc_get_logger()` is `null` | Called before WooCommerce has loaded | Hook setup into `woocommerce_loaded` or later (e.g. `init`) |

## 11. When the Reference Falls Short

This file covers the stable, documented WooCommerce surface as of the project's vetted notes. If the user's question touches a feature that is not in the registries above, do not guess block names, attribute names, or filter names — grep the WooCommerce plugin source directly:

- Block definitions: `wp-content/plugins/woocommerce/src/Blocks/BlockTypes/` (PHP) and `wp-content/plugins/woocommerce/assets/js/blocks/` (JS).
- Cart/Checkout filters: `wp-content/plugins/woocommerce/packages/checkout/filter-registry/`.
- REST schemas: `wp-content/plugins/woocommerce/src/StoreApi/Schemas/`.

Report what you found from source rather than inventing API shapes — the Internal Privacy and Block Name gates apply just as strictly to "things I'm pretty sure exist".
