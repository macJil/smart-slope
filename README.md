# Smart Slope V2

A web-based landslide risk monitoring and early warning prototype for selected areas in Baguio City, Philippines.

## What It Does

Smart Slope V2 collects environmental data (rainfall, humidity, temperature, pressure, wind, soil moisture), calculates a transparent 0–100 risk score using a PHP rule engine, stores the results in a relational database, and visualizes them on a responsive dashboard. Google Gemini AI provides on-demand explanations of the calculated risk.

## Features

- User authentication with role-based access (admin, CDRRMO staff, viewer)
- Location CRUD — add, edit, delete monitoring locations
- Live weather from Open-Meteo API
- Transparent PHP RiskEngine (no black-box ML)
- Risk score 0–100 with four levels (Low, Moderate, High, Critical)
- AI analysis via Google Gemini (on-demand, server-side)
- CSV import and export for telemetry data
- Chart.js visualizations (risk history, rainfall, temperature/humidity)
- AJAX-powered dashboard (jQuery)
- CSRF protection, prepared statements, XSS escaping

## Technologies

| Category | Stack |
|----------|-------|
| Backend | PHP 8.2+, PDO, MariaDB/MySQL |
| Frontend | HTML5, CSS3, Bootstrap 5, jQuery, Chart.js |
| Weather API | Open-Meteo (free, no key) |
| AI | Google Gemini API (server-side) |

## Installation

1. Clone the repository and switch to the `simplified_v` branch.
2. Start XAMPP (Apache + MySQL).
3. Import `db_schema.sql` via phpMyAdmin (creates `landslide_db`).
4. Run `register_admin.php` once to create your admin account.
5. Delete `register_admin.php`.
6. Configure the AI provider as described below, then restart Apache.
7. Open `http://localhost/smart-slope/login.php`.

## AI API Setup on macOS XAMPP

The application reads keys from the Apache environment. Do not paste real keys into
`config.php`, commit them to Git, or put them in browser JavaScript. The keys previously
shown in `test-ai.php` should be revoked and regenerated.

### 1. Create a provider key

You only need one provider. Groq is a good first choice because it was working during
testing and has a free tier.

| Provider | Create credentials at | Required variables |
|----------|------------------------|--------------------|
| Groq | https://console.groq.com/keys | `GROQ_API_KEY` |
| Gemini | https://aistudio.google.com/apikey | `GEMINI_API_KEY` |
| Grok | https://console.x.ai/ | `GROK_API_KEY` |
| Mistral | https://console.mistral.ai/api-keys/ | `MISTRAL_API_KEY` |
| Cerebras | https://cloud.cerebras.ai/ | `CEREBRAS_API_KEY` |
| Cloudflare | Cloudflare Dashboard > AI > Workers AI | `CLOUDFLARE_ACCOUNT_ID`, `CLOUDFLARE_API_TOKEN` |
| OpenRouter | https://openrouter.ai/keys | `OPENROUTER_API_KEY` |
| Hugging Face | https://huggingface.co/settings/tokens | `HF_API_TOKEN` |

For Cloudflare, the account ID is not the API token. Create a token with permission to
run Workers AI models.

### 2. Add the key to Apache

Open the Apache configuration file:

`/Applications/XAMPP/etc/httpd.conf`

Add the required `SetEnv` lines near the end of the file. Replace only the placeholder
values with newly generated credentials:

```apache
SetEnv GROQ_API_KEY "replace-with-your-groq-key"
SetEnv GROQ_MODEL "openai/gpt-oss-120b"
```

Use these optional blocks for other providers:

```apache
SetEnv GEMINI_API_KEY "replace-with-your-gemini-key"
SetEnv GEMINI_MODEL "gemini-3.6-flash"

SetEnv GROK_API_KEY "replace-with-your-grok-key"
SetEnv GROK_MODEL "grok-2"

SetEnv MISTRAL_API_KEY "replace-with-your-mistral-key"
SetEnv MISTRAL_MODEL "mistral-small-latest"

SetEnv CEREBRAS_API_KEY "replace-with-your-cerebras-key"
SetEnv CEREBRAS_MODEL "gpt-oss-120b"

SetEnv CLOUDFLARE_ACCOUNT_ID "replace-with-your-account-id"
SetEnv CLOUDFLARE_API_TOKEN "replace-with-your-cloudflare-token"
SetEnv CLOUDFLARE_MODEL "@cf/meta/llama-3.1-8b-instruct"

SetEnv OPENROUTER_API_KEY "replace-with-your-openrouter-key"
SetEnv OPENROUTER_MODEL "meta-llama/llama-3.1-8b-instruct"

SetEnv HF_API_TOKEN "replace-with-your-huggingface-token"
SetEnv HF_MODEL "meta-llama/Llama-3.1-8B-Instruct"
```

Only configure providers you intend to use. Empty variables are skipped automatically.
The application tries providers in this order: Grok, Gemini, Groq, Mistral, Cerebras,
Cloudflare, OpenRouter, Hugging Face, then the built-in Default Engine.

### 3. Restart Apache

In XAMPP Manager, stop Apache and start it again. A browser refresh alone does not
reload `httpd.conf`. If Apache refuses to start, remove the last `SetEnv` line and
check `/Applications/XAMPP/logs/error_log` for the configuration error.

### 4. Verify the key is being used

1. Sign in at `http://localhost/smart-slope/login.php`.
2. Open the dashboard.
3. Trigger an AI analysis from the latest or manual prediction reading.
4. Check the provider label in the AI Analysis panel. It should say `Groq`, `Gemini (Google)`, or another configured provider, not `Default Engine`.

If it still says `Default Engine`, check the following:

- Apache was restarted after editing `httpd.conf`.
- The variable name exactly matches the names above.
- The key has not expired or been revoked.
- The selected model is available to that account.
- The provider has not returned a rate-limit, quota, or billing error.
- `allow_url_fopen` is enabled and OpenSSL is loaded in XAMPP PHP.

The configured model can be changed without editing PHP by changing its matching
`*_MODEL` variable in `httpd.conf` and restarting Apache.

## Database

Three tables:

Relationship: `sensor_nodes 1 — N telemetry_logs` (ON DELETE CASCADE).

## Important Limitations

This is an **educational prototype**, not a certified landslide warning system.
The risk score is a simplified environmental model using predefined rules —
not a scientifically validated landslide prediction model.

## Course Alignment

- **WEBSYS1**: PHP, PDO CRUD, OOP, jQuery/AJAX, REST/JSON, security (SQLi, XSS, CSRF, password hashing)
- **IMDBSE2**: Web app, functional frontend + backend, relational database, CRUD
- **DICT PSC XI**: AI (Gemini), IoT-ready schema (ESP32 source), SDG 11

## License

Educational use. © 2026 Smart Slope Team.