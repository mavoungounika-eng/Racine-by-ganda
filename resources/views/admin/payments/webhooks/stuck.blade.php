@extends('layouts.admin-master')

@section('title', 'Stuck Webhooks - Payments Hub - RACINE BY GANDA')
@section('page-title', 'Stuck Webhooks')
@section('page-subtitle', 'Événements webhook/callback bloqués')

@section('content')
@push('scripts')
<script>
    $(document).ready(function() {
        // Requeue one
        $('.requeue-one-btn').on('click', function() {
            const provider = $(this).data('provider');
            const id = $(this).data('id');
            const minutes = $(this).data('minutes');
            
            $('#requeueOneModal').find('input[name="provider"]').val(provider);
            $('#requeueOneModal').find('input[name="id"]').val(id);
            $('#requeueOneModal').find('input[name="minutes"]').val(minutes);
            $('#requeueOneModal').modal('show');
        });

        // Reset window
        $('.reset-window-btn').on('click', function() {
            const provider = $(this).data('provider');
            const id = $(this).data('id');
            
            $('#resetWindowModal').find('input[name="provider"]').val(provider);
            $('#resetWindowModal').find('input[name="id"]').val(id);
            $('#resetWindowModal').modal('show');
        });
    });
</script>
@endpush

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.payments.index') }}">Paiements</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.payments.webhooks.index') }}">Webhooks</a></li>
        <li class="breadcrumb-item active" aria-current="page">Stuck</li>
    </ol>
</nav>

{{-- Stats mini --}}
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card card-racine">
            <div class="card-body text-center">
                <div class="small text-muted">Stuck Stripe</div>
                <div class="h4 mb-0 text-warning">{{ $stats['stripe_total'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-racine">
            <div class="card-body text-center">
                <div class="small text-muted">Stuck Monetbil</div>
                <div class="h4 mb-0 text-warning">{{ $stats['monetbil_total'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-racine">
            <div class="card-body text-center">
                <div class="small text-muted">Received Stuck</div>
                <div class="h4 mb-0">{{ $stats['stripe_received'] + $stats['monetbil_received'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card card-racine">
            <div class="card-body text-center">
                <div class="small text-muted">Failed Old</div>
                <div class="h4 mb-0 text-danger">{{ $stats['stripe_failed_old'] + $stats['monetbil_failed_old'] }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Filtres --}}
<div class="card card-racine mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.payments.webhooks.stuck.index') }}" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Provider</label>
                <select name="provider" class="form-control">
                    <option value="all" {{ $provider === 'all' ? 'selected' : '' }}>Tous</option>
                    <option value="stripe" {{ $provider === 'stripe' ? 'selected' : '' }}>Stripe</option>
                    <option value="monetbil" {{ $provider === 'monetbil' ? 'selected' : '' }}>Monetbil</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Tous</option>
                    <option value="received" {{ $status === 'received' ? 'selected' : '' }}>Received</option>
                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="blocked" {{ $status === 'blocked' ? 'selected' : '' }}>Blocked</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Minutes (threshold)</label>
                <input type="number" name="minutes" class="form-control" value="{{ $minutes }}" min="1">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date from</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date to</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Recherche</label>
                <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="Event ID / Ref">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Filtrer
                </button>
                <a href="{{ route('admin.payments.webhooks.stuck.index') }}" class="btn btn-secondary">
                    <i class="fas fa-redo me-2"></i>Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card card-racine">
    <div class="card-header bg-transparent border-bottom-2 border-racine-beige d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold">Événements Stuck</h5>
        @can('payments.reprocess')
        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#requeueBulkModal">
            <i class="fas fa-redo me-2"></i>Requeue Selection
        </button>
        @endcan
    </div>
    <div class="card-body">
        <form id="requeueForm" method="POST" action="{{ route('admin.payments.webhooks.stuck.requeue') }}">
            @csrf
            <input type="hidden" name="minutes" value="{{ $minutes }}">
            <input type="hidden" name="provider" value="{{ $provider }}">
            <input type="hidden" name="ids" id="selectedIds" value="[]">
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>Provider</th>
                            <th>Event Identifier</th>
                            <th>Event Type</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Dispatched At</th>
                            <th>Processed At</th>
                            <th>Stuck Reason</th>
                            <th>Requeue Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginated as $event)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input event-checkbox" 
                                       data-provider="{{ $event['provider'] }}" 
                                       data-id="{{ $event['id'] }}">
                            </td>
                            <td>
                                @if($event['provider'] === 'stripe')
                                    <span class="badge badge-primary">Stripe</span>
                                @else
                                    <span class="badge badge-info">Monetbil</span>
                                @endif
                            </td>
                            <td>
                                <code>{{ \Illuminate\Support\Str::limit($event['event_identifier'], 30) }}</code>
                            </td>
                            <td>{{ $event['event_type'] ?? '-' }}</td>
                            <td>
                                @if($event['status'] === 'processed')
                                    <span class="badge badge-success">Processed</span>
                                @elseif($event['status'] === 'failed')
                                    <span class="badge badge-danger">Failed</span>
                                @else
                                    <span class="badge badge-warning">Received</span>
                                @endif
                            </td>
                            <td>{{ $event['created_at']->format('Y-m-d H:i:s') }}</td>
                            <td>
                                @if($event['dispatched_at'])
                                    {{ $event['dispatched_at']->format('Y-m-d H:i:s') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($event['processed_at'])
                                    {{ $event['processed_at']->format('Y-m-d H:i:s') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $event['is_stuck_reason'] }}</small>
                            </td>
                            <td>
                                @if(($event['requeue_count'] ?? 0) > 0)
                                    <span class="badge badge-warning" 
                                          title="Dernier requeue: {{ $event['last_requeue_at'] ? $event['last_requeue_at']->format('Y-m-d H:i:s') : '-' }}">
                                        {{ $event['requeue_count'] }}
                                    </span>
                                    @if(!($event['can_requeue'] ?? true) && ($event['next_requeue_at'] ?? null))
                                        <small class="d-block text-muted mt-1">
                                            <i class="fas fa-clock"></i> Cooldown jusqu'à {{ $event['next_requeue_at']->format('H:i') }}
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td>
                                @can('payments.reprocess')
                                @if($event['status'] === 'blocked')
                                    <button type="button" 
                                            class="btn btn-sm btn-info reset-window-btn" 
                                            data-provider="{{ $event['provider'] }}" 
                                            data-id="{{ $event['id'] }}"
                                            title="Reset requeue window (réactive le requeue)"
                                            data-toggle="tooltip" 
                                            data-placement="top">
                                        <i class="fas fa-unlock"></i>
                                    </button>
                                @elseif(!($event['can_requeue'] ?? true))
                                    <button type="button" 
                                            class="btn btn-sm btn-secondary" 
                                            disabled 
                                            title="{{ $event['blocked_message'] ?? 'Limite de requeue atteinte' }}"
                                            data-toggle="tooltip" 
                                            data-placement="top">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                @else
                                    <button type="button" 
                                            class="btn btn-sm btn-warning requeue-one-btn" 
                                            data-provider="{{ $event['provider'] }}" 
                                            data-id="{{ $event['id'] }}"
                                            data-minutes="{{ $minutes }}"
                                            title="Requeue cet événement">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                @endif
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                Aucun événement stuck trouvé
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($paginated->hasPages())
            <div class="mt-3">
                {{ $paginated->links() }}
            </div>
            @endif
        </form>
    </div>
</div>

{{-- Modal Requeue Bulk --}}
@can('payments.reprocess')
<div class="modal fade" id="requeueBulkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Requeue Selection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.payments.webhooks.stuck.requeue') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="minutes" value="{{ $minutes }}">
                    <input type="hidden" name="provider" value="{{ $provider }}">
                    <input type="hidden" name="ids" id="bulkSelectedIds" value="[]">
                    
                    <div class="mb-3">
                        <label class="form-label">Raison <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required minlength="5" 
                                  placeholder="Expliquez pourquoi vous requeue ces événements..."></textarea>
                        <small class="form-text text-muted">Minimum 5 caractères</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">Requeue</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Requeue One --}}
<div class="modal fade" id="requeueOneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Requeue Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.payments.webhooks.stuck.requeueOne') }}" id="requeueOneForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="provider" id="requeueOneProvider">
                    <input type="hidden" name="id" id="requeueOneId">
                    <input type="hidden" name="minutes" value="{{ $minutes }}">
                    
                    <div class="mb-3">
                        <label class="form-label">Raison <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required minlength="5" 
                                  placeholder="Expliquez pourquoi vous requeue cet événement..."></textarea>
                        <small class="form-text text-muted">Minimum 5 caractères</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">Requeue</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.event-checkbox');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateSelectedIds();
        });
    }
    
    // Update selected IDs
    function updateSelectedIds() {
        const selected = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => ({
                provider: cb.dataset.provider,
                id: parseInt(cb.dataset.id)
            }));
        
        const bulkIdsInput = document.getElementById('bulkSelectedIds');
        if (bulkIdsInput) {
            bulkIdsInput.value = JSON.stringify(selected);
        }
    }
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedIds);
    });
    
    // Requeue one button
    const requeueOneBtns = document.querySelectorAll('.requeue-one-btn');
    const requeueOneModal = new bootstrap.Modal(document.getElementById('requeueOneModal'));
    
    requeueOneBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('requeueOneProvider').value = this.dataset.provider;
            document.getElementById('requeueOneId').value = this.dataset.id;
            requeueOneModal.show();
        });
    });

    // Reset window button
    const resetWindowBtns = document.querySelectorAll('.reset-window-btn');
    const resetWindowModal = new bootstrap.Modal(document.getElementById('resetWindowModal'));
    
    resetWindowBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('resetWindowProvider').value = this.dataset.provider;
            document.getElementById('resetWindowId').value = this.dataset.id;
            resetWindowModal.show();
        });
    });
});
</script>
@endpush




