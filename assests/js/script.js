// script.js — Smart Slope V2 dashboard AJAX and charts

let rainfallChart, tempHumidityChart, riskHistoryChart, riskGaugeChart;
let latestReading = null;
let locationName   = '';

// ── Helpers ────────────────────────────────────────────

// Escape HTML to prevent XSS (security requirement)
function esc(v) {
    const div = document.createElement('div');
    div.textContent = (v === null || v === undefined || v === '') ? '—' : String(v);
    return div.innerHTML;
}

// Risk level → color mapping
const RISK_COLORS = {
    Low:       '#198754',
    Moderate:  '#ffc107',
    High:      '#fd7e14',
    Critical:  '#dc3545'
};

const RISK_ALERT_CLASS = {
    Low:       'alert-success',
    Moderate:  'alert-info',
    High:      'alert-warning',
    Critical:  'alert-danger'
};

const RISK_GUIDANCE = {
    Low:       'No immediate landslide warning from the latest reading.',
    Moderate:  'Conditions are worth monitoring. Stay alert for changes.',
    High:      'Elevated risk detected. Review conditions and prepare to act.',
    Critical:  'Critical risk detected. Follow local emergency procedures immediately.'
};

const SOURCE_BADGE = {
    API:   '<span class="badge bg-info">API</span>',
    ESP32: '<span class="badge bg-primary">ESP32</span>',
    CSV:   '<span class="badge bg-secondary">CSV</span>'
};

// ── Fetch telemetry data ───────────────────────────────

function fetchTelemetry() {
    $.ajax({
        url: `data.php?node_id=${encodeURIComponent(window.smartSlopeNodeId)}`,
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            if (!response.success) return;

            locationName = response.node.location_name;
            $('#location-display').text(locationName);

            const data = response.data;
            latestReading = data[0] || null;

            $('#data-status').text(
                data.length ? 'Connected · readings available' : 'No readings yet — click Update Weather'
            );
            $('#last-updated').text(
                latestReading ? `Latest: ${latestReading.timestamp}` : ''
            );

            data.reverse(); // oldest first for charts

            updateWeatherCards(latestReading);
            updateCharts(data);
            updateTable(data);

            if (latestReading) {
                updateRiskGauge(parseInt(latestReading.risk_score) || 0, latestReading.risk_level);
                updateAlert(latestReading.risk_level);
                fetchAiAnalysis(latestReading, locationName);  // auto-generate AI analysis
            }
        }
    });
}

// ── Auto-generate AI analysis ──────────────────────────

function fetchAiAnalysis(latest, locName) {
    if (!latest) return;

    $('#ai-result').html(
        '<div class="spinner-border spinner-border-sm text-secondary"></div>'
        + ' <span class="text-muted">Generating analysis…</span>'
    );

    $.ajax({
        url: 'predict.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            action: 'ai',
            location_name:  locName,
            rainfall_mm:    parseFloat(latest.rainfall_mm),
            humidity:       parseFloat(latest.humidity),
            pressure:       parseFloat(latest.pressure),
            temperature:    parseFloat(latest.temperature),
            wind_speed:     parseFloat(latest.wind_speed),
            soil_moisture:  latest.soil_moisture || null,
            risk_score:     parseInt(latest.risk_score),
            risk_level:     latest.risk_level
        }),
        success: function (result) {
            if (result.success) {
                // Color the badge by provider type
                let badgeClass = 'bg-secondary';
                if (result.provider) {
                    if (result.provider.startsWith('Grok'))          badgeClass = 'bg-dark';
                    else if (result.provider.startsWith('Gemini'))   badgeClass = 'bg-success';
                    else if (result.provider.startsWith('Groq'))    badgeClass = 'bg-warning text-dark';
                    else if (result.provider.startsWith('Mistral')) badgeClass = 'bg-danger';
                    else if (result.provider.startsWith('Cerebras'))badgeClass = 'bg-info text-dark';
                    else if (result.provider.startsWith('Cloudflare'))badgeClass = 'bg-info';
                    else if (result.provider.startsWith('OpenRouter'))badgeClass = 'bg-primary';
                    else if (result.provider.startsWith('Hugging')) badgeClass = 'bg-warning text-dark';
                }
                const sourceLabel = `<span class="badge ${badgeClass} ms-2">${esc(result.provider || 'Default Engine')}</span>`;
                $('#ai-result').html(`
                    <p class="mb-0" style="white-space: pre-wrap;">${esc(result.analysis)}</p>
                    <div class="mt-2">${sourceLabel}</div>`);
            } else {
                $('#ai-result').html(
                    `<p class="text-muted mb-0">${esc(result.message || 'Analysis unavailable.')}</p>`
                );
            }
        },
        error: function () {
            $('#ai-result').html(
                '<p class="text-muted mb-0">AI analysis is currently unavailable.</p>'
            );
        }
    });
}

// ── Update weather cards ───────────────────────────────

function updateWeatherCards(latest) {
    if (!latest) {
        ['card-rainfall','card-humidity','card-temperature','card-pressure','card-wind','card-soil']
            .forEach(id => $('#' + id).text('—'));
        return;
    }
    $('#card-rainfall').text(parseFloat(latest.rainfall_mm).toFixed(1));
    $('#card-humidity').text(parseFloat(latest.humidity).toFixed(0));
    $('#card-temperature').text(parseFloat(latest.temperature).toFixed(1));
    $('#card-pressure').text(parseFloat(latest.pressure).toFixed(0));
    $('#card-wind').text(parseFloat(latest.wind_speed).toFixed(1));
    $('#card-soil').text(latest.soil_moisture ? parseFloat(latest.soil_moisture).toFixed(0) : 'N/A');
}

// ── Charts ─────────────────────────────────────────────

function updateCharts(data) {
    if (!data.length) return;

    const labels     = data.map(d => d.timestamp);
    const rainfall   = data.map(d => parseFloat(d.rainfall_mm));
    const humidity   = data.map(d => parseFloat(d.humidity));
    const temperature= data.map(d => parseFloat(d.temperature));
    const riskScores = data.map(d => parseInt(d.risk_score) || 0);
    const riskColors = data.map(d => RISK_COLORS[d.risk_level] || '#6c757d');

    if (rainfallChart) rainfallChart.destroy();
    if (tempHumidityChart) tempHumidityChart.destroy();
    if (riskHistoryChart) riskHistoryChart.destroy();

    // Rainfall chart
    rainfallChart = new Chart(document.getElementById('rainfall-chart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Rainfall (mm)',
                data: rainfall,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.15)',
                tension: 0.1,
                fill: true
            }]
        },
        options: { responsive: true, maintainAspectRatio: false,
                   scales: { y: { beginAtZero: true } } }
    });

    // Temperature + Humidity chart
    tempHumidityChart = new Chart(document.getElementById('temp-humidity-chart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Temperature (°C)', data: temperature, borderColor: '#dc3545',
                  backgroundColor: 'rgba(220,53,69,0.15)', tension: 0.1, yAxisID: 'y' },
                { label: 'Humidity (%)', data: humidity, borderColor: '#198754',
                  backgroundColor: 'rgba(25,135,84,0.15)', tension: 0.1, yAxisID: 'y1' }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y:  { type: 'linear', position: 'left' },
                y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false },
                      min: 0, max: 100 }
            }
        }
    });

    // Risk history chart (bar chart with color-coded bars)
    riskHistoryChart = new Chart(document.getElementById('risk-history-chart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Risk Score (0–100)',
                data: riskScores,
                backgroundColor: riskColors
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
}

// ── Risk gauge (doughnut) ──────────────────────────────

function updateRiskGauge(score, level) {
    const color = RISK_COLORS[level] || '#6c757d';

    if (riskGaugeChart) riskGaugeChart.destroy();

    riskGaugeChart = new Chart(document.getElementById('risk-gauge'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [score, 100 - score],
                backgroundColor: [color, '#e9ecef'],
                borderWidth: 0
            }]
        },
        options: {
            rotation: -90, circumference: 180, cutout: '70%',
            plugins: { legend: { display: false }, tooltip: { enabled: false } }
        }
    });

    $('#risk-score-text').text(`${score} / 100`);
    $('#risk-level-text').text(level);
    $('#risk-level-text').css('color', color);
    $('#risk-guidance').text(RISK_GUIDANCE[level] || '');
}

// ── Alert banner ───────────────────────────────────────

function updateAlert(level) {
    const alert = document.getElementById('operational-alert');
    const cls   = RISK_ALERT_CLASS[level] || 'alert-secondary';
    const msg   = RISK_GUIDANCE[level] || 'Waiting for the latest reading.';

    alert.className = `alert ${cls} border mb-4`;
    alert.innerHTML = `<strong>${level}:</strong> ${esc(msg)}`;
}

// ── Latest readings table ──────────────────────────────

function updateTable(data) {
    const tbody = document.getElementById('data-table');
    tbody.innerHTML = '';

    data.slice(-20).reverse().forEach(d => {
        const level = d.risk_level || 'Low';
        const color = RISK_COLORS[level] || '#6c757d';
        const riskBadge = `<span class="badge" style="background:${color}">${esc(level)}</span>`;
        const source    = SOURCE_BADGE[d.source] || `<span class="badge bg-secondary">${esc(d.source)}</span>`;

        tbody.innerHTML += `
            <tr>
                <td>${esc(d.timestamp)}</td>
                <td>${esc(parseFloat(d.rainfall_mm).toFixed(1))}</td>
                <td>${esc(parseFloat(d.humidity).toFixed(0))}</td>
                <td>${esc(parseFloat(d.temperature).toFixed(1))}</td>
                <td>${esc(parseFloat(d.pressure).toFixed(0))}</td>
                <td>${esc(parseFloat(d.wind_speed).toFixed(1))}</td>
                <td>${d.soil_moisture ? esc(parseFloat(d.soil_moisture).toFixed(0)) : 'N/A'}</td>
                <td><strong>${esc(d.risk_score)}</strong></td>
                <td>${riskBadge}</td>
                <td>${source}</td>
            </tr>`;
    });
}

// ── Page ready ─────────────────────────────────────────

$(document).ready(function () {

    // Location selector — navigate to selected node
    $('#location-select').on('change', function () {
        window.location.href = `index.php?node_id=${encodeURIComponent(this.value)}`;
    });

    // Refresh button
    $('#refresh-dashboard').on('click', function () {
        const btn = $(this);
        btn.prop('disabled', true).text('Refreshing…');
        fetchTelemetry();
        setTimeout(() => btn.prop('disabled', false).text('Refresh data'), 600);
    });

    // Update weather — AJAX call to fetch_weather.php
    $('#update-weather-btn').on('click', function () {
        const btn = $(this);
        btn.prop('disabled', true).text('Fetching…');

        $.ajax({
            url: `fetch_weather.php?node_id=${encodeURIComponent(window.smartSlopeNodeId)}`,
            type: 'GET',
            dataType: 'json',
            success: function (result) {
                if (result.success) {
                    fetchTelemetry(); // refresh dashboard with new data
                } else {
                    alert(result.message || 'Unable to fetch weather.');
                }
            },
            error: function () {
                alert('Unable to fetch weather data. Check your connection.');
            },
            complete: function () {
                btn.prop('disabled', false).text('Update weather');
            }
        });
    });

    // Manual prediction form
    $('#prediction-form').on('submit', function (e) {
        e.preventDefault();
        const payload = {
            rainfall_mm:    parseFloat($('#rainfall_mm').val()),
            humidity:      parseFloat($('#humidity').val()),
            pressure:      parseFloat($('#pressure').val()),
            temperature:   parseFloat($('#temperature').val()),
            wind_speed:    parseFloat($('#wind_speed').val()),
            soil_moisture: parseFloat($('#soil_moisture').val()) || 0
        };

        $('#prediction-result').html(
            '<div class="spinner-border text-primary"></div> Assessing…'
        );

        $.ajax({
            url: 'predict.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function (result) {
                if (!result.success) {
                    $('#prediction-result').html(
                        `<div class="alert alert-warning">${esc(result.message)}</div>`
                    );
                    return;
                }
                const color = RISK_COLORS[result.risk_level] || '#6c757d';
                $('#prediction-result').html(`
                    <div class="alert" style="border-left: 4px solid ${color}">
                        <h4 class="mb-1">Manual assessment: <strong>${esc(result.risk_level)}</strong></h4>
                        <p class="mb-1">Risk Score: <strong>${result.risk_score} / 100</strong></p>
                        <p class="mb-0 text-muted">${esc(RISK_GUIDANCE[result.risk_level] || '')}</p>
                    </div>`);
                updateRiskGauge(result.risk_score, result.risk_level);
            },
            error: function () {
                $('#prediction-result').html(
                    '<div class="alert alert-warning">Assessment could not be completed.</div>'
                );
            }
        });
    });

    // Initial load + auto-refresh every 5 minutes
    // AI analysis auto-generates each time telemetry is fetched
    fetchTelemetry();
    setInterval(fetchTelemetry, 300000);
});