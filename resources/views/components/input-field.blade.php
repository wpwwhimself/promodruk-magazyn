@props([
    'type' => 'text',
    'name',
    'label' => null,
    'autofocus' => false,
    'required' => false,
    "disabled" => false,
    "value" => null,
    "small" => false,
    "hints" => null,
])

<div {{
    $attributes
        // ->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "placeholder", "small"])))
        ->merge(["for" => $name])
        ->class(["input-small" => $small, "input-container"])
    }}>

    @if($type != "hidden" && $label)
    <label for="{{ $name }}">{{ $label }}</label>
    @endif

    @if ($type == "TEXT")
    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $autofocus ? "autofocus" : "" }}
        {{ $required ? "required" : "" }}
        {{ $disabled ? "disabled" : "" }}
        {{ $attributes->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "class"]))) }}
        {{-- onfocus="highlightInput(this)" onblur="clearHighlightInput(this)" --}}
    >{{ html_entity_decode($value) }}</textarea>
    @elseif ($type == "dummy")
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    <input type="text" disabled value="{{ $value }}">
    @else
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        @if ($type == "checkbox" && $value)
        checked
        @endif
        {{ $attributes->merge(["value" => ($type == "checkbox") ? "1" : html_entity_decode($value)]) }}
        {{ $autofocus ? "autofocus" : "" }}
        {{ $required ? "required" : "" }}
        {{ $disabled ? "disabled" : "" }}
        {{ $hints ? "autocomplete=off" : "" }}
        {{ $attributes->filter(fn($val, $key) => (!in_array($key, ["autofocus", "required", "class"]))) }}
        {{-- onfocus="highlightInput(this)" onblur="clearHighlightInput(this)" --}}
    />
    @endif

    @if ($hints)
    <div class="hints flex-right wrap"></div>
    @endif
</div>
