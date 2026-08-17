@if ($activities->hasPages())
    <div class="cash-pagination-summary text-muted small">
        Hiển thị {{ $activities->firstItem() }}–{{ $activities->lastItem() }} / {{ $activities->total() }} kết quả
    </div>
    <nav aria-label="Phân trang danh sách thu chi">
        {{ $activities->links('pagination::bootstrap-4') }}
    </nav>
@endif
