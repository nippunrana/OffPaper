# DataViews & DataForms (WP 7.0+)

DataViews powers admin-style listings (the Pages list, the Patterns list, plugin order tables, etc.). DataForms is the matching form layer. Both consume **field definitions** that drive rendering, validation, and formatting.

**Load this file when**: the user is building or modifying a DataViews-driven admin screen, wants custom validation on a field, asks about `groupBy` / `onReset`, or needs `getValueFormatted` to display a different value than what's stored.

---

## 1. `groupBy` is now an object

The legacy string form (`groupBy: 'status'`) is **deprecated**. Always emit the object form:

```js
view = {
    type: 'table',
    groupBy: {
        field: 'status',         // required — the field id to group by
        direction: 'desc',       // optional, 'asc' | 'desc'. Defaults to 'asc'.
        label: 'Group by status' // optional, overrides the auto-generated UI label
    },
    // …
};
```

If you encounter old code with `groupBy: 'status'`, migrate it. Don't pass both forms — the object wins but emits a deprecation warning.

---

## 2. `onReset` semantics

The "Reset view" button in the view controls is governed by the `onReset` prop. Pick the value that matches the desired UX state:

| `onReset` value | Button state | Use when… |
|---|---|---|
| `undefined` | Hidden | The view is not persisted (every page load starts from defaults), so there is nothing to reset. |
| `false` | Visible but disabled | View persistence is active **and** the current view already matches defaults. The disabled state confirms to the user that there are no customisations to clear. |
| `function` | Active | View persistence is active **and** the current view diverges from defaults. The function is invoked when the user clicks "Reset view". |

Drive the value reactively from your store state:

```js
const isAtDefaults = useSelect( ( s ) => s( store ).isViewAtDefaults() );
const persistsView = true; // your decision

const onReset = ! persistsView
    ? undefined
    : isAtDefaults
        ? false
        : () => dispatch( store ).resetView();
```

---

## 3. Field API validation rules

Declarative validation moved into the field definition in WP 7.0. Rules align with JSON Schema vocabulary and run client-side before the form is submitted; server-side enforcement is still your responsibility on the REST handler.

| Rule | Supported field types | Notes |
|---|---|---|
| `pattern` | `text`, `email`, `tel`, `url` | Standard JS regex source (no leading/trailing `/`). |
| `minLength` / `maxLength` | `text`, `email`, `tel`, `url` | Inclusive bounds. |
| `min` / `max` | `integer`, `number` | Inclusive bounds. |

```js
const fields = [
    {
        id: 'order_id',
        label: 'Order ID',
        type: 'text',
        validation: {
            pattern: '^ORD-[0-9]{6}$',
            minLength: 10,
            maxLength: 10,
        },
    },
    {
        id: 'quantity',
        label: 'Quantity',
        type: 'integer',
        validation: { min: 1, max: 999 },
    },
];
```

Validation errors are surfaced through the form's `errors` state. Combine rules freely — they evaluate independently.

---

## 4. `getValueFormatted` for display logic

Field definitions accept `getValueFormatted( item, field )` to derive the visible string from the stored value. The stored value goes to the REST API and the database; the formatted value goes to the screen.

```js
{
    id: 'file_size',
    label: 'Size',
    type: 'integer',
    getValueFormatted: ( item ) => {
        const bytes = item.file_size;
        if ( bytes < 1024 ) return `${ bytes } B`;
        if ( bytes < 1024 * 1024 ) return `${ ( bytes / 1024 ).toFixed( 1 ) } KB`;
        return `${ ( bytes / ( 1024 * 1024 ) ).toFixed( 1 ) } MB`;
    },
}
```

Common use cases:
- Bytes → human-readable size
- Unix timestamps → locale-aware date strings (`new Date( item.created_at ).toLocaleDateString()`)
- Status codes → translated labels
- Role slugs → role display names

Do **not** put filtering or sorting logic in `getValueFormatted` — that operates on the raw value, not the formatted one. Sorting a column displays raw values in sort order, then renders each with the formatter.

---

## 5. Common pitfalls

| Symptom | Cause | Fix |
|---|---|---|
| Deprecation warning on `groupBy` | Used legacy string form | Switch to the object form with `field` / `direction` / `label` |
| "Reset view" button always hidden | `onReset` left as `undefined` | Set `false` (disabled) or a function once the view is persisted |
| Validation passes but server rejects | Only client-side rules added | Mirror the rules on your REST handler — client validation is UX, not security |
| Formatted value disappears on sort | Put sort/filter logic in `getValueFormatted` | Sort uses the raw value; keep formatting purely presentational |
| Regex pattern silently invalid | Wrapped pattern in `/…/` | Pass the source string only, e.g. `^ORD-[0-9]{6}$` |
