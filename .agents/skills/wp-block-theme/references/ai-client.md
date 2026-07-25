# WP AI Client + Connectors (WP 7.0+)

A provider-agnostic PHP layer for generative AI. Separates *what* you want (prompt and requirements) from *how* it runs (which provider and model). Ships in core in WP 7.0 with OpenAI, Anthropic (Claude), and Google (Gemini) connectors out of the box.

**Load this file when**: the user asks to "call an AI model from PHP", "summarise / translate / classify with AI", "register a custom AI provider", "manage API keys", or implements anything inside an Ability's `execute_callback` that needs an LLM.

---

## 1. Pre-flight: always feature-detect first

Generation methods make network calls and consume credits. Feature-detection methods are **pure capability checks** — no API call, no latency, no cost. Always gate generation behind one.

```php
if ( ! WP_AI_Client_Prompt_Builder::is_supported_for_text_generation() ) {
    return new WP_Error(
        'no_text_provider',
        __( 'No AI provider is configured for text generation.', 'my-plugin' )
    );
}
```

| Method | Returns true when… |
|---|---|
| `is_supported_for_text_generation()` | Any active connector can produce text |
| `is_supported_for_image_generation()` | Any active connector can produce images |
| `is_supported_for_text_to_speech_conversion()` | Any active connector supports TTS |
| `is_supported_for_speech_generation()` | Any active connector supports native speech generation |
| `is_supported_for_video_generation()` | Any active connector supports video |

These are **static methods** on `WP_AI_Client_Prompt_Builder`. Call them at the call site or during admin notice rendering — not at plugin load (the connector registry may not be ready yet; wait until `init` or later).

**`wp_supports_ai()` vs. the `is_supported_for_*` checks.** `wp_supports_ai(): bool` (WP 7.0+) answers a coarser question: *is this environment capable of AI at all* (PHP/runtime requirements met). The `is_supported_for_*` methods answer *can a configured connector do this specific modality right now*. Use `wp_supports_ai()` as the outer gate (e.g. to decide whether to register an AI feature or show its UI), then the modality check at the call site. The result is filterable via the `wp_supports_ai` filter for custom environment requirements.

---

## 2. The prompt builder

Entry point: `wp_ai_client_prompt( $text = '' )` returns a `WP_AI_Client_Prompt_Builder`. Pass the prompt text directly *or* use `with_text()` later — both forms are equivalent.

### Configuration methods

| Concern | Method |
|---|---|
| Prompt text | `with_text( string $text )` |
| File input (image, audio, etc.) | `with_file( string $path_or_url, ?string $mime = null )` |
| Conversation history | `with_history( array $messages )` |
| System instruction | `using_system_instruction( string $text )` |
| Temperature | `using_temperature( float $temp )` |
| Max output tokens | `using_max_tokens( int $max )` |
| Nucleus sampling | `using_top_p( float $p )` |
| Top-k sampling | `using_top_k( int $k )` |
| Stop sequences | `using_stop_sequences( array $sequences )` |
| Preferred model | `using_model_preference( string $model )` |
| Output modalities | `as_output_modalities( array $modalities )` |
| Output file type | `as_output_file_type( string $mime )` |
| Structured JSON response | `as_json_response( array $json_schema )` |

All methods return `$this` and can be chained in any order. Validation happens at the generation call, not at configuration time.

### Generation methods

Text and image have **direct** methods that return the content itself; text-to-speech, native speech, and video are only available via the **result-form** (`*_result()`) methods, which return a `GenerativeAiResult` (call `getContent()` on it to retrieve the content). Every generation method can also return `WP_Error` — always check with `is_wp_error()` before using the result.

| Modality | Direct (single) | Direct (multiple candidates) | Result-form |
|---|---|---|---|
| Text | `generate_text()` → `string` | `generate_texts( int $n )` → `string[]` | `generate_text_result()` |
| Image | `generate_image()` → `File` DTO | `generate_images( int $n )` → `File[]` | `generate_image_result()` |
| Text-to-speech | — | — | `convert_text_to_speech_result()` |
| Native speech | — | — | `generate_speech_result()` |
| Video | — | — | `generate_video_result()` |

A `File` DTO exposes the generated image via `getDataUri()` (a data URI you can embed or save). Pass a count to `generate_texts()` / `generate_images()` to get several variations of one prompt.

---

## 3. Worked examples

### Plain text

```php
$summary = wp_ai_client_prompt()
    ->using_system_instruction( 'You are a concise editor. One paragraph max.' )
    ->using_temperature( 0.3 )
    ->using_max_tokens( 200 )
    ->with_text( "Summarise this post:\n\n" . wp_strip_all_tags( $post->post_content ) )
    ->generate_text();

if ( is_wp_error( $summary ) ) {
    error_log( 'AI summary failed: ' . $summary->get_error_message() );
    return '';
}
return $summary;
```

### Structured JSON response

Use `as_json_response()` with a JSON schema when you need the model to return data your code can parse without regex.

```php
$result = wp_ai_client_prompt( 'Summarize this post and estimate reading time in minutes.' )
    ->as_json_response( array(
        'type'       => 'object',
        'properties' => array(
            'summary'      => array( 'type' => 'string' ),
            'reading_time' => array( 'type' => 'integer' ),
        ),
        'required'   => array( 'summary', 'reading_time' ),
    ) )
    ->generate_text();
```

The model is constrained to emit valid JSON matching the schema. You still need to `json_decode()` the result.

### Token usage tracking

Use the `*_result()` form to access usage data:

```php
$result = wp_ai_client_prompt( 'Translate to French: Hello, world.' )
    ->generate_text_result();

if ( is_wp_error( $result ) ) {
    return $result;
}

$usage = $result->getTokenUsage();      // returns object with input/output/thinking counts
$provider = $result->getProviderMetadata(); // which provider actually served the request
$model    = $result->getModelMetadata();    // which model was used

log_ai_spend( $usage->input, $usage->output, $provider->name, $model->id );
```

### Image generation

Use the direct `generate_image()` for the image itself (a `File` DTO), or `generate_image_result()` when you also need provider/token metadata:

```php
$image = wp_ai_client_prompt( 'A watercolour illustration of a fox in autumn leaves.' )
    ->as_output_file_type( 'image/png' )
    ->generate_image();

if ( ! is_wp_error( $image ) ) {
    $data_uri = $image->getDataUri(); // File DTO → data URI you can embed or save
}
```

---

## 4. Connectors API

Connectors are the discovery/auth layer. Registered connectors automatically appear in **Settings → Connectors** for the site admin.

### Registering or modifying connectors

Use the `wp_connectors_init` action; it fires once with a `WP_Connector_Registry` instance.

```php
add_action( 'wp_connectors_init', function ( WP_Connector_Registry $registry ) {
    if ( $registry->is_registered( 'openai' ) ) {
        $connector = $registry->unregister( 'openai' );
        $connector['description'] = __( 'Internal OpenAI proxy (rate-limited per team).', 'my-plugin' );
        $registry->register( 'openai', $connector );
    }
} );
```

`WP_Connector_Registry` methods:
- `is_registered( string $id ): bool`
- `register( string $id, array $args ): void`
- `unregister( string $id ): array` — returns the unregistered definition (handy for modify-and-re-register patterns)

To read a single connector's definition outside the registry hook, use `wp_get_connector( string $id ): array|null` (WP 7.0+) — available after `init`, returns the connector array or `null` if it isn't registered.

**Connector `type`.** AI connectors register with `'type' => 'ai_provider'` (underscore — *not* `ai-provider`). WP 7.0 generalised the registry so `type` is no longer restricted to AI; other connector categories can register under their own type. Credential masking and key-source resolution are handled internally by core (the underscore-prefixed `_wp_connectors_*` helpers are private — do not call them); the admin UI surfaces the masked key and its source automatically.

### Authentication

Two auth methods are supported: `api_key` and `none`. Most providers use `api_key`.

### Credential resolution priority

The Connectors layer resolves the API key for `api_key` connectors in this **strict order**, first match wins:

1. **Environment variable** — e.g. `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `GOOGLE_API_KEY`. Set in your server's environment or `.env` file loaded by your stack.
2. **PHP constant** — `define( 'OPENAI_API_KEY', '…' );` typically in `wp-config.php`.
3. **Database option** — set by the admin at **Settings → Connectors**. Stored under the general pattern `connectors_{type}_{id}_api_key`. In WP 7.0 the type segment is always `ai`, so in practice the key is `connectors_ai_{$id}_api_key` (e.g. `connectors_ai_openai_api_key`). The `{type}` placeholder is reserved for future connector categories beyond AI.

The admin screen shows which source supplied the active key (env / constant / DB) per connector. Database keys are masked in the REST API to prevent leakage.

**Where to set keys, by environment**

| Environment | Recommended source | Why |
|---|---|---|
| Local dev | env var via `.env` or shell | Not committed; per-developer |
| Staging | PHP constant in environment-specific config | Loaded with `wp-config-staging.php` |
| Production | env var (managed secret) | Secret manager integration; no DB exposure |
| Demo / client-managed | DB via Settings → Connectors UI | Non-technical user can rotate keys |

### Third-party providers

Plugins can register their own connector inside `wp_connectors_init` by calling `$registry->register( 'my-provider', [...] )` with a definition that includes the auth method, capability flags, and an SDK adapter class. The new provider then becomes available to every `wp_ai_client_prompt()` call.

**Auto-discovery:** providers registered via `AiClient::defaultRegistry()` are automatically discovered by WordPress and integrated into the **Settings → Connectors** admin UI — no extra step needed. The `wp_connectors_init` hook is the correct place to call this; the default registry is populated by core before the hook fires.

---

## 5. Governance: cost control & timeouts

Two filters (WP 7.0+) let you intercept generation before it bills tokens or hangs — the right place for rate-limiting, budget caps, and tuning slow providers.

| Filter | Use |
|---|---|
| `wp_ai_client_prevent_prompt` | Return `true` to block a prompt from executing. The callback receives a read-only clone of the prompt builder, so you can inspect the request (model, text length) and deny based on a budget or per-user quota. The blocked call returns `WP_Error` — handle it like any other failure. |
| `wp_ai_client_default_request_timeout` | Filters the default HTTP timeout (a `float`, in seconds) applied to AI Client requests. Raise it for slow image/video providers; lower it to fail fast in latency-sensitive paths. |

```php
// Hard budget cap: stop new prompts once today's spend is exceeded.
add_filter( 'wp_ai_client_prevent_prompt', function ( bool $prevent, $builder ): bool {
    if ( get_transient( 'ai_budget_exceeded' ) ) {
        return true; // generation returns WP_Error to the caller
    }
    return $prevent;
}, 10, 2 );
```

> **Not in WP 7.0:** the prompt builder has **no** `using_abilities()` method, `set_model()`, or `with_options()`, and there is no documented snake→camel `__call` magic. Configure via the explicit `using_*` / `as_*` methods in §2. To expose plugin capabilities to AI agents, register **Abilities** (`references/abilities-api.md`) — that is the WP 7.0 surface for AI-invokable functions, separate from the prompt builder.

---

## 6. Common pitfalls

| Symptom | Cause | Fix |
|---|---|---|
| `WP_Error: no_active_connector` | No connector configured at Settings → Connectors | Surface the message to the admin; do not retry |
| Always falls back to one provider | `using_model_preference()` requested a model none of the active connectors support | Drop the preference or add a matching connector |
| JSON response not parseable | Forgot `as_json_response()` | Add it with a schema; the model will then be constrained |
| Token usage missing | Called `generate_text()` instead of `generate_text_result()` | Use the `_result()` variant when you need metadata |
| Feature detection returns false in plugin file | Called too early — registry not built | Move the check inside an `init` (or later) hook |
| API key not picked up | Env var set after PHP-FPM started | Restart PHP-FPM; env vars are read at process start |
