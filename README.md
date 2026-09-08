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
6. Copy `config.php` and add your Gemini API key (get one free at https://aistudio.google.com/apikey).
7. Open `http://localhost/smart-slope/login.php`.

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