# Templates Reference

## Table of Contents
1. [Template Types](#template-types)
2. [JSON vs. Liquid Templates](#json-vs-liquid-templates)
3. [JSON Template Schema](#json-template-schema)
4. [Alternate Templates](#alternate-templates)
5. [Liquid-Only Templates](#liquid-only-templates)
6. [Agentic Commerce Templates](#agentic-commerce-templates)
7. [Customer Templates](#customer-templates)
8. [Metaobject Templates](#metaobject-templates)
9. [Template Type Quick Reference](#template-type-quick-reference)

---

## Template Types

Shopify renders a specific URL using the template file that matches the resource type.
All template files live in `templates/`.

| Template name | URL pattern | Format options |
|---|---|---|
| `index` | Homepage (`/`) | JSON or Liquid |
| `product` | `/products/handle` | JSON or Liquid |
| `collection` | `/collections/handle` | JSON or Liquid |
| `article` | `/blogs/blog-handle/article-handle` | JSON or Liquid |
| `blog` | `/blogs/blog-handle` | JSON or Liquid |
| `page` | `/pages/handle` | JSON or Liquid |
| `cart` | `/cart` | JSON or Liquid |
| `search` | `/search` | JSON or Liquid |
| `list-collections` | `/collections` | JSON or Liquid |
| `404` | Any invalid URL | JSON or Liquid |
| `password` | Password-protected store page | JSON or Liquid |
| `gift_card` | Gift card URL | **Liquid only** |
| `robots.txt` | `/robots.txt` | **Liquid only** (filename: `robots.txt.liquid`) |
| `agents.md` | `/agents.md` | **Liquid only** (filename: `agents.md.liquid`) |
| `llms.txt` | `/llms.txt` | **Liquid only** (filename: `llms.txt.liquid`) |
| `llms-full.txt` | `/llms-full.txt` | **Liquid only** (filename: `llms-full.txt.liquid`) |
| `metaobject/{type}` | `/pages/{type}/{handle}` | JSON or Liquid |

---

## JSON vs. Liquid Templates

| | JSON template | Liquid template |
|---|---|---|
| **Purpose** | Orchestration — defines sections and order | Direct rendering with full Liquid logic |
| **Content** | Pure JSON (no HTML, no Liquid) | HTML + Liquid |
| **Merchant flexibility** | High — merchants can add/reorder/remove sections | Low — fixed layout |
| **Preferred for** | All standard page types | Special templates requiring logic |
| **App block support** | Yes | No |

**Rule:** A URL route has ONE template — either JSON or Liquid. Both cannot exist for
the same route; if both exist, JSON takes priority.

---

## JSON Template Schema

JSON templates use this exact structure. No HTML, no Liquid logic inside the JSON file.

```json
{
  "layout": "theme",
  "wrapper": "main",
  "sections": {
    "unique_section_id": {
      "type": "section-filename-without-extension",
      "disabled": false,
      "settings": {
        "setting_id": "value"
      },
      "blocks": {
        "block_unique_id": {
          "type": "block-type-name",
          "settings": {
            "text": "Block content"
          }
        }
      },
      "block_order": ["block_unique_id"]
    }
  },
  "order": ["unique_section_id"]
}
```

**Key rules:**
- `layout`: name of the layout file without `.liquid` (e.g., `"theme"`). Omit to use default.
- `wrapper`: HTML element wrapping the rendered sections (e.g., `"main"`).
- `sections`: object keyed by unique section IDs (any string, unique within this template).
- `order`: array of section IDs in render order; every ID in `sections` should appear here.
- Maximum **25 sections** per JSON template.
- Comments and trailing commas are **stripped** from JSON template files.

**Product template example:**
```json
{
  "layout": "theme",
  "wrapper": "main",
  "sections": {
    "main_product": {
      "type": "main-product",
      "settings": {
        "show_vendor": true,
        "enable_sticky_info": true
      },
      "blocks": {
        "vendor": { "type": "text", "settings": { "text": "" } },
        "title": { "type": "title", "settings": {} },
        "price": { "type": "price", "settings": {} },
        "buy_buttons": { "type": "buy_buttons", "settings": {} }
      },
      "block_order": ["vendor", "title", "price", "buy_buttons"]
    },
    "product_recommendations": {
      "type": "product-recommendations",
      "settings": {
        "products_to_show": 4,
        "columns_desktop": 4
      }
    }
  },
  "order": ["main_product", "product_recommendations"]
}
```

---

## Alternate Templates

Shopify supports multiple templates per resource type for different use cases.
Naming convention: `{type}.{suffix}.json` or `{type}.{suffix}.liquid`.

Examples:
```
templates/product.json            ← default product template
templates/product.preorder.json   ← alternate template for pre-order products
templates/product.bundle.json     ← alternate template for product bundles
templates/page.landing.json       ← alternate landing page template
templates/page.faq.json           ← alternate FAQ page template
```

To assign an alternate template to a product/page, use the Shopify admin or the
template attribute in theme editor. The merchant selects from Page Templates dropdown.

---

## Liquid-Only Templates

These templates cannot be JSON — they must be Liquid files.

### gift_card.liquid
Used for rendering gift card pages. Has access to `gift_card` object.

### robots.txt.liquid
Controls search engine crawling. Rendered at `/robots.txt`.
Default is auto-generated; override with `templates/robots.txt.liquid`.

```liquid
{%- assign sitemap = routes.root_url | append: 'sitemap.xml' -%}
User-agent: *
Disallow: /admin
Disallow: /cart
Sitemap: {{ sitemap }}
```

### password.liquid
Can be JSON or Liquid — Liquid gives more control for custom password pages.

---

## Agentic Commerce Templates

These templates customize how AI agents and language models discover and interact
with the store. They follow the same pattern as `robots.txt.liquid`.

**`templates/agents.md.liquid`** → `/agents.md`
The primary agent discovery document. Default is auto-generated by Shopify and
describes store capabilities, search, catalog, UCP/MCP endpoints.

```liquid
---
title: {{ shop.name }} - Agent Commerce Interface
description: {{ shop.description }}
---

## Store Overview
{{ shop.name }} sells {{ shop.products_count }} products.

## Search
Search products at: {{ routes.root_url }}search?q={query}

## Cart & Checkout
- Cart URL: {{ routes.cart_url }}
- Checkout URL: {{ routes.root_url }}checkout
```

**`templates/llms.txt.liquid`** → `/llms.txt`
Mirrors `agents.md` by default. Override to customize what language models see.

**`templates/llms-full.txt.liquid`** → `/llms-full.txt`
Extended version of `llms.txt` with more detail about products and policies.

**Priority:** If a specific template file exists, it overrides Shopify's auto-generated
default for that path. `agents.md.liquid` also controls the default content for the
`/llms.txt` and `/llms-full.txt` paths if their specific template files don't exist.

---

## Customer Templates

Customer account templates live in `templates/customers/`.

| Template | Route |
|---|---|
| `templates/customers/login.json` | `/account/login` |
| `templates/customers/register.json` | `/account/register` |
| `templates/customers/account.json` | `/account` |
| `templates/customers/order.json` | `/account/orders/{id}` |
| `templates/customers/addresses.json` | `/account/addresses` |
| `templates/customers/activate_account.json` | Account activation |
| `templates/customers/reset_password.json` | Password reset |

Note: These are for **legacy customer accounts**. New customer accounts use a
separate headless interface managed differently.

---

## Metaobject Templates

Metaobject templates live in `templates/metaobject/`.
Naming: `templates/metaobject/{metaobject-type}.json`

Example: If you have a metaobject type with handle `team_member`, create:
`templates/metaobject/team_member.json`

Rendered at: `/pages/team-member/{handle}` (verify URL pattern at shopify.dev)

---

## Template Type Quick Reference

```
Needs pure logic/HTML in template → Liquid template (*.liquid)
Needs merchants to add/reorder sections → JSON template (*.json)
Rendering /robots.txt → robots.txt.liquid (Liquid only)
Rendering /agents.md or /llms.txt → agents.md.liquid / llms.txt.liquid (Liquid only)
Gift card pages → gift_card.liquid (Liquid only)
Customer account pages → templates/customers/*.json
Metaobject pages → templates/metaobject/{type}.json
Multiple designs for same resource → product.{suffix}.json or page.{suffix}.json
No layout needed → {% layout none %} in Liquid template
```
