// assets/js/script.js - Pure JavaScript for Smart Slope V2

function refreshAiAnalysis() {
    let latestData = {};
    try {
        latestData = JSON.parse(document.getElementById('latest-data')?.textContent || '{}');
    } catch (error) {
        return;
    }
    
    if (Object.keys(latestData).length === 0) return;
    
    fetch('predict.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=ai&' + new URLSearchParams(latestData)
    })
    .then(response => response.json())
    .then(result => {
        const aiResult = document.getElementById('ai-result');
        const providerBadge = document.getElementById('ai-provider');
        
        if (aiResult) {
            aiResult.textContent = result.analysis || 'Analysis unavailable.';
        }
        if (providerBadge) {
            providerBadge.textContent = result.provider || 'Default';
        }
    })
    .catch(error => {
        console.error('AI refresh error:', error);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    refreshAiAnalysis();

    document.getElementById('refresh-weather')?.addEventListener('click', function() {
        const button = this;
        const nodeId = button.dataset.nodeId;
        button.disabled = true;
        button.textContent = 'Refreshing...';

        fetch(`fetch_weather.php?node_id=${encodeURIComponent(nodeId)}`)
            .then(response => response.json())
            .then(result => {
                if (!result.success) throw new Error(result.message || 'Weather update failed.');
                window.location.reload();
            })
            .catch(error => {
                button.disabled = false;
                button.textContent = 'Refresh';
                alert(error.message);
            });
    });
});