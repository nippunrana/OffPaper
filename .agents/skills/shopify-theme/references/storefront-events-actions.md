# Storefront Events & Actions Reference

## ⚠ Verification Status

This feature is **verified to exist** (confirmed on shopify.dev changelog June 2026),
but the complete list of event names and exact method signatures needs verification at
`shopify.dev/docs` before implementation. The entries marked ✅ below are confirmed
from the official changelog; entries marked ⚠ may have spelling variations.

---

## Table of Contents
1. [What Is the Standard Storefront Events & Actions API](#what-is-the-standard-storefront-events--actions-api)
2. [Verified Events](#verified-events)
3. [Verified Actions](#verified-actions)
4. [Theme Developer Role](#theme-developer-role)
5. [App Developer Role](#app-developer-role)
6. [Agentic Commerce Templates](#agentic-commerce-templates)
7. [Implementation Notes](#implementation-notes)

---

## What Is the Standard Storefront Events & Actions API

Shopify provides a standard communication layer between themes and the code (apps,
agents) that runs on them:

- **Events:** DOM events emitted by the theme for commerce interactions (product view,
  cart updates, search). Apps and agents subscribe to these events to receive real-time
  data without making follow-up API calls.

- **Actions:** Callable functions that apps and agents invoke to trigger theme behaviors
  (update cart, open cart drawer). By default, actions hit the Storefront API and reload
  the page, but theme developers can override them to handle UI updates without a reload.

This creates a standard interface so apps don't need separate integrations for each theme.

---

## Verified Events

These event names are confirmed from the Shopify developer changelog:

| Event name | Fired when | Payload |
|---|---|---|
| `shopify:product:view` ✅ | A product page loads or a product is viewed | Product data |
| `shopify:cart:lines-update` ✅ | Cart lines are updated (add, remove, quantity change) | Cart data |
| `shopify:search:update` ✅ | A search is performed | Search results data |

**Additional events likely exist** (not yet confirmed from available docs). Verify the
complete event list at: `https://shopify.dev/docs/storefronts/themes/architecture/events`

**How to emit an event from a theme (verified pattern):**
```javascript
// Themes emit events; apps/agents subscribe
document.dispatchEvent(new CustomEvent('shopify:product:view', {
  detail: { product: { id: '123', title: 'T-Shirt', price: 2500 } },
  bubbles: true,
  cancelable: false
}));
```

**How apps subscribe:**
```javascript
document.addEventListener('shopify:product:view', function(event) {
  const product = event.detail.product;
  // No follow-up API call needed — data is in event.detail
});
```

---

## Verified Actions

These action names are confirmed from the Shopify developer changelog:

| Action | Verified spelling | Description |
|---|---|---|
| `Shopify.actions.updateCart` ✅ | `Shopify.actions.updateCart` | Updates cart lines |
| `Shopify.actions.getCart` ✅ | `Shopify.actions.getCart` | Fetches current cart state |
| `Shopify.actions.openCart` ✅ | `Shopify.actions.openCart` | Opens cart drawer/page |

**⚠ Additional action methods** (e.g., `addToCart`, `removeFromCart`) may exist but
spellings are not confirmed. Verify the complete actions API at shopify.dev before using.

**Default behavior (if theme doesn't override):**
Actions hit the Storefront API and trigger a page reload.

**How theme developers override actions:**
Theme developers can intercept actions to update the UI without a reload:
```javascript
// Override Shopify.actions.updateCart to skip page reload
if (window.Shopify && window.Shopify.actions) {
  const originalUpdateCart = Shopify.actions.updateCart;
  Shopify.actions.updateCart = async function(updates) {
    // Perform AJAX cart update
    const response = await fetch('/cart/update.js', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ updates })
    });
    const cart = await response.json();

    // Update UI without page reload
    updateCartDrawer(cart);

    // Let the action emit its corresponding event
    return cart;
  };
}
```

---

## Theme Developer Role

As a theme developer, your responsibilities in this API:

1. **Emit events** for commerce interactions so apps can listen.
2. **Override actions** to handle UI updates without page reloads (optional, enhances UX).

**Pattern for emitting cart update event:**
```javascript
// After a cart update succeeds, emit the event
function updateCart(formData) {
  return fetch('/cart/add.js', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(cart => {
      // Emit the standard event
      document.dispatchEvent(new CustomEvent('shopify:cart:lines-update', {
        detail: { cart },
        bubbles: true
      }));
      return cart;
    });
}
```

**Checking if actions API is available:**
```javascript
if (window.Shopify && window.Shopify.actions) {
  // Override or extend actions
}
```

---

## App Developer Role

Apps subscribe to theme events and call actions. Theme developers don't need to
implement this side, but understanding it helps design good themes:

```javascript
// Apps subscribe to events
document.addEventListener('shopify:cart:lines-update', function(event) {
  // Triggered when cart updates; event.detail has cart data
  updateMiniCartWidget(event.detail.cart);
});

// Apps call actions to trigger theme behaviors
Shopify.actions.openCart(); // Opens cart drawer if theme supports it
```

---

## Agentic Commerce Templates

A related feature: Shopify auto-generates AI-readable discovery documents at these URLs:

| URL | Purpose |
|---|---|
| `/agents.md` | Primary AI agent discovery (capabilities, search, cart, UCP/MCP) |
| `/llms.txt` | Language model discovery (brand understanding); mirrors agents.md by default |
| `/llms-full.txt` | Extended LLM discovery with catalog details |

**Customizing via theme templates:**
Drop a template file in `templates/` to override the auto-generated content:

- `templates/agents.md.liquid` → controls `/agents.md`
- `templates/llms.txt.liquid` → controls `/llms.txt`
- `templates/llms-full.txt.liquid` → controls `/llms-full.txt`

If `agents.md.liquid` exists but `llms.txt.liquid` does not, `/llms.txt` falls back to
the content of `agents.md.liquid`.

**Example `templates/agents.md.liquid`:**
```liquid
---
title: {{ shop.name }} Commerce Interface
description: {{ shop.description | strip_html | truncate: 200 }}
---

## Store Information
- Name: {{ shop.name }}
- Domain: {{ shop.domain }}
- Currency: {{ shop.currency }}
- Locale: {{ request.locale.iso_code }}

## Product Discovery
- Search: {{ routes.search_url }}?q={query}&type=product
- All Collections: {{ routes.collections_url }}
- Product count: {{ shop.products_count }}

## Commerce Actions
- Add to cart: POST {{ routes.cart_add_url }}
- View cart: {{ routes.cart_url }}
- Checkout: {{ routes.checkout_url }}

## Policies
{% if shop.privacy_policy.body != blank %}
- Privacy: {{ routes.root_url }}policies/privacy-policy
{% endif %}
{% if shop.refund_policy.body != blank %}
- Refunds: {{ routes.root_url }}policies/refund-policy
{% endif %}
```

**The Shopify auto-generated default** includes:
- Store search and catalog URLs
- Sitemap pointer
- UCP (Universal Commerce Protocol) discovery endpoint
- MCP (Model Context Protocol) server endpoint at `/api/ucp/mcp`

---

## Implementation Notes

1. **Verify before implementing:** The complete standard storefront events/actions API
   may have evolved. Always verify at `shopify.dev/docs/storefronts/themes` before
   implementing event emission or action overrides.

2. **Don't implement this if not needed:** Most themes don't need to emit standard events
   or override actions. This API is primarily for themes targeting ecosystem-wide app
   compatibility or AI agent integration.

3. **Feature detection always:** Always check `window.Shopify && window.Shopify.actions`
   before using actions. The API may not be present in all contexts.

4. **`agents.md.liquid` customization is safe:** Overriding AI discovery templates is
   low-risk and increasingly important for stores that want AI agents to understand their
   product catalog, policies, and commerce capabilities.
