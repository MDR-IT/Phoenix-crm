<x-app-layout>
    <x-slot name="header">Pipeline board</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <div>
            <h1 class="h3 mb-1">Lead pipeline</h1>
            <p class="text-secondary mb-0">Drag cards between stages or use Move on each card. Status history is recorded.</p>
        </div>
        <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary">List view</a>
    </div>

    <div class="pc-kanban" id="kanban-board">
        @foreach ($columns as $status => $column)
            <div class="pc-kanban-column" data-status="{{ $status }}">
                <div class="pc-kanban-head">
                    <strong>{{ $column['label'] }}</strong>
                    <span class="badge badge-role">{{ $column['leads']->count() }}</span>
                </div>
                <div class="pc-kanban-list" data-status="{{ $status }}">
                    @foreach ($column['leads'] as $lead)
                        <div class="pc-kanban-card" draggable="true" data-lead-id="{{ $lead->id }}">
                            <a href="{{ route('leads.show', $lead) }}" class="fw-semibold text-decoration-none">{{ $lead->reference }}</a>
                            <div class="small">{{ $lead->customer?->full_name }}</div>
                            <div class="small text-secondary">{{ $lead->product?->name ?: 'No product' }} · {{ $lead->assignee?->name ?: 'Unassigned' }}</div>
                            <form method="POST" action="{{ route('leads.status', $lead) }}" class="mt-2">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    @foreach ($statuses as $key => $label)
                                        <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @push('scripts')
        <script>
            const token = document.querySelector('meta[name="csrf-token"]').content;
            document.querySelectorAll('.pc-kanban-card').forEach(card => {
                card.addEventListener('dragstart', e => {
                    e.dataTransfer.setData('text/plain', card.dataset.leadId);
                    card.classList.add('dragging');
                });
                card.addEventListener('dragend', () => card.classList.remove('dragging'));
            });
            document.querySelectorAll('.pc-kanban-list').forEach(list => {
                list.addEventListener('dragover', e => {
                    e.preventDefault();
                    list.classList.add('drag-over');
                });
                list.addEventListener('dragleave', () => list.classList.remove('drag-over'));
                list.addEventListener('drop', async e => {
                    e.preventDefault();
                    list.classList.remove('drag-over');
                    const leadId = e.dataTransfer.getData('text/plain');
                    const status = list.dataset.status;
                    const card = document.querySelector(`.pc-kanban-card[data-lead-id="${leadId}"]`);
                    if (!card) return;
                    list.appendChild(card);
                    const res = await fetch(`/leads/${leadId}/status`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ status }),
                    });
                    if (!res.ok) {
                        alert('Could not update status');
                        window.location.reload();
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
