export function initializeScheduleHeader(state) {
    const createButton = document.getElementById('create-schedule-btn');

    if (!createButton) {
        return;
    }

    createButton.addEventListener('click', () => {
        window.dispatchEvent(new CustomEvent('schedule:create-requested'));
    });
}
