# Abilities API — Client-Side (WP 7.0+)

The Abilities API is the common surface for AI agents and plugins to discover and invoke WordPress capabilities. Server-side registration is documented in `architecture.md` ("Abilities API"). This file covers the **client-side / JavaScript** half and the **REST mapping rules** that connect the two.

**Load this file when**: the user wants to register an ability from JavaScript, expose existing PHP abilities to a JS client, or needs to understand how an ability becomes an HTTP endpoint.

---

## 1. Two packages, two layers

| Package | Role |
|---|---|
| `@wordpress/abilities` | Pure JS state store. Register abilities, query the registry, execute them locally. No network. |
| `@wordpress/core-abilities` | Integration layer. Fetches **server-registered** abilities from the REST endpoint `/wp-abilities/v1/` and exposes them through the same state store. |

Import `@wordpress/abilities` for client-only abilities (e.g. a UI macro that doesn't need PHP). Import `@wordpress/core-abilities` when you want unified discovery of both client- and server-registered abilities.

---

## 2. `registerAbility` shape

```js
import { registerAbility } from '@wordpress/abilities';

registerAbility( {
    name: 'my-plugin/delete-log',          // required, namespaced kebab-case
    label: 'Delete Log',                    // required, human-readable
    category: 'maintenance',                // required, must already be registered
    description: 'Permanently delete a server log entry.', // recommended
    input_schema: {                         // JSON Schema Draft-04
        type: 'object',
        properties: { id: { type: 'number' } },
        required: [ 'id' ],
    },
    output_schema: {
        type: 'object',
        properties: { deleted: { type: 'boolean' } },
    },
    permissionCallback: () => currentUserCan( 'manage_options' ),
    callback: async ( { id } ) => {
        const response = await apiFetch( {
            path: `/my-plugin/v1/logs/${ id }`,
            method: 'DELETE',
        } );
        return { deleted: response.ok };
    },
    meta: {
        annotations: {
            readonly: false,
            destructive: true,
            idempotent: true,
        },
    },
} );
```

### Field rules

| Field | Constraint |
|---|---|
| `name` | Lowercase, namespaced (`vendor/ability-name`), kebab-case, ASCII only. Must be unique across the registry. |
| `category` | Must already be registered. Register categories on the server with `wp_register_ability_category()` or pre-register them in JS during bootstrap. |
| `input_schema` / `output_schema` | **JSON Schema Draft-04**. WP 7.0 will not accept newer dialects in the same Abilities pipeline. |
| `permissionCallback` | Synchronous, returns `boolean`. Use the JS `currentUserCan()` from `@wordpress/core-data` or your own gate. |
| `callback` | May be async. Return value must match `output_schema`. Throw or return `WP_Error`-shaped object on failure. |
| `meta.annotations` | Drives REST method mapping (see §3) and AI-agent decision making. |

---

## 3. REST method mapping rules (server-registered abilities)

Server-registered abilities (`wp_register_ability()`) automatically expose a REST route under `/wp-abilities/v1/{namespace}/{name}`. The HTTP method is **derived from the `meta.annotations` object**:

| Annotations | HTTP method |
|---|---|
| `readonly: true` | `GET` |
| `destructive: true` **AND** `idempotent: true` | `DELETE` |
| All other combinations (default) | `POST` |

Important nuances:

- The mapping is checked in the order above. `readonly: true` wins even if `destructive` is also true (don't do that — it's a contradiction).
- `destructive: true` alone (without `idempotent: true`) maps to `POST`, not `DELETE`. The idempotency guarantee is what permits `DELETE`.
- `idempotent: true` without `destructive` is allowed but has no effect on the method — it maps to `POST`.

### Choosing annotations

| Use case | `readonly` | `destructive` | `idempotent` | HTTP |
|---|---|---|---|---|
| Search posts | `true` | `false` | — | GET |
| Generate a draft (creates DB rows) | `false` | `false` | `false` | POST |
| Soft-delete a record | `false` | `true` | `true` | DELETE |
| Send an email | `false` | `false` | `false` | POST |
| Rebuild a cache index (safe to repeat) | `false` | `false` | `true` | POST |
| Hard-delete a file | `false` | `true` | `true` | DELETE |

Always annotate honestly: AI agents read these flags to decide whether to ask for confirmation before invoking an ability.

---

## 4. Permission callback symmetry

Server-side abilities use the snake-case `permission_callback`:

```php
'permission_callback' => function (): bool {
    return current_user_can( 'manage_options' );
},
```

Client-side abilities use the camel-case `permissionCallback`. This is **intentional** — the two halves run in different contexts and may diverge (e.g. a client check that hides a UI toggle, paired with a stricter server check that enforces capability on the REST call).

For server-registered abilities, the server `permission_callback` is the **authoritative gate**. The client check is purely UX.

---

## 5. Fetching server-registered abilities from JS

```js
import { useSelect } from '@wordpress/data';
import { store as abilitiesStore } from '@wordpress/abilities';

function MyAbilitiesPicker() {
    const abilities = useSelect(
        ( select ) => select( abilitiesStore ).getAbilities( { category: 'maintenance' } ),
        []
    );
    // …
}
```

The store is hydrated automatically by `@wordpress/core-abilities` when it boots. You do not need to call `fetch()` against `/wp-abilities/v1/` manually.

---

## 6. Common pitfalls

| Symptom | Cause | Fix |
|---|---|---|
| Ability returns 404 on DELETE | Missing `idempotent: true` annotation | Add it; method falls through to POST otherwise |
| `category` registration fails | Category not registered before ability | Register the category first (server: `wp_register_ability_category()`) |
| `input_schema` rejected | Used 2020-12 JSON Schema dialect | Rewrite to Draft-04 |
| AI agent ignores destructive ability | Missing `destructive: true` annotation | Annotate honestly so agents can prompt for confirmation |
| Client `currentUserCan` returns false in tests | Not running in WP admin context | Mock the capability or use a JS permission stub |
