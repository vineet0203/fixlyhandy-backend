async function fetchCrews(apiBase) {
    const response = await window.axios.get(`${apiBase}/schedules/crews`);
    return Array.isArray(response.data) ? response.data : [];
}

function renderTeamOptions(teamFilter, crews) {
    if (!teamFilter) {
        return;
    }

    const options = ['<option value="">All Crews</option>'];

    crews.forEach((crew) => {
        options.push(`<option value="${crew.id}">${crew.name}</option>`);
    });

    teamFilter.innerHTML = options.join('');
}

function renderCrewMembers(container, crews) {
    if (!container) {
        return;
    }

    const cards = [];

    crews.forEach((crew) => {
        (crew.members || []).forEach((member) => {
            const initial = (member.name || 'U').charAt(0).toUpperCase();
            cards.push(`
                <article class="crew-member-item" data-crew-id="${crew.id}" data-employee-id="${member.id}">
                    <div class="avatar">${initial}</div>
                    <div>
                        <strong>${member.name}</strong>
                        <small>${crew.name}${member.role ? ` • ${member.role}` : ''}</small>
                    </div>
                </article>
            `);
        });
    });

    container.innerHTML = cards.length > 0 ? cards.join('') : '<p class="empty-state">No crew members available.</p>';
}

export function initializeCrewMembers(state) {
    const teamFilter = document.getElementById('team-filter');
    const crewMembersList = document.getElementById('crew-members-list');

    fetchCrews(state.apiBase)
        .then((crews) => {
            renderTeamOptions(teamFilter, crews);
            renderCrewMembers(crewMembersList, crews);
        })
        .catch(() => {
            if (crewMembersList) {
                crewMembersList.innerHTML = '<p class="empty-state">Unable to load crews.</p>';
            }
        });
}
