# WooCommerce Cart & Checkout JS Lifecycle, Observers & SlotFill

This reference covers the **client-side** extensibility model of the block Cart/Checkout: the
data stores, the status state machine, the event-emitter / observer pattern that drives the
"Place Order" handshake, the SlotFill injection points, and the native DOM events that
replaced jQuery. It is the JS counterpart to `references/woocommerce-checkout-fields.md`
(which covers the PHP field-registration side).

For the static value-transform filters (`cartItemPrice`, `totalLabel`, etc. via
`@woocommerce/blocks-checkout`) see `references/woocommerce.md → §6`. This file adds the
**event** layer and the **second** filter-registration path (`@wordpress/hooks`).

## 1. When to Load This File

Load **before writing any JS** when the user asks to:

- Run custom logic when checkout validates, when payment is set up, or after the order
  succeeds/fails (`onCheckoutValidation`, `onPaymentSetup`, `onCheckoutSuccess`/`Fail`).
- Block or delay order placement until async work (third-party tax, fraud check) completes.
- Read checkout/cart state in React (`isCalculating`, totals, addresses) via the data stores.
- Inject a React component into the Cart/Checkout (SlotFill: order meta, shipping packages,
  local pickup, discounts).
- React to add-to-cart / remove-from-cart events, or bridge legacy jQuery cart events.
- Transform cart line-item display via the `woocommerce/cart-line-items` filter.

If the task is server-side field registration/validation, use
`references/woocommerce-checkout-fields.md`. If it is CSS/markup, use `references/woocommerce.md`.

## 2. Pre-Write Gates (Checkout Lifecycle)

- **Single-source-of-truth gate** — the Checkout block owns *all* server communication.
  Extensions must **never** POST to the server directly; they pass data into the block's
  request via observer return values (`paymentMethodData`, billing/address objects). Bypassing
  the block desynchronizes state and corrupts the order.
- **No-mutation gate** — observers run in **strict sequential order**. An observer must
  **never** mutate global or context state directly: later observers won't see the change.
  Always communicate results back through the **returned response object** (Success/Fail/Error).
- **Structured-return gate** — observers must return a typed response object, not a bare
  boolean/string, or the status machine cannot transition to `COMPLETE` cleanly.
- **Gate-condition awareness** — `onPaymentSetup` only fires when all three gates are open
  (§5). If your payment logic "never runs," check `isProcessing` / `hasError` / `isCalculating`
  before assuming a wiring bug.

## 3. Data Stores

State lives in two `wp.data` stores — the "single source of truth" that keeps the React UI
and the underlying data in real-time sync:

- `wc/store/cart` — cart contents, totals, coupons, shipping rates.
- `wc/store/checkout` — checkout status, flags, and order data.

```js
import { useSelect } from '@wordpress/data';

const isCalculating = useSelect(
    ( select ) => select( 'wc/store/checkout' ).isCalculating(),
    []
);

return <div className={ isCalculating ? 'is-loading' : '' }>{ /* … */ }</div>;
```

To programmatically **halt** checkout until custom async logic finishes, dispatch the
`disableCheckoutFor` thunk (e.g. while a third-party tax service responds). This is the
supported way to keep "Place Order" disabled without racing the status machine.

## 4. The Status State Machine

The checkout moves through a fixed sequence of statuses. Knowing the current chapter tells you
which events fire and which buttons are live.

| Status | Meaning | What happens |
|---|---|---|
| `IDLE` | Baseline | Set after load and after every state change; ready for input |
| `CALCULATING` | Doing the math | Coupon applied, shipping rate selected, or address updated |
| `BEFORE_PROCESSING` | The inspection | Fires `onCheckoutValidation`; observers vet the data |
| `PROCESSING` | The handshake | Fires `onPaymentSetup`; payment method confirms data |
| `AFTER_PROCESSING` | Server replied | Branches to `onCheckoutSuccess` or `onCheckoutFail` |
| `COMPLETE` | Done | Shopper redirected to the "Order Received" page |

### 4.1 `isCalculating` — UI Safety Behaviors

While `isCalculating` is `true`, the block performs three protections so a shopper can't
submit against an outdated total:

1. Disables the **Place Order** button (Checkout block).
2. Disables the **Proceed to Checkout** button (Cart block).
3. Shows a **loading state for Express Payment** methods (Apple Pay / Google Pay) until totals settle.

## 5. Event Emitters & Observers

The block **emits** events; extensions register **observers** that react. Three core events:

### 5.1 `onCheckoutValidation` (during `BEFORE_PROCESSING`)

The "great inspection." Each observer can inspect the data and block submission. To fail:

- Return an `errorMessage` (renders as a general notice), **or**
- Return `validationErrors` — an object whose **keys must match the field property names**
  (e.g. `email`, `shipping_address`, `coupon`) so the message appears next to the right field.

### 5.2 `onPaymentSetup` (during `PROCESSING`)

The payment "handshake." It is a **blocking observer chain** — if any observer returns a
non-truthy/error value, processing aborts. It fires **only when all three gates are open**:

1. `isProcessing` is `true`,
2. `hasError` is `false`,
3. `isCalculating` is `false`.

Responses:

- **Success** — return a `success` type, optionally with `paymentMethodData`. That data is
  bundled into the server request that finalizes the order.
- **Failure** — return an `error` or `fail` type. This sets `hasError = true` and halts the
  journey so the shopper can fix the problem.

### 5.3 `onCheckoutSuccess` / `onCheckoutFail` (during `AFTER_PROCESSING`)

After the server replies, the flow branches. On success, observers receive five fields:

1. **Redirect URL** — usually the "Order Received" page.
2. **Order ID**.
3. **Customer ID**.
4. **Order Notes** — any note the customer left.
5. **Payment Result** — `{ paymentStatus, paymentDetails }` (an arbitrary server-side object).

### 5.4 Retry Logic

`AFTER_PROCESSING` doesn't always end in `COMPLETE`. If the server returns a **fixable** error
(e.g. a declined card) and the `retry` flag is `true`, the status returns to `IDLE` so the
shopper can try again. Only when the status reaches `COMPLETE` is the shopper redirected.

### 5.5 The Cardinal Rule

> Observers must **never** update global or context state directly. Because they run
> sequentially, later observers won't see the change. Communicate everything through the
> returned response object (Success / Fail / Error).

## 6. SlotFill Injection Points

Slots let you render React components into protected areas of Cart/Checkout **without**
template overrides or touching private internal DOM. Each fill receives `cart` (camelCase
Store API data), `extensions` (third-party data bag), and `context`
(`woocommerce/cart` or `woocommerce/checkout`).

| Slot | Renders |
|---|---|
| `ExperimentalOrderMeta` | Above the primary action buttons |
| `ExperimentalOrderShippingPackages` | Inside the shipping step / options |
| `ExperimentalOrderLocalPickupPackages` | Inside the Local Pickup block |
| `ExperimentalDiscountsMeta` | Directly below the coupon input |

These are **Experimental** APIs (note the prefix) — they may change between releases; isolate
their use and don't build deep dependencies on their internal markup.

## 7. Cart Line-Item Filter via `@wordpress/hooks`

Distinct from the `registerCheckoutFilters()` path in `references/woocommerce.md → §6`, line
items can also be filtered through the `woocommerce/cart-line-items` registry using
`registerFilters` from `@wordpress/hooks`. Callbacks receive `( value, extensions, args )`
where `args.context` is e.g. `summary`.

```js
import { registerFilters } from '@wordpress/hooks';

registerFilters( 'woocommerce/cart-line-items', {
    itemName: ( name, extensions, args ) =>
        args.context === 'summary' ? `🛒 ${ name }` : name,
} );
```

**Price-filter constraint (same as §6):** any filter that returns a price string
(`cartItemPrice`, `saleBadgePriceFormat`, `subtotalPriceFormat`) **must** include the
`<price/>` substring, or validation fails and the filter is ignored.

## 8. Native DOM Events (jQuery Replacement)

WooCommerce Blocks moved off jQuery to native DOM events. Legacy jQuery cart events are
bridged by the `translatejQueryEventToNative()` utility, which re-emits them with the
`wc-blocks_` prefix so modern code can listen without jQuery.

| Native event | Fires when | Notable payload |
|---|---|---|
| `wc-blocks_adding_to_cart` | Add-to-cart request initiated | — |
| `wc-blocks_added_to_cart` | Add-to-cart succeeded | `preserveCartData` parameter |
| `wc-blocks_removed_from_cart` | Item removal completed | — |

```js
document.body.addEventListener( 'wc-blocks_added_to_cart', ( event ) => {
    // react to a successful add-to-cart without jQuery
} );
```

## 9. Common Pitfalls

| Symptom | Cause | Fix |
|---|---|---|
| Custom payment logic never runs | One of the three `onPaymentSetup` gates is closed | Verify `isProcessing` true, `hasError` false, `isCalculating` false |
| Later observer ignores an earlier observer's change | Mutated global/context state directly (No-mutation gate) | Communicate only via the returned response object |
| Validation error shows as a generic notice, not on the field | `validationErrors` key didn't match the field property name | Use exact property names (`email`, `shipping_address`, `coupon`) |
| Order stuck, never reaches "Thank You" | Observer returned a bare value, not a structured response | Return a typed Success/Fail/Error object |
| Declined card doesn't let shopper retry | `retry` flag not set / not honored | Return a fixable error with `retry: true` to bounce back to `IDLE` |
| Price filter silently ignored | Return string missing the `<price/>` token | Include `<price/>` in any price-returning filter |
| SlotFill content vanishes after update | Depended on Experimental slot internals | Treat `Experimental*` slots as unstable; isolate usage |
| Extension data never reaches the server | Tried to POST directly instead of via the block | Pass data through observer returns (`paymentMethodData`, address objects) |

## 10. When the Reference Falls Short

This file covers the documented client lifecycle from the project's vetted notes. For an
event, selector, or slot not listed here, **do not guess** — grep the source:

- Checkout data store & events: `wp-content/plugins/woocommerce/assets/js/base/context/`
  and `…/data/checkout/`.
- Cart/Checkout filters: `wp-content/plugins/woocommerce/packages/checkout/filter-registry/`.
- SlotFill definitions: `wp-content/plugins/woocommerce/assets/js/blocks/cart-checkout-shared/`.

Report what you find from source rather than inventing event or slot names.
