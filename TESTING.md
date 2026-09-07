# Smart Slope Testing Guide

Use this checklist before a presentation or demonstration. Start Apache and MySQL in XAMPP first.

## 1. Open the website

1. Open `http://localhost/smart_slope/login.php`.
2. Sign in with the configured admin account.
3. Expected result: the dashboard opens without PHP errors.

## 2. Check the three monitoring locations

1. On the dashboard, open the **Monitoring location** list.
2. Select each location:
   - Barangay Loay, Baguio City
   - Barangay Pinget, Baguio City
   - Barangay Gibraltar, Baguio City
3. Expected result: the page reloads with the selected location shown in the URL and its own readings.
4. Confirm that changing locations does not show the previous location's telemetry.

## 3. Update weather data

1. Select a location.
2. Click **Update weather**.
3. Expected result: a success message names the selected location.
4. Return to the dashboard and confirm a new reading appears for that location.
5. Repeat for all three locations.

## 4. Check the automatic current alert

1. Select a monitoring location.
2. Confirm that the dashboard shows a **Current status** or **High-risk alert** banner.
3. Confirm that the risk gauge matches the latest reading for that location.
4. Update weather data and return to the dashboard.
5. Expected result: the alert and latest reading update without manual weather input.

## 5. Refresh the dashboard after data changes

1. Select a monitoring location.
2. Click **Refresh data**.
3. Expected result: the button briefly shows **Refreshing...**, then the charts and latest readings reload for the selected location.
4. Open **Upload records**, upload `sample_telemetry.csv`, and click **Refresh dashboard**.
5. Expected result: the dashboard opens for the same selected location and shows the new records.
6. Click **Download report**.
7. Expected result: the downloaded report contains telemetry for the selected location.

## 6. Remove imported data without removing live data

1. Select a monitoring location.
2. Open **Clear imported data**.
3. Confirm that the page says only CSV-imported records will be removed.
4. Click **Remove imported data**.
5. Expected result: imported records are removed for that location and live API readings remain.
6. Click **Refresh dashboard** and confirm the dashboard shows the remaining current data.

## 7. Test manual presentation mode

1. Scroll to **Manual presentation mode**.
2. Enter rainfall `10` and click **Assess risk**.
3. Expected result: a green **Low Risk** manual assessment appears.
4. Enter rainfall `120` and click **Assess risk**.
5. Expected result: a red **High Risk** manual assessment appears.
6. Confirm that the automatic current alert remains separate from the manual demonstration result.

## 8. Test low-risk prediction endpoint

The normal dashboard does not require manual weather input. This command tests the prediction endpoint directly:

1. Send these values:
   - Rainfall: `10`
   - Humidity: `60`
   - Air pressure: `1015`
   - Temperature: `22`
   - Wind speed: `2`
2. Expected result: the endpoint returns `prediction: 0`.

## 9. Test high-risk prediction endpoint

1. Send these values to the prediction endpoint:
   - Rainfall: `120`
   - Humidity: `90`
   - Air pressure: `1008`
   - Temperature: `21`
   - Wind speed: `5`
2. Expected result: the endpoint returns `prediction: 1`.

## 10. Check live dashboard updates

1. Confirm **Connected - readings available** appears near the dashboard heading.
2. Confirm the latest reading time is displayed.
3. Confirm the risk gauge, risk history, rainfall chart, and temperature/humidity chart contain data.
4. Wait for the five-minute refresh or reload the page.
5. Expected result: the data remains available and the latest reading time updates after a new weather fetch.

## 11. Test CSV import

1. Open **Upload records**.
2. Upload `sample_telemetry.csv`.
3. Expected result: the page reports that 3 records were imported.
4. Return to the dashboard and confirm the records appear under the selected location's node ID.

## 12. Test CSV export

1. Open **Download report**.
2. Expected result: a CSV file downloads.
3. Open the file and confirm it contains telemetry headers and records.

## 13. Test access control

1. Sign out.
2. Open `http://localhost/smart_slope/index.php` directly.
3. Expected result: the website redirects to the login page.
4. Sign in again and confirm the dashboard is available.

## 14. Command-line smoke tests

Run these commands from the project directory:

```bash
PHP_BIN=/Applications/XAMPP/xamppfiles/bin/php
for file in *.php classes/*.php; do "$PHP_BIN" -l "$file" || exit 1; done

curl -sS "http://localhost/smart_slope/data.php?node_id=1"
curl -sS "http://localhost/smart_slope/data.php?node_id=2"
curl -sS "http://localhost/smart_slope/data.php?node_id=3"

curl -sS -X POST http://localhost/smart_slope/predict.php \
  -H "Content-Type: application/json" \
  -d '{"rainfall_mm":10,"humidity":60,"pressure":1015,"temperature":22,"wind_speed":2}'

curl -sS -X POST http://localhost/smart_slope/predict.php \
  -H "Content-Type: application/json" \
  -d '{"rainfall_mm":120,"humidity":90,"pressure":1008,"temperature":21,"wind_speed":5}'
```

Expected command results:

- PHP reports no syntax errors.
- Each data endpoint returns a JSON array.
- The low-risk prediction returns `prediction: 0`.
- The high-risk prediction returns `prediction: 1`.

## Important note

The prediction endpoint uses the trained Random Forest model when Apache can load the installed Python/scikit-learn architecture. On this development machine, Apache currently uses the prototype threshold mode because of a Python/scikit-learn architecture mismatch. In that mode, rainfall above 100 mm is high risk; otherwise it is low risk. This is suitable for demonstrating the workflow, but it must not be described as a live Random Forest result.
