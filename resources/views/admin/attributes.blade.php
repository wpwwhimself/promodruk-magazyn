@extends("layouts.admin")
@section("title", "Cechy")

@section("content")

<h2>Cechy podstawowe</h2>

@if (userIs("Administrator"))
<a href="{{ route("main-attributes-prune") }}">Usuń nieużywane cechy podstawowe</a>
@endif

@if ($mainAttributes->where("color", "")->count() > 0)
<h3 class="danger">Nieopisane</h3>
<ul>
    @foreach ($mainAttributes->where("color", "") as $attribute)
    <li>
        <a href="{{ route("main-attributes-edit", $attribute->id) }}">
            <x-color-tag :color="$attribute" />
            {{ $attribute->name }}
        </a>

        @if (isset($productExamples[$attribute->name]))
        <small class="ghost">(
            <strong class="success" {{ Popper::interactive()->pop($productExamples[$attribute->name]->pluck("id")->join(" | ")) }}>
                {{ $productExamples[$attribute->name]->count() }} prod.,
            </strong>
            np. {{ $productExamples[$attribute->name]->random()->id }}
        )</small>
        @endif
    </li>
    @endforeach
</ul>

<h3>Pełna lista</h3>
@endif

<ul>
    @forelse ($mainAttributes as $attribute)
    <li>
        <a href="{{ route("main-attributes-edit", $attribute->id) }}">
            <x-color-tag :color="$attribute" />
            {{ $attribute->name }}
        </a>
    </li>
    @empty
    <li class="ghost">Brak zdefiniowanych cech podstawowych</li>
    @endforelse
</ul>

<a href="{{ route("main-attributes-edit") }}">Dodaj cechę główną</a>

<h2>Cechy dodatkowe</h2>

<ul>
    @forelse ($attributes as $attribute)
    <li>
        <a href="{{ route("attributes-edit", $attribute->id) }}">{{ $attribute->name }}</a>
        ({{ $attribute->variants()->count() }} wariantów)
    </li>
    @empty
    <li class="ghost">Brak zdefiniowanych cech</li>
    @endforelse
</ul>

<a href="{{ route("attributes-edit") }}">Dodaj cechę</a>

@endsection
