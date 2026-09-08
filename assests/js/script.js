// assets/js/script.js - Vanilla JS, no jQuery

// Auto-refresh AI analysis
function refreshAiAnalysis() {
    const latest = <?= json_encode($latest ?? []) ?>;
    fetch('predict.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=ai&' + new URLSearchParams(latest)
    })
    .then(response => response.json())
    .then(result => {
        const aiResult = document.getElementById('ai-result');
        if (aiResult) {
            aiResult.innerHTML = 
                `<div style="white-space: pre-wrap;">${result.analysis}</div>` +
                `<p class="text-muted small mt-2 mb-0"><strong>Provider:</strong> ${result.provider}</p>`;
        }
    })
    .catch(() => {});
}

// Refresh on page load
document.addEventListener('DOMContentLoaded', refreshAiAnalysis);

// Refresh every 30 seconds
setInterval(refreshAiAnalysis, 30000);