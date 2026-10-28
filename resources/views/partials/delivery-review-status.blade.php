@if($reviewedAt)
    <span class="badge badge-success">Reviewed</span>
    <small class="d-block text-muted mt-1">{{ $reviewedAt }}</small>
@else
    <span class="badge badge-warning">Pending review</span>
    <form method="POST" action="{{ $reviewUrl }}" class="mt-2">
        @csrf
        @method('PATCH')
        <button class="btn btn-sm btn-outline-primary" type="submit">Mark as reviewed</button>
    </form>
@endif
