# Smart Slope
## Complete Project and Prototype Documentation

**Project:** Smart Slope - IoT/API Hybrid Landslide Early Warning System  
**Initial monitoring area:** Barangay Loay, Baguio City  
**Current prototype locations:** Barangay Loay, Barangay Pinget, and Barangay Gibraltar  
**Environment:** XAMPP Apache + MariaDB + PHP 8.2 + Python 3  
**Documentation status:** Prototype-ready and presentation-ready  
**Last reviewed:** September 7, 2026

For GitHub publishing, follow [GITHUB_PUSH_GUIDE.md](GITHUB_PUSH_GUIDE.md). The guide covers repository creation, Git setup, security review, first push, future updates, and cloning the prototype.

---

## 1. Executive Summary

Smart Slope is a PHP/MySQL web application for monitoring weather conditions associated with landslide risk. It is designed as a prototype for local disaster-risk and government operations teams.

The system currently supports:

- Three selectable monitoring locations in Baguio City.
- Weather collection from the Open-Meteo API.
- Location-specific telemetry storage in MariaDB.
- A dashboard with risk status, charts, and latest readings.
- A Python Random Forest prediction model.
- A documented rainfall-threshold fallback when the Apache/Python architecture is incompatible.
- Login protection for the dashboard.
- CSV import and export for demonstration and historical data handling.
- Safe removal of CSV-imported records without deleting live API readings.
- Local Bootstrap, jQuery, and Chart.js assets so the user interface does not depend on CDN availability.

The prototype is intended to help staff answer four operational questions quickly:

1. Which monitoring location is being viewed?
2. What is the current risk level?
3. What are the latest weather readings?
4. What action or further review is needed?

The main dashboard is designed for automatic monitoring. It reads the latest telemetry for the selected location and shows the current alert. The manual prediction endpoint remains available for technical testing, but it is not part of the normal staff workflow.

The dashboard also includes an optional **Manual presentation mode**. This allows a presenter to enter hypothetical weather values and demonstrate a Low Risk or High Risk result without changing the stored telemetry. The automatic alert and manual demonstration result are separate.

### Important operational description

This is an early-warning prototype, not a certified disaster-warning system. Its results should support, not replace, official assessment, PAGASA information, geotechnical review, and the decisions of authorized emergency officials.

---

## 2. Main Features

### 2.1 Location monitoring

The dashboard currently provides three locations:

| Node ID | Location | Latitude | Longitude | Status |
|---:|---|---:|---:|---|
| 1 | Barangay Loay, Baguio City | 16.4173 | 120.5963 | Active |
| 2 | Barangay Pinget, Baguio City | 16.4230 | 120.5900 | Active |
| 3 | Barangay Gibraltar, Baguio City | 16.4078 | 120.6000 | Active |

A user selects a location from the dashboard. The selected node controls:

- Which telemetry readings are displayed.
- Which risk history is displayed.
- Which coordinates are sent to Open-Meteo.
- Which node receives the new weather record.

### 2.2 Weather ingestion

The system requests current values from Open-Meteo without requiring an API key. It stores:

- Rainfall
- Relative humidity
- Air pressure
- Temperature
- Wind speed
- Data source
- Location node
- Timestamp

### 2.3 Risk prediction

The prediction input uses five features:

- `rainfall_mm`
- `humidity`
- `pressure`
- `temperature`
- `wind_speed`

The result is represented as:

- `0`: Low Risk
- `1`: High Risk

The dashboard displays the result with plain-language guidance and a color-coded status.

### 2.4 Dashboard visualization

The dashboard contains:

- Current risk gauge.
- Risk history chart.
- Rainfall-over-time chart.
- Temperature and humidity chart.
- Latest readings table.
- Data connection status.
- Latest reading timestamp.

### 2.5 File handling

Authorized users can:

- Upload telemetry records from CSV.
- Download stored telemetry as CSV.

---

## 3. System Architecture

```text
                         +----------------------+
                         |   Government Staff   |
                         |   Browser Dashboard  |
                         +----------+-----------+
                                    |
                           PHP pages and AJAX
                                    |
        +---------------------------+---------------------------+
        |                           |                           |
        v                           v                           v
+---------------+          +----------------+          +----------------+
| index.php     |          | data.php       |          | predict.php    |
| Dashboard     |          | JSON telemetry |          | Prediction API |
+-------+-------+          +--------+-------+          +--------+-------+
        |                            |                         |
        |                            v                         v
        |                    +---------------+          +-------------+
        |                    | MariaDB       |          | predict.py   |
        |                    | landslide_db  |          | Random Forest|
        |                    +---------------+          +-------------+
        |
        v
+------------------+       +------------------+
| fetch_weather.php| ----> | Open-Meteo API  |
+------------------+       +------------------+
```

### Data flow

1. A user signs in through `login.php`.
2. `auth.php` protects the dashboard and other private pages.
3. The dashboard loads a selected `node_id`.
4. JavaScript requests `data.php?node_id=<id>`.
5. PHP reads telemetry records from MariaDB.
6. The dashboard renders charts and the latest readings table.
7. `fetch_weather.php?node_id=<id>` requests the selected location's current weather.
8. The result is stored in `telemetry_logs` with `source = API`.
9. `predict.php` sends five values to `predict.py`.
10. `predict.py` loads `landslide_model.pkl` and returns JSON.
11. If Apache cannot load the Python model because of an architecture mismatch, PHP returns the documented rainfall-threshold fallback.

---

## 4. Technology Stack

| Layer | Technology | Purpose |
|---|---|---|
| Web server | XAMPP Apache | Serves PHP pages locally |
| Backend | PHP 8.2 | Pages, authentication, APIs, database operations |
| Database | MariaDB/MySQL | Stores users, locations, and telemetry |
| Database access | PDO | Prepared statements and database abstraction |
| Frontend framework | Bootstrap 5.3.8 | Responsive government-facing layout |
| Client requests | jQuery 3.7.1 | AJAX requests to PHP endpoints |
| Charts | Chart.js 4.x | Risk and weather charts |
| Weather source | Open-Meteo | Current weather data |
| Machine learning | Python, pandas, NumPy, scikit-learn | Data generation, model training, prediction |
| Model persistence | joblib | Saves and loads the Random Forest model |
| Local environment | macOS + XAMPP | Development and demonstration environment |

All browser libraries used by the application are stored locally under `assests/`. The directory name is intentionally preserved for compatibility, although `assets/` would normally be the preferred spelling.

---

## 5. Project Structure

```text
smart_slope/
├── PROJECT_DOCUMENTATION.md       # This complete documentation
├── GITHUB_PUSH_GUIDE.md            # Step-by-step GitHub publishing guide
├── TESTING.md                     # Repeatable browser and command-line test guide
├── .gitignore                     # Git exclusions for local/runtime files
├── config.php                    # PDO database connection
├── db_schema.sql                 # Database, tables, indexes, and three seed locations
├── auth.php                      # Login/session guard
├── login.php                     # Login form and password verification
├── logout.php                    # Destroys the current session
├── register_admin.php            # One-time demo admin account creator
├── index.php                     # Protected monitoring dashboard
├── data.php                      # Location-filtered JSON telemetry endpoint
├── fetch_weather.php             # Open-Meteo ingestion for a selected node
├── predict.php                   # JSON prediction endpoint and fallback logic
├── predict.py                    # Loads the model and predicts one result
├── generate_data.py              # Creates synthetic model training data
├── train_model.py                # Trains and saves the Random Forest model
├── landslide_model.pkl           # Serialized trained model
├── import_csv.php                # Authenticated CSV upload page
├── export_csv.php                # Authenticated CSV download endpoint
├── sample_telemetry.csv          # Three-row import demonstration file
├── smart-slope-complete-project-documentation.pdf
│                                # Original supplied project documentation
├── i.txt                         # Original file-structure notes
├── data/
│   └── historical_weather.csv    # Model training dataset
├── classes/
│   ├── SensorNode.php            # Location CRUD class
│   └── Telemetry.php             # Telemetry insert and retrieval class
└── assests/
    ├── css/
    │   ├── bootstrap.min.css     # Local Bootstrap production stylesheet
    │   ├── bootstrap.css         # Local Bootstrap readable stylesheet
    │   ├── bootstrap*.css         # Bootstrap grid, reboot, utility, and RTL variants
    │   ├── *.css.map              # Bootstrap source maps for development tools
    │   └── style.css              # Application-specific styles
    └── js/
        ├── bootstrap.bundle.min.js# Local Bootstrap bundle with Popper
        ├── bootstrap*.js          # Bootstrap readable, minified, ESM, and map files
        ├── jquery-3.7.1.min.js    # Local jQuery runtime
        ├── chart.umd.min.js       # Local Chart.js runtime
        └── script.js              # Dashboard AJAX and chart logic
```

### Files not used by the application

- `.DS_Store` is a macOS Finder metadata file and has no application purpose.
- Bootstrap source maps are development-support files, not application logic.
- The supplied PDF and `i.txt` are reference documentation rather than runtime files.

---

## 6. File-by-File Explanation

### `config.php`

Creates the shared PDO connection to the `landslide_db` database.

Responsibilities:

- Uses `localhost` as the database host.
- Uses the `root` XAMPP account by default.
- Uses UTF-8 (`utf8mb4`).
- Enables `PDO::ERRMODE_EXCEPTION`.
- Sets associative-array fetch mode.

Prototype configuration:

```php
$host = 'localhost';
$dbname = 'landslide_db';
$username = 'root';
$password = '';
```

For production, credentials must be moved outside the public web directory and changed from the default XAMPP configuration.

### `db_schema.sql`

Creates the database and three tables:

- `sensor_nodes`: monitoring locations and coordinates.
- `telemetry_logs`: weather and sensor readings.
- `users`: login accounts and roles.

It also creates indexes for telemetry time and node/time queries and inserts the three current monitoring locations if they do not already exist.

The script is safe to rerun for the seed locations because it uses `WHERE NOT EXISTS`.

### `auth.php`

Starts the PHP session and checks for `$_SESSION['user_id']`. Unauthenticated users are redirected to `login.php`.

It is included at the top of protected pages such as:

- `index.php`
- `import_csv.php`
- `export_csv.php`

### `login.php`

Provides the staff login page and handles authentication.

Process:

1. Reads the submitted username and password.
2. Retrieves the account with a prepared statement.
3. Verifies the password with `password_verify()`.
4. Stores user ID, username, and role in the session.
5. Redirects successful users to the dashboard.

Invalid credentials produce a user-friendly error message.

### `logout.php`

Destroys the active session and redirects the user to the login page.

### `register_admin.php`

Creates the prototype administrator account:

```text
Username: admin
Password: admin123
```

Run this only during local setup. Delete or protect this file after creating the first account. The default password must be changed before any real deployment.

### `index.php`

The main protected dashboard.

Responsibilities:

- Loads the available sensor nodes.
- Reads `node_id` from the query string.
- Selects the requested valid location or defaults to the first location.
- Displays the location selector.
- Links the weather update action to the selected node.
- Loads local Bootstrap, jQuery, Chart.js, and application assets.
- Displays the automatic current alert, risk gauge, and telemetry visualizations.

Example location URL:

```text
http://localhost/smart_slope/index.php?node_id=2
```

### `data.php`

A JSON endpoint for the dashboard.

Request:

```text
GET /smart_slope/data.php?node_id=1
```

Successful response: a JSON array of telemetry rows for the selected node.

Invalid or missing node:

- HTTP status `400`.
- JSON error: `Select a valid monitoring location.`

The endpoint uses `Telemetry::getByNode()` and does not mix readings between locations.

### `fetch_weather.php`

Requests current weather for a selected node from Open-Meteo and inserts a telemetry record.

Request:

```text
GET /smart_slope/fetch_weather.php?node_id=1
```

The endpoint:

1. Validates the selected node.
2. Reads its latitude and longitude from `sensor_nodes`.
3. Calls Open-Meteo.
4. Validates that a current weather response exists.
5. Inserts the values with `source = API`.
6. Displays a success page naming the selected location.

If Open-Meteo is unavailable or returns invalid data, the endpoint returns a clear temporary-unavailable message rather than inserting incomplete data.

### `predict.php`

Receives JSON and returns a prediction JSON response.

Required fields:

```json
{
  "rainfall_mm": 10,
  "humidity": 60,
  "pressure": 1015,
  "temperature": 22,
  "wind_speed": 2
}
```

Normal model response:

```json
{"prediction":0}
```

Fallback response when Apache cannot load the Python model:

```json
{
  "prediction": 1,
  "fallback": true,
  "message": "Prototype mode: rainfall threshold assessment used."
}
```

The fallback is intentionally transparent. It uses the same prototype rule used to generate the synthetic labels:

- Rainfall above `100 mm`: High Risk.
- Rainfall at or below `100 mm`: Low Risk.

### `predict.py`

Loads `landslide_model.pkl`, reads JSON from standard input, extracts the five model features, and prints a JSON prediction.

The model path can be supplied as a command-line argument. Otherwise, it defaults to the model beside the script.

### `generate_data.py`

Creates `data/historical_weather.csv` with 1,000 synthetic daily weather rows.

Generated feature ranges include:

- Rainfall from an exponential distribution.
- Humidity between 60 and 95 percent.
- Pressure between 1000 and 1020 hPa.
- Temperature between 15 and 30 degrees Celsius.
- Wind speed between 0 and 10 m/s.

The label is generated as `1` when rainfall exceeds 100 mm, otherwise `0`.

This data is suitable for demonstrating the pipeline, but it is not a substitute for a validated landslide dataset.

### `train_model.py`

Trains a `RandomForestClassifier` using the five weather features.

Process:

1. Loads `data/historical_weather.csv`.
2. Splits data into 80 percent training and 20 percent testing.
3. Trains a 100-tree Random Forest.
4. Prints accuracy and a classification report.
5. Saves the model to `landslide_model.pkl`.

The reported accuracy must not be presented as real-world landslide prediction accuracy because the current labels are synthetic and derived directly from rainfall.

### `classes/SensorNode.php`

Provides object-oriented CRUD methods for monitoring locations:

- `create()`
- `getAll()`
- `getById()`
- `update()`
- `delete()`

The current dashboard uses `getAll()` and `getById()`.

### `classes/Telemetry.php`

Provides object-oriented telemetry operations:

- `insert()` stores a telemetry row.
- `getLatest()` returns recent readings across nodes.
- `getByNode()` returns recent readings for one location.
- `deleteOlderThan()` supports future retention cleanup.

Queries use prepared statements and integer-bound limit values.

### `import_csv.php`

Authenticated upload page for historical or sample telemetry.

Expected column order:

```text
node_id, soil_moisture, rainfall_mm, humidity, pressure, temperature, wind_speed, source, timestamp
```

It skips the header row and inserts each remaining row with a prepared statement. When opened from a selected dashboard location, all uploaded rows are assigned to that selected node so the upload and refresh workflow cannot accidentally display data under a different location.

For a production version, this page should add stricter file-size, MIME, column-count, numeric-range, and row-level validation.

### `export_csv.php`

Authenticated download endpoint.

It:

- Sends a CSV content type.
- Creates a timestamped filename.
- Writes telemetry headers.
- Exports stored rows ordered by newest timestamp.

When opened from the dashboard, the export is filtered to the selected node and receives a location-specific filename. Without a node filter, it exports all locations.

### `clear_imported.php`

Authenticated cleanup page for imported records. It deletes only rows where `source = CSV` for the selected monitoring node. Live Open-Meteo rows (`API`) and future ESP32 rows are preserved.

This separation is important for demonstrations: staff can upload sample data, create a report, and then remove the sample data without deleting current weather history.

### `assests/css/style.css`

Contains the application-specific styles layered on top of Bootstrap:

- Light operations-dashboard background.
- Simple card borders and spacing.
- Responsive dashboard heading.
- Chart heights.
- Mobile adjustments.
- Readable table headers.

### `assests/js/script.js`

Controls the interactive dashboard.

Responsibilities:

- Requests `data.php?node_id=<selected node>`.
- Updates connection status and latest reading time.
- Builds rainfall, temperature/humidity, and risk history charts.
- Builds the current risk gauge.
- Populates the latest readings table.
- Provides the automatic alert from the selected location's latest telemetry. The `predict.php` endpoint remains available for technical testing.
- Includes an optional manual presentation form that sends hypothetical values to `predict.php` without inserting them into the database.
- Reloads the selected location when the location selector changes.
- Refreshes telemetry every five minutes.

### Local vendor assets

#### Bootstrap

The Bootstrap files provide the responsive layout, forms, navigation, cards, alerts, tables, and mobile navigation behavior. `bootstrap.bundle.min.js` includes the JavaScript bundle needed by the responsive navbar.

#### jQuery

`jquery-3.7.1.min.js` provides the AJAX and DOM helpers used by `script.js`.

#### Chart.js

`chart.umd.min.js` renders the dashboard charts and risk gauge.

The application uses local copies so the prototype continues to work when internet access is unavailable. Open-Meteo still requires network access for live weather updates.

### `sample_telemetry.csv`

A three-row demonstration file containing low- and high-rainfall records for node 1. It is used to demonstrate CSV import.

### `data/historical_weather.csv`

The generated model-training dataset. It should be regenerated only when intentionally retraining the demonstration model.

### `landslide_model.pkl`

Serialized Random Forest model produced by `train_model.py`. It is a binary artifact and should not be manually edited.

### `TESTING.md`

Repeatable browser and command-line testing instructions for setup, login, locations, weather, prediction, charts, CSV, and access control.

### `smart-slope-complete-project-documentation.pdf`

Original supplied documentation. It is useful as project background, but this Markdown file is the current implementation reference because it reflects later changes such as three locations, local frontend assets, and the prediction fallback.

### `i.txt`

Original project file-structure notes. It describes the initial structure and may mention the old `assets` spelling. The live project uses `assests` for compatibility with the current files.

---

## 7. Database Design

### `sensor_nodes`

| Column | Purpose |
|---|---|
| `node_id` | Primary key for a monitoring location |
| `location_name` | Human-readable location name |
| `latitude` | Open-Meteo latitude |
| `longitude` | Open-Meteo longitude |
| `status` | `active`, `inactive`, or `maintenance` |
| `created_at` | Location creation time |

### `telemetry_logs`

| Column | Purpose |
|---|---|
| `log_id` | Primary key |
| `node_id` | Foreign key to `sensor_nodes` |
| `soil_moisture` | Reserved for future ESP32 sensor data |
| `rainfall_mm` | Rainfall value used in current prediction |
| `humidity` | Relative humidity |
| `pressure` | Air pressure |
| `temperature` | Temperature |
| `wind_speed` | Wind speed |
| `source` | `API`, `ESP32`, or `CSV` |
| `timestamp` | Reading time |

### `users`

| Column | Purpose |
|---|---|
| `user_id` | Primary key |
| `username` | Unique login name |
| `password_hash` | Secure password hash |
| `full_name` | Staff member display name |
| `role` | `admin`, `cdrrmo_staff`, or `viewer` |
| `created_at` | Account creation time |

### Relationships

```text
sensor_nodes 1 ---- many telemetry_logs
users         independent authentication table
```

Deleting a sensor node cascades to its telemetry rows. In a production system, soft deletion or archival should be considered instead of deleting historical emergency data.

---

## 8. Installation and Setup

### Requirements

- macOS, Windows, or Linux.
- XAMPP with Apache, PHP, and MariaDB/MySQL.
- Python 3.
- Python packages:

```bash
python3 -m pip install pandas numpy scikit-learn joblib
```

- Internet access for Open-Meteo weather requests.

### Local setup

1. Start **Apache** and **MySQL** in XAMPP.
2. Confirm the project is located at:

```text
/Applications/XAMPP/xamppfiles/htdocs/smart_slope
```

3. Open phpMyAdmin or the XAMPP MySQL client.
4. Import `db_schema.sql`.
5. Create the demo administrator once:

```text
http://localhost/smart_slope/register_admin.php
```

6. Delete or protect `register_admin.php` after account creation.
7. Open:

```text
http://localhost/smart_slope/login.php
```

### Optional model regeneration

From the project directory:

```bash
python3 generate_data.py
python3 train_model.py
```

This overwrites the demonstration dataset and `landslide_model.pkl`.

### PHP syntax check

The XAMPP PHP binary is commonly located at:

```bash
/Applications/XAMPP/xamppfiles/bin/php
```

Run:

```bash
PHP_BIN=/Applications/XAMPP/xamppfiles/bin/php
for file in *.php classes/*.php; do "$PHP_BIN" -l "$file" || exit 1; done
```

---

## 9. Presentation Demonstration Script

Use this sequence for a clear government or academic presentation.

### Demonstration 1: Secure access

1. Open the login page.
2. Sign in as the authorized demo user.
3. Explain that the dashboard is protected by a session guard.

### Demonstration 2: Location selection

1. Select Barangay Loay.
2. Show its current readings.
3. Select Barangay Pinget.
4. Show that the node-specific readings change.
5. Select Barangay Gibraltar.
6. Explain that the same dashboard can monitor multiple locations without mixing records.

### Demonstration 3: Live weather update

1. Select a location.
2. Click **Update weather**.
3. Point out that the selected node's coordinates are used.
4. Return to the dashboard and show the updated timestamp and reading.

### Demonstration 4: Low-risk scenario

Use the latest reading for a location with rainfall at or below 100 mm. Explain that the dashboard reports Low Risk automatically.

### Demonstration 5: High-risk scenario

Use a latest reading with rainfall above 100 mm. Explain that the dashboard reports High Risk automatically and displays response guidance.

### Demonstration 6: Accountability data

1. Show the latest readings table.
2. Show the risk history and rainfall charts.
3. Upload `sample_telemetry.csv`.
4. Download the telemetry report.
5. Explain that the database retains the source and monitoring node for traceability.

---

## 10. Verification Results

The prototype has been checked locally with the following results:

| Check | Result |
|---|---|
| PHP syntax for application files | Passed |
| JavaScript syntax | Passed |
| Login redirect | Passed |
| Protected dashboard access | Passed |
| Dashboard HTTP response | HTTP 200 |
| Data API node 1 | HTTP 200, JSON array |
| Data API node 2 | HTTP 200, JSON array |
| Data API node 3 | HTTP 200, JSON array |
| Invalid node handling | HTTP 400 with JSON error |
| Weather update node 1 | Passed |
| Weather update node 2 | Passed |
| Weather update node 3 | Passed |
| Low-risk prediction | `prediction: 0` |
| High-risk prediction | `prediction: 1` |
| CSV import | 3 records imported successfully |
| CSV export | HTTP 200 and valid CSV download |
| Local Bootstrap assets | Present and used |
| CDN references in PHP pages | None |

---

## 11. Security Measures

Current protections:

- PDO prepared statements for database input.
- `password_hash()` and `password_verify()` for passwords.
- Session-based authentication for private pages.
- `htmlspecialchars()` for displayed session and location values.
- Integer validation for selected node IDs.
- JSON validation for prediction requests.
- Foreign key relationship between locations and telemetry.
- Local frontend libraries reduce dependency on third-party script delivery at runtime.

### Security work required before production

- Change the default database and admin passwords.
- Remove `register_admin.php` after setup.
- Use HTTPS.
- Add CSRF tokens to forms.
- Add session regeneration after login.
- Add secure cookie settings.
- Add role-based authorization, not only login protection.
- Validate uploaded CSV size, MIME type, headers, numeric ranges, and row count.
- Do not display raw database or external-service error details to users.
- Keep secrets outside the public web directory.
- Add audit logs for user actions and alert acknowledgements.

---

## 12. Prototype Limitations

These limitations should be stated during a presentation:

1. The current model is trained on synthetic data whose label is derived from rainfall above 100 mm. It demonstrates the ML pipeline but is not scientifically validated for real landslide prediction.
2. Open-Meteo provides weather conditions, not direct ground movement, soil moisture, slope tilt, or geotechnical measurements.
3. ESP32 ingestion is represented in the schema through `source = ESP32`, but a live ESP32 endpoint is not implemented in the current prototype.
4. The dashboard shows risk and guidance but does not send SMS, email, sirens, or official emergency notifications.
5. Apache on this machine cannot load the arm64 scikit-learn wheel through its current architecture, so the browser request uses the documented rainfall fallback. The model itself remains available for compatible Python execution.
6. There is no official alert acknowledgement, escalation, or incident-management workflow yet.
7. The prototype uses a single default database configuration suitable for XAMPP local development.
8. The selected locations are demonstration coordinates and should be verified by the responsible government office.

---

## 13. Recommended Next Steps

### Before a formal pilot

- Replace synthetic labels with verified historical rainfall, soil, slope, and landslide-event data.
- Validate thresholds with PAGASA, PHIVOLCS, CDRRMO, and geotechnical specialists.
- Add real ESP32 telemetry with authentication and signed requests.
- Add SMS/email notifications and an alert acknowledgement process.
- Add a location administration page for authorized administrators.
- Add model versioning, confidence scores, and prediction logs.
- Deploy on a supported server architecture with HTTPS and backups.
- Conduct usability testing with actual intended government users.

### Phase 2: IoT integration

- Add soil-moisture sensors.
- Add tilt or vibration sensors.
- Add physical rain gauges.
- Implement `/api/v1/telemetry.php` with device authentication.
- Add sensor heartbeat monitoring.
- Use Open-Meteo as a fallback when sensor data is stale.

### Phase 3: regional scaling

- Add additional barangays through `sensor_nodes`.
- Integrate official hazard and weather sources.
- Add a mobile-friendly public alert view.
- Add historical reports and trend comparisons.

---

## 14. Final Readiness Statement

Smart Slope is ready to be presented as a functional local prototype demonstrating:

- A protected PHP/MySQL web application.
- A simple government-oriented monitoring dashboard.
- Three independent monitoring locations.
- Live API weather ingestion.
- Location-specific telemetry storage.
- AI prediction integration with a transparent fallback.
- Charts, CSV data handling, and repeatable testing.

It should be presented as an early-warning prototype and decision-support demonstration. It should not yet be presented as a certified operational public warning system or as a model with validated 92 percent real-world accuracy.
