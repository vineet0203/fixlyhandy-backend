import { initializeScheduleHeader } from './components/schedule-header';
import { initializeScheduleFilters } from './components/schedule-filters';
import { initializeCrewMembers } from './components/crew-members';
import { initializeScheduleCalendar } from './components/schedule-calendar';
import { initializeRouteOptimization } from './components/route-optimization';
import { initializeUpcomingJobs } from './components/upcoming-jobs';

const root = document.getElementById('schedule-dashboard');

if (root) {
    const apiBase = root.dataset.apiBase || '/api';
    const token = localStorage.getItem('token');

    if (token) {
        window.axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    }

    const state = {
        apiBase,
        activeCrewId: '',
        currentView: root.dataset.initialView || 'timeGridWeek',
    };

    initializeScheduleHeader(state);
    initializeScheduleFilters(state);
    initializeCrewMembers(state);
    initializeScheduleCalendar(state);
    initializeRouteOptimization(state);
    initializeUpcomingJobs(state);
}
