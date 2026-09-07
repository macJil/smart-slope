let rainfallChart, tempHumidityChart, riskHistoryChart, riskGaugeChart;

function fetchTelemetry() {
    $.ajax({
        url: `data.php?node_id=${encodeURIComponent(window.smartSlopeNodeId)}`,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            const latest = data[0];
            $('#data-status').text(data.length ? 'Connected · readings available' : 'No readings available');
            $('#last-updated').text(latest ? `Latest reading: ${latest.timestamp}` : '');
            data.reverse();
            updateCharts(data);
            updateTable(data);
            if (latest) {
                updateOperationalAlert(parseFloat(latest.rainfall_mm) > 100 ? 1 : 0);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
        }
    });
}

function updateCharts(data) {
    if (!data.length) {
        console.warn('No data');
        return;
    }

    const timestamps = data.map(d => d.timestamp);
    const rainfall = data.map(d => parseFloat(d.rainfall_mm));
    const humidity = data.map(d => parseFloat(d.humidity));
    const temperature = data.map(d => parseFloat(d.temperature));
    const risk = data.map(d => parseFloat(d.rainfall_mm) > 100 ? 1 : 0);

    if (rainfallChart) rainfallChart.destroy();
    if (tempHumidityChart) tempHumidityChart.destroy();
    if (riskHistoryChart) riskHistoryChart.destroy();

    rainfallChart = new Chart(document.getElementById('rainfall-chart'), {
        type: 'line',
        data: {
            labels: timestamps,
            datasets: [{
                label: 'Rainfall (mm)',
                data: rainfall,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.15)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    tempHumidityChart = new Chart(document.getElementById('temp-humidity-chart'), {
        type: 'line',
        data: {
            labels: timestamps,
            datasets: [
                {
                    label: 'Temperature (C)',
                    data: temperature,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.15)',
                    tension: 0.1,
                    yAxisID: 'y'
                },
                {
                    label: 'Humidity (%)',
                    data: humidity,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.15)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { type: 'linear', position: 'left' },
                y1: {
                    type: 'linear',
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    min: 0,
                    max: 100
                }
            }
        }
    });

    riskHistoryChart = new Chart(document.getElementById('risk-history-chart'), {
        type: 'bar',
        data: {
            labels: timestamps,
            datasets: [{
                label: 'Risk (1 = High, 0 = Low)',
                data: risk,
                backgroundColor: risk.map(value => value === 1 ? '#dc3545' : '#198754')
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    updateRiskGauge(risk[risk.length - 1]);
}

function updateRiskGauge(prediction) {
    const value = prediction === 1 ? 80 : 20;
    const color = prediction === 1 ? '#dc3545' : '#198754';
    const text = prediction === 1 ? 'High Risk' : 'Low Risk';
    const guidance = prediction === 1
        ? 'Review conditions and follow your local emergency response procedure.'
        : 'No immediate warning from the latest rainfall reading.';

    if (riskGaugeChart) riskGaugeChart.destroy();

    riskGaugeChart = new Chart(document.getElementById('risk-gauge'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [value, 100 - value],
                backgroundColor: [color, '#e9ecef'],
                borderWidth: 0
            }]
        },
        options: {
            rotation: -90,
            circumference: 180,
            cutout: '70%',
            plugins: { legend: { display: false }, tooltip: { enabled: false } }
        }
    });

    document.getElementById('risk-level').textContent = text;
    document.getElementById('risk-guidance').textContent = guidance;
}

function updateOperationalAlert(prediction) {
    const alert = document.getElementById('operational-alert');
    if (prediction === 1) {
        alert.className = 'alert alert-danger border mb-4';
        alert.innerHTML = '<strong>High-risk alert:</strong> Review the latest conditions and follow the local emergency response procedure.';
        return;
    }

    alert.className = 'alert alert-success border mb-4';
    alert.innerHTML = '<strong>Current status:</strong> No immediate landslide warning from the latest reading.';
}

function updateTable(data) {
    const tbody = document.getElementById('data-table');
    tbody.innerHTML = '';
    data.slice(-20).reverse().forEach(d => {
        const risk = parseFloat(d.rainfall_mm) > 100
            ? '<span class="badge bg-danger">High</span>'
            : '<span class="badge bg-success">Low</span>';
        const source = d.source === 'API'
            ? '<span class="badge bg-info">API</span>'
            : '<span class="badge bg-secondary">ESP32</span>';
        tbody.innerHTML += `
            <tr>
                <td>${d.timestamp}</td>
                <td>${d.soil_moisture ?? '—'}</td>
                <td>${parseFloat(d.rainfall_mm).toFixed(1)}</td>
                <td>${parseFloat(d.humidity).toFixed(1)}</td>
                <td>${parseFloat(d.pressure).toFixed(1)}</td>
                <td>${parseFloat(d.temperature).toFixed(1)}</td>
                <td>${source}</td>
                <td>${risk}</td>
            </tr>`;
    });
}

$(document).ready(function() {
    $('#location-select').on('change', function() {
        window.location.href = `index.php?node_id=${encodeURIComponent(this.value)}`;
    });

    $('#refresh-dashboard').on('click', function() {
        const button = $(this);
        button.prop('disabled', true).text('Refreshing...');
        fetchTelemetry();
        window.setTimeout(function() {
            button.prop('disabled', false).text('Refresh data');
        }, 600);
    });

    $('#prediction-form').on('submit', function(event) {
        event.preventDefault();
        const payload = {
            rainfall_mm: parseFloat($('#rainfall_mm').val()),
            humidity: parseFloat($('#humidity').val()),
            pressure: parseFloat($('#pressure').val()),
            temperature: parseFloat($('#temperature').val()),
            wind_speed: parseFloat($('#wind_speed').val())
        };
        $('#prediction-result').html('<div class="spinner-border text-primary" role="status"></div> Assessing...');

        $.ajax({
            url: 'predict.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function(result) {
                if (result.error) {
                    $('#prediction-result').html(`<div class="alert alert-warning">${result.error}</div>`);
                    return;
                }
                const risk = result.prediction === 1 ? 'High Risk' : 'Low Risk';
                const alertClass = result.prediction === 1 ? 'alert-danger' : 'alert-success';
                const note = result.fallback
                    ? 'Prototype mode: this assessment uses the documented rainfall threshold.'
                    : 'Assessment returned by the trained Random Forest model.';
                $('#prediction-result').html(`
                    <div class="alert ${alertClass}">
                        <h4>Manual assessment: <strong>${risk}</strong></h4>
                        <p class="mb-0">${note}</p>
                    </div>`);
                updateRiskGauge(result.prediction);
            },
            error: function() {
                $('#prediction-result').html('<div class="alert alert-warning">The manual assessment could not be completed.</div>');
            }
        });
    });

    fetchTelemetry();
    setInterval(fetchTelemetry, 300000);
});
