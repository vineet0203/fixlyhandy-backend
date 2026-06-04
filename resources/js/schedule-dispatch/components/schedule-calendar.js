import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

function buildPayload(eventLike) {
    const start = eventLike.start;
    const end = eventLike.end;

    return {
        schedule_date: start.toISOString().slice(0, 10),
        start_time: start.toTimeString().slice(0, 5),
        end_time: end.toTimeString().slice(0, 5),
    };
}

function toEventInput(item) {
    return {
        id: item.id,
        title: item.title,
        start: item.start,
        end: item.end,
        backgroundColor: item.backgroundColor,
        borderColor: item.borderColor,
        extendedProps: {
            crew_id: item.crew_id,
            employee_id: item.employee_id,
            status: item.status,
        },
    };
}

export function initializeScheduleCalendar(state) {
    const calendarEl = document.getElementById('schedule-calendar');

    if (!calendarEl) {
        return;
    }

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: state.currentView,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '',
        },
        editable: true,
        selectable: true,
        eventResizableFromStart: true,
        events: async (fetchInfo, successCallback, failureCallback) => {
            try {
                const response = await window.axios.get(`${state.apiBase}/schedules`);
                const rows = Array.isArray(response.data) ? response.data : [];
                const filtered = state.activeCrewId
                    ? rows.filter((item) => String(item.crew_id) === String(state.activeCrewId))
                    : rows;
                successCallback(filtered.map(toEventInput));
            } catch (error) {
                failureCallback(error);
            }
        },
        eventClick: (info) => {
            const { title } = info.event;
            const status = info.event.extendedProps.status || 'pending';
            alert(`Job: ${title}\nStatus: ${status}`);
        },
        eventDrop: async (info) => {
            try {
                await window.axios.put(`${state.apiBase}/schedules/${info.event.id}`, buildPayload(info.event));
            } catch (error) {
                info.revert();
            }
        },
        eventResize: async (info) => {
            try {
                await window.axios.put(`${state.apiBase}/schedules/${info.event.id}`, buildPayload(info.event));
            } catch (error) {
                info.revert();
            }
        },
    });

    window.addEventListener('schedule:view-changed', (event) => {
        calendar.changeView(event.detail.view);
    });

    window.addEventListener('schedule:crew-filtered', () => {
        calendar.refetchEvents();
    });

    window.addEventListener('schedule:create-requested', async () => {
        const jobIdInput = window.prompt('Enter Job ID to schedule:');
        const parsedJobId = Number(jobIdInput);

        if (!jobIdInput || Number.isNaN(parsedJobId) || parsedJobId <= 0) {
            alert('A valid Job ID is required.');
            return;
        }

        const now = new Date();
        const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000);

        try {
            await window.axios.post(`${state.apiBase}/schedules`, {
                job_id: parsedJobId,
                title: 'New Scheduled Job',
                schedule_date: now.toISOString().slice(0, 10),
                start_time: now.toTimeString().slice(0, 5),
                end_time: oneHourLater.toTimeString().slice(0, 5),
                address: '',
                location_lat: null,
                location_lng: null,
            });
            calendar.refetchEvents();
            window.dispatchEvent(new CustomEvent('schedule:upcoming-refresh'));
        } catch (error) {
            alert('Unable to create schedule. Please provide a valid job before creating.');
        }
    });

    calendar.render();
}
