function statusClass(status) {
    const value = (status || '').toLowerCase();

    if (value === 'in_progress') {
        return 'status-pill status-service';
    }

    if (value === 'completed') {
        return 'status-pill status-normal';
    }

    if (value === 'cancelled') {
        return 'status-pill status-inspection';
    }

    return 'status-pill status-repair';
}

function renderUpcoming(container, jobs) {
    if (!container) {
        return;
    }

    if (!jobs.length) {
        container.innerHTML = '<p class="empty-state">No upcoming jobs.</p>';
        return;
    }

    container.innerHTML = jobs
        .map((job) => {
            return `
                <article class="upcoming-item">
                    <div class="upcoming-time">${job.time || '--:--'}</div>
                    <div class="upcoming-details">
                        <strong>${job.job_title || 'Scheduled Job'}</strong>
                        <p>${job.location || 'Location not set'}</p>
                        <span class="${statusClass(job.status)}">${job.status || 'pending'}</span>
                    </div>
                </article>
            `;
        })
        .join('');
}

export function initializeUpcomingJobs(state) {
    const container = document.getElementById('upcoming-jobs-list');

    const load = async () => {
        try {
            const response = await window.axios.get(`${state.apiBase}/schedules/upcoming`);
            const jobs = Array.isArray(response.data) ? response.data : [];
            renderUpcoming(container, jobs);
            window.dispatchEvent(new CustomEvent('schedule:upcoming-updated', { detail: { jobs } }));
        } catch (error) {
            if (container) {
                container.innerHTML = '<p class="empty-state">Unable to load upcoming jobs.</p>';
            }
        }
    };

    window.addEventListener('schedule:upcoming-refresh', load);
    load();
}
