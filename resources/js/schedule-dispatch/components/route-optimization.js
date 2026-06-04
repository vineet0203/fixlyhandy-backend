function calculateRouteSummary(upcomingJobs) {
    const stops = upcomingJobs.length;
    const distance = (stops * 2.5).toFixed(1);
    const duration = Math.max(stops * 18, 0);

    return `Today's Route: ${stops} stops | ${distance} Mi | ${duration} Min.`;
}

export function initializeRouteOptimization() {
    const summary = document.getElementById('route-summary');
    const button = document.getElementById('optimize-route-btn');

    if (button) {
        button.addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('schedule:upcoming-refresh'));
        });
    }

    window.addEventListener('schedule:upcoming-updated', (event) => {
        if (summary) {
            summary.textContent = calculateRouteSummary(event.detail.jobs || []);
        }
    });
}
