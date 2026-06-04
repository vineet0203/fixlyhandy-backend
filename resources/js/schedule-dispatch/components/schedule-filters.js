export function initializeScheduleFilters(state) {
    const teamFilter = document.getElementById('team-filter');
    const viewFilter = document.getElementById('view-filter');

    if (teamFilter) {
        teamFilter.addEventListener('change', (event) => {
            state.activeCrewId = event.target.value;
            window.dispatchEvent(new CustomEvent('schedule:crew-filtered', { detail: { crewId: state.activeCrewId } }));
        });
    }

    if (viewFilter) {
        viewFilter.addEventListener('change', (event) => {
            state.currentView = event.target.value;
            window.dispatchEvent(new CustomEvent('schedule:view-changed', { detail: { view: state.currentView } }));
        });
    }
}
