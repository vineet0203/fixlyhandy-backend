<section class="crew-members" id="crew-members">
    <h3>Crew Members</h3>
    <div id="crew-members-list">
        @forelse ($crews as $crew)
            @foreach ($crew->members as $member)
                @if ($member->employee)
                    <article class="crew-member-item" data-crew-id="{{ $crew->id }}" data-employee-id="{{ $member->employee->id }}">
                        <div class="avatar">{{ strtoupper(substr($member->employee->first_name, 0, 1)) }}</div>
                        <div>
                            <strong>{{ $member->employee->full_name }}</strong>
                            <small>{{ $crew->name }}{{ $member->role ? ' • '.$member->role : '' }}</small>
                        </div>
                    </article>
                @endif
            @endforeach
        @empty
            <p class="empty-state">No crew members available.</p>
        @endforelse
    </div>
</section>
