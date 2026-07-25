# WooCommerce Additional Checkout Fields API

This reference covers `woocommerce_register_additional_checkout_field` — the supported way
to add custom data collection to the **block-based** Cart/Checkout without `unset()` hacks,
fragile array filtering, or template overrides. It is the modern replacement for the legacy
`woocommerce_checkout_fields` filter and is the only path that keeps custom data a
first-class citizen in the Store API and React frontend.

For the runtime status/observer model that *fires while these fields are validated and
submitted* (`onCheckoutValidation`, `onPaymentSetup`, statuses), see
`references/woocommerce-checkout-lifecycle.md`. For coding-standard rules referenced here
(prefixing, `function_exists`, textdomain), see `references/woocommerce-standards.md`.

## 1. When to Load This File

Load this file **before writing any code** when the user asks to:

- Add, modify, or remove a field in the block Cart/Checkout (contact / address / order area).
- Register a custom field that must persist to customer or order meta.
- Sanitize or validate a checkout field server-side.
- Show/hide or require a checkout field conditionally based on cart state (JSON Schema).
- Read a custom checkout value back out of an order or customer object.
- Keep a legacy `woocommerce_checkout_fields` integration working alongside the block checkout.

If the task is only *styling* Cart/Checkout or composing `woocommerce/*` blocks, stay in
`references/woocommerce.md`. If it is about the *submission lifecycle* (payment handshake,
redirects), use `references/woocommerce-checkout-lifecycle.md`.

## 2. Pre-Write Gates (Checkout Fields)

All must pass before writing code. These are **in addition to** the gates in `SKILL.md` and
`references/woocommerce.md → §2`.

- **Timing gate** — registration must run **on or after `woocommerce_init`**. Registering
  earlier means the Checkout block and Store API are not ready and the REST handshake fails.
  Always wrap the call in `add_action( 'woocommerce_init', … )`.
- **ID-syntax gate** — every `id` must be `namespace/field-name` (a single forward slash).
  The `/` is a mandatory separator; the namespace must be your unique prefix. Core IDs that
  do **not** use a slash (`billing_company`, `billing_phone`, …) are only used when
  *overriding a default field*, never when inventing a new one.
- **Location gate** — `location` must be exactly one of `contact`, `address`, or `order`.
  No other values exist. The location decides persistence and UI placement (§3) — choosing
  wrong causes data loss or a fragmented customer profile, not an error.
- **Type gate** — `type` must be exactly `text`, `select`, or `checkbox`. These are the only
  supported field types. `select` additionally requires an `options` array.
- **Pointer-escaping gate** — in any JSON Schema rule (§6), a field ID's `/` must be escaped
  as `~1`. `my-plugin/field` is referenced as `my-plugin~1field`. An unescaped slash is read
  as a pointer path segment and silently never matches.

## 3. Field Locations — Placement & Persistence

`location` is the single most important architectural decision: it dictates UI placement,
where the data is stored, and how long it lives.

| Location | Identifier | UI Placement | Persistence | Lifecycle |
|---|---|---|---|---|
| Contact information | `contact` | Form header, next to the email field | **Customer Meta** — shows in "Account Details" on My Account | Long-lived, tied to shopper identity (e.g. Loyalty ID) |
| Addresses | `address` | **Both** Shipping and Billing forms | **Customer Meta + Order Meta** (dual-saved) | Reused across orders, pre-fills for returning customers |
| Order information | `order` | "Order Information" inner block (defaults to form footer; repositionable via Gutenberg block controls) | **Order Meta only** | Transient — new orders start empty (e.g. Gift Message) |

### 3.1 Address-Field Bifurcation (critical)

Registering **one** field in the `address` location renders it in **both** the shipping and
billing forms. If the shopper unchecks "Same as billing," WooCommerce collects and stores
**two independent values**:

- `_wc_shipping/my-plugin/field`
- `_wc_billing/my-plugin/field`

Always design address-location logic to handle two values, even when the UI looks unified.
The `$group` parameter in location validation (§5.2) tells you which one you are processing.

## 4. Field Types & Options

Registration happens inside the `woocommerce_init` action:

```php
add_action( 'woocommerce_init', static function (): void {
	woocommerce_register_additional_checkout_field( array(
		'id'            => '{{TEXT_DOMAIN}}/reference-code',
		'label'         => __( 'Reference Code', '{{TEXT_DOMAIN}}' ),
		'optionalLabel' => __( 'Reference Code (if available)', '{{TEXT_DOMAIN}}' ),
		'location'      => 'address',
		'type'          => 'text',
		'required'      => false,
	) );
} );
```

### 4.1 Type Reference

| Type | Required options | Type-specific attributes | Meta prefix |
|---|---|---|---|
| `text` | `id`, `label`, `location` | `optionalLabel`, `maxLength`, `readOnly` | `_wc_billing/`, `_wc_shipping/`, `_wc_other/` |
| `select` | `id`, `label`, `location`, `options` | `placeholder`, `optionalLabel` | `_wc_other/` |
| `checkbox` | `id`, `label`, `location` | `error_message`, `optionalLabel` | `_wc_other/` |

- **`optionalLabel`** — if omitted, WooCommerce appends `(optional)` to the label
  automatically. Define it explicitly for a clean, custom UI.
- **`options`** (select only) — array of `{ value, label }` pairs.
- **`required`** — boolean, or a JSON Schema rule array for conditional requirement (§6).

## 5. Sanitization & Validation

Server-side enforcement is the final defense regardless of client state. **Sanitization
always runs before validation** — sanitize formats the data, validation judges it.

### 5.1 Single-Field Sanitize then Validate

Two equivalent registration styles exist; both appear in the source docs.

**Inline callbacks on the registration:**

```php
woocommerce_register_additional_checkout_field( array(
	'id'                => '{{TEXT_DOMAIN}}/government-id',
	'label'             => __( 'Government ID', '{{TEXT_DOMAIN}}' ),
	'location'          => 'address',
	'type'              => 'text',
	'sanitize_callback' => static fn( string $value ): string =>
		strtoupper( str_replace( ' ', '', $value ) ), // "abc 123" -> "ABC123"
	'validate_callback' => static function ( string $value, string $field_id, WP_Error $errors ): void {
		if ( ! preg_match( '/^[A-Z0-9]{5}$/', $value ) ) {
			$errors->add( '{{TEXT_DOMAIN}}-invalid-id', __( 'The ID must be 5 alphanumeric characters.', '{{TEXT_DOMAIN}}' ) );
		}
	},
) );
```

**Or via the global filter/action hooks** (equivalent; useful when the field is registered elsewhere):

- `woocommerce_sanitize_additional_field` (filter) — formatting stage.
- `woocommerce_validate_additional_field` (action) — rule-enforcement stage.

```php
add_action( 'woocommerce_validate_additional_field', static function ( WP_Error $errors, string $field_key, $field_value ): void {
    if ( '{{TEXT_DOMAIN}}/gov-id' === $field_key && ! preg_match( '/^[A-Z0-9]{5}$/', (string) $field_value ) ) {
        $errors->add( '{{TEXT_DOMAIN}}-invalid-id', __( 'The ID must be 5 alphanumeric characters.', '{{TEXT_DOMAIN}}' ) );
    }
}, 10, 3 );
```

**Validation rules (non-negotiable):**

- **Return `void` on success** — do not return the `WP_Error` object; call `$errors->add()` to fail.
- **Namespace every error code** — prefix with your plugin namespace so codes from core or
  other extensions don't collide and overwrite your message.

### 5.2 Multi-Field / Cross-Field Validation

When a field's validity depends on a sibling (e.g. "Confirm ID", or a tax ID that depends on
the selected country), use the location-scoped action. It receives **all** fields in the group.

| Location | Action hook |
|---|---|
| `contact` | `woocommerce_blocks_validate_location_contact_fields` |
| `address` | `woocommerce_blocks_validate_location_address_fields` |
| `order` | `woocommerce_blocks_validate_location_other_fields` *(note: `other`, not `order`)* |

For the **address** location the third parameter `$group` is `'billing'` or `'shipping'` —
essential because of address-field bifurcation (§3.1).

```php
add_action( 'woocommerce_blocks_validate_location_address_fields', static function ( WP_Error $errors, array $fields, string $group ): void {
    // $group is 'billing' or 'shipping'
    if ( ( $fields['{{TEXT_DOMAIN}}/id'] ?? '' ) !== ( $fields['{{TEXT_DOMAIN}}/confirm-id'] ?? '' ) ) {
        $errors->add( '{{TEXT_DOMAIN}}-mismatch', __( 'The ID values do not match.', '{{TEXT_DOMAIN}}' ) );
    }
}, 10, 3 );
```

## 6. Conditional Logic via JSON Schema

WooCommerce Blocks uses **JSON Schema Draft-07** for the `required`, `hidden`, and
`validation` properties. Rules are evaluated in real time on the client **and** enforced on
the server — one definition, two enforcement points ("single source of truth").

### 6.1 The Document Object & Pointers

Rules evaluate against the **Document Object**, which describes current checkout state. All
of its properties are **snake_case** (`total_price`, `shipping_rates`, `prefers_collection`).
Navigation uses JSON pointers:

- **Slash escaping** — a field ID containing `/` must use `~1`. `my-plugin/field` →
  `my-plugin~1field`. (See the Pointer-escaping gate in §2.)
- **`0/` prefix** — root-level navigation, e.g. `0/customer/address/phone`.
- **`1/` prefix** — relative navigation, steps back one level to a sibling within the same
  address group, e.g. `1/phone`.

### 6.2 Required / Hidden / Validation Example

This field is hidden when Local Pickup is selected, required when it is not, and carries a
real-time validation message via the **non-standard `errorMessage` keyword** (a WooCommerce
extension to Draft-07):

```php
woocommerce_register_additional_checkout_field( array(
	'id'       => '{{TEXT_DOMAIN}}/delivery-instructions',
	'label'    => __( 'Delivery Instructions', '{{TEXT_DOMAIN}}' ),
	'location' => 'order',
	'type'     => 'text',
	'required' => array(
		array( 'properties' => array( 'prefers_collection' => array( 'const' => false ) ) ),
	),
	'hidden'   => array(
		array( 'properties' => array( 'prefers_collection' => array( 'const' => true ) ) ),
	),
	'validation' => array(
		array(
			'minLength'    => 5,
			'errorMessage' => __( 'Please provide a valid instruction.', '{{TEXT_DOMAIN}}' ),
		),
	),
) );
```

### 6.3 Performance Caveat

Schemas are re-evaluated on **every** frontend state change. Keep them shallow — avoid deep
nesting and minimize the number of conditions per registration, or the checkout UI lags.

## 7. Modifying Default (Core) Fields

To change attributes on a built-in field (e.g. the `optionalLabel` on Company), re-register
it by its **core identifier** (no namespace slash) rather than using the legacy `unset()`:

```php
woocommerce_register_additional_checkout_field( array(
	'id'            => 'billing_company', // core identifier — no namespace
	'location'      => 'address',
	'optionalLabel' => __( 'Business Name (optional)', '{{TEXT_DOMAIN}}' ),
) );
```

Common core identifiers for the `address` / `contact` locations:

```
billing_first_name  billing_last_name   billing_company
billing_address_1   billing_address_2   billing_city
billing_postcode    billing_country     billing_state
billing_email       billing_phone
```

### 7.1 Editing States — the `XX` Placeholder

When adding or modifying states, **every** state ID in the array must be prefixed with the
field's two-letter country code in place of the literal `XX`. WooCommerce will not recognize
state modifications unless each state ID is preceded by its correct country code.

## 8. Reading Field Values Back

Always prefer the **helper methods** over direct meta-key access — they survive future schema
migrations and normalize types.

| Helper | Returns |
|---|---|
| `get_field_from_object( string $id, $object )` | One field's value from a customer/order object |
| `get_all_fields_from_object( $object )` | Array of all registered additional fields on the object |

### 8.1 The Stale-Data Trap (most common bug)

During the `place_order` lifecycle, **never** read the logged-in customer via
`wp_get_current_user()` (or similar) — the persistent customer record is only updated *after*
the order is processed, so you get `null` or stale data. Always read from the **object being
processed** (the order) using `get_all_fields_from_object( $order )` or the Store API inputs.

### 8.2 Guest vs Logged-In Persistence

| Customer state | Mechanism | Behavior |
|---|---|---|
| Guest | Session-based storage | Available while the session is valid |
| Logged-in | Database persistence | Only persisted **after** the order is placed |

### 8.3 Direct Meta-Key Access (external engines only)

For email builders or external systems that cannot call PHP helpers, data is stored under:

- **Address:** `_wc_billing/[namespace]/[field]` and `_wc_shipping/[namespace]/[field]`
- **Contact / Order:** `_wc_other/[namespace]/[field]`

Prefer the constants on the `CheckoutFields` class over hardcoding:

- `CheckoutFields::BILLING_ADDRESS_META_KEY_PREFIX` → `_wc_billing/`
- `CheckoutFields::SHIPPING_ADDRESS_META_KEY_PREFIX` → `_wc_shipping/`
- `CheckoutFields::OTHER_FIELDS_META_KEY_PREFIX` → `_wc_other/`

**Checkbox caveat:** direct DB reads return the strings `"1"`, `"0"`, or `""`. The helper
methods automatically cast these to real booleans (`true` / `false`) — another reason to
prefer helpers and keep your business logic type-safe.

## 9. Backward Compatibility with Legacy Meta

During migration from classic to block checkout, keep legacy meta-keys in sync with two hooks.
The `{$key}` in the read filter is the **full field ID including the namespace**.

- **Saving** — `woocommerce_set_additional_field_value`: mirror block-based data back into
  legacy meta when an order saves.
- **Reading** — `woocommerce_get_default_value_for_{$key}`: pull a value from legacy meta to
  pre-fill a block field.

## 10. Common Pitfalls

| Symptom | Cause | Fix |
|---|---|---|
| REST handshake / "field not registered" error | Registered before `woocommerce_init` | Wrap registration in `add_action( 'woocommerce_init', … )` |
| Field collides with another extension | Missing namespace, or non-slash `id` for a new field | Use `namespace/field-name` for all new fields |
| Address field saves only one value | Forgot bifurcation — read only billing OR shipping | Handle both `_wc_billing/…` and `_wc_shipping/…`; use `$group` in validation |
| Validation never fails | Returned `WP_Error` instead of calling `add()`, or returned a truthy value on success | Return `void` on pass; call `$errors->add( 'ns-code', … )` to fail |
| Error message overwritten by another plugin | Un-namespaced error code | Prefix every error code with your namespace |
| Conditional rule silently never matches | `/` in field ID not escaped in the JSON pointer | Escape as `~1` (`my-plugin~1field`) |
| Checkbox logic treats `"0"` as truthy | Read meta directly instead of via helper | Use `get_field_from_object()`; it returns real booleans |
| Custom value is `null` during order processing | Read `wp_get_current_user()` mid-`place_order` (stale-data trap) | Read from the order object via `get_all_fields_from_object( $order )` |
| State edits ignored by WooCommerce | State IDs not prefixed with the country code | Replace the `XX` placeholder with the real two-letter code on every state ID |

## 11. Architect's Checklist

Before shipping a checkout-fields integration, verify:

1. **Timing** — registration runs on/after `woocommerce_init`.
2. **Mapping** — the `location` matches the required persistence (Customer Meta vs Order Meta).
3. **Sanitization** — formatting runs *before* validation.
4. **Integrity** — `WP_Error` codes are namespaced and applied via `$errors->add()`, returning `void` on success.
5. **Schema syntax** — every field-ID `/` is escaped as `~1` in JSON pointers; schemas are shallow.
6. **Compatibility** — legacy filters use the full `namespace/field` ID for data continuity.

## 12. When the Reference Falls Short

This file covers the documented Additional Checkout Fields surface from the project's vetted
notes. If the user needs an undocumented field type, a Store API schema detail, or an
attribute not listed above, **do not invent it** — grep the WooCommerce source:

- Field registration & `CheckoutFields`: `wp-content/plugins/woocommerce/src/Blocks/Domain/Services/CheckoutFields.php`
- Store API schemas: `wp-content/plugins/woocommerce/src/StoreApi/Schemas/`

Report what you find from source rather than guessing API shapes.
