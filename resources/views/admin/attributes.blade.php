@extends("layouts.admin")
@section("title", "Cechy")

@section("content")

<x-magazyn-section title="Cechy podstawowe">
    <x-slot:buttons>
        @foreach ([
            [route("primary-colors-list"), "Kolory nadrzędne", true],
            [route("main-attributes-edit"), "Dodaj nową", true],
            [route("main-attributes-prune"), "Usuń nieużywane", userIs("Administrator")],
        ] as [$route, $label, $conditions])
            @if ($conditions)
            <a class="button" href="{{ $route }}">{{ $label }}</a>
            @endif
        @endforeach
    </x-slot:buttons>

    <p>
        Poniżej znajdują się kolory dostawców zaimportowane w toku synchronizacji.
        Produkty przechowywane w systemie posiadają przypisaną nazwę koloru, odpowiadającą jednemu z poniższych.
        Jeśli ten kolor nie posiada koloru nadrzędnego, systemy pokażą jego oryginalną nazwę wraz z kafelkiem błędnego koloru:
    </p>
    <x-color-tag />
    <p>
        W przeciwnym wypadku kafelek pokazywać będzie informacje o kolorze nadrzędnym.
    </p>

    <div>
        @php
            $data = (request("show") == "missing")
                ? $mainAttributes->whereNull("primary_color_id")
                : (request("show") == "filled"
                    ? $mainAttributes->whereNotNull("primary_color_id")
                    : $mainAttributes
                )
        @endphp

        <hr>

        <div class="flex-right middle">
            <p>Wyświetlam {{ $data->count() }} pozycji, z czego {{ $data->whereNotNull("primary_color_id")->count() }} posiada przypisany kolor nadrzędny</p>
            @foreach ([
                [route("attributes"), "Pokaż wszystkie", !empty(request("show"))],
                [route("attributes", ["show" => "missing"]), "Pokaż nieopisane", request("show") != "missing"],
                [route("attributes", ["show" => "filled"]), "Pokaż opisane", request("show") != "filled"],
            ] as [$route, $label, $conditions])
                @if ($conditions)
                <a class="button" href="{{ $route }}">{{ $label }}</a>
                @endif
            @endforeach
        </div>

        <div class="grid" style="--col-count: 4">
            @forelse ($data as $attribute)
            <div>
                <div class="flex-right middle">
                    <span>{{ $attribute->id }}</span>
                    <x-color-tag :color="$attribute->primaryColor" />
                    <a href="{{ route("main-attributes-edit", $attribute->id) }}">{{ $attribute->name }}</a>

                    @isset ($productExamples[$attribute->name])
                    <small class="ghost">({{ $productExamples[$attribute->name]
                        ->map(fn ($exs, $source) => ($source ?: "własne") . ": " . $exs->count())
                        ->join(", ") }})</small>
                    @endisset
                </div>
            </div>
            @empty
            <span class="ghost">Brak {{ empty(request("show")) ? "zdefiniowanych" : "" }} cech podstawowych</span>
            @endforelse
        </div>
    </div>
</x-magazyn-section>

<x-magazyn-section title="Cechy dodatkowe">
    <x-slot:buttons>
        <a class="button" href="{{ route("attributes-edit") }}">Dodaj cechę</a>
    </x-slot:buttons>

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
</x-magazyn-section>

@endsection
