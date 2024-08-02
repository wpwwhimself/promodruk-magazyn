@extends("layouts.admin")
@section("title", "Synchronizacje")

@section("content")

<style>
.table {
    --col-count: 3;
    grid-template-columns: repeat(var(--col-count), auto);
}
</style>
<div class="table">
    <span class="head">Dostawca</span>
    <span class="head">Stan</span>
    <span class="head">Ostatni import</span>
    <hr>

    @foreach ($synchronizations as $sync)
    <span>{{ $sync->supplier_name }}</span>
    <a href="{{ route('synch-enable', ['supplier_name' => $sync->supplier_name, 'enabled' => intval(!$sync->enabled)]) }}">
        @if ($sync->enabled)
        <span class="success">Włączona</span>
        @else
        <span class="danger">Wyłączona</span>
        @endif
    </a>
    <span>{{ $sync->last_sync_started_at }}</span>
    @endforeach
</div>

@endsection
