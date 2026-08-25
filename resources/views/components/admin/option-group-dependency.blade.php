@props([
    'optionGroup',
    'earlierGroups' => [],
    'setParentMethod',
    'toggleMethod',
    'setParentArgs' => [],
    'toggleArgs' => [],
])

@php
    $dependsOnKey = (string) ($optionGroup['depends_on_key'] ?? '');
    $dependsOnValues = is_array($optionGroup['depends_on_values'] ?? null) ? $optionGroup['depends_on_values'] : [];
    $childValues = is_array($optionGroup['values'] ?? null) ? $optionGroup['values'] : [];
    $parent = collect($earlierGroups)->firstWhere('key', $dependsOnKey);
    $parentValues = is_array($parent['values'] ?? null) ? $parent['values'] : [];

    $setParentArgList = collect($setParentArgs)
        ->map(fn (mixed $arg): string => is_int($arg) || is_float($arg) ? (string) $arg : \Illuminate\Support\Js::from($arg))
        ->implode(', ');
    $setParentPrefix = $setParentArgList === '' ? '' : $setParentArgList.', ';

    $toggleArgList = collect($toggleArgs)
        ->map(fn (mixed $arg): string => is_int($arg) || is_float($arg) ? (string) $arg : \Illuminate\Support\Js::from($arg))
        ->implode(', ');
    $togglePrefix = $toggleArgList === '' ? '' : $toggleArgList.', ';
@endphp

<div class="option-group-dependency stack">
    <div class="field">
        <label>ขึ้นกับตัวเลือก</label>
        <select
            class="select"
            wire:change="{{ $setParentMethod }}({{ $setParentPrefix }}$event.target.value)"
        >
            <option value="" @selected($dependsOnKey === '')>ไม่ขึ้นกับตัวเลือกอื่น</option>
            @foreach ($earlierGroups as $earlier)
                @php
                    $earlierKey = (string) ($earlier['key'] ?? '');
                    $earlierDepends = (string) ($earlier['depends_on_key'] ?? '');
                @endphp
                @if ($earlierKey !== '' && $earlierDepends === '')
                    <option value="{{ $earlierKey }}" @selected($dependsOnKey === $earlierKey)>
                        {{ ($earlier['label'] ?? '') !== '' ? $earlier['label'] : $earlierKey }}
                    </option>
                @endif
            @endforeach
        </select>
    </div>

    @if ($dependsOnKey !== '' && $parent !== null)
        <div class="stack">
            <span class="field-caption">ค่าที่อนุญาตตามตัวเลือกต้นทาง</span>
            @forelse ($parentValues as $parentValue)
                @php
                    $allowed = is_array($dependsOnValues[$parentValue] ?? null)
                        ? $dependsOnValues[$parentValue]
                        : [];
                @endphp
                <div class="dependency-allow-row" wire:key="dep-{{ $dependsOnKey }}-{{ $parentValue }}">
                    <p class="dependency-parent-value">{{ $parentValue }}</p>
                    <div class="dependency-value-chips" role="group" aria-label="ค่าที่อนุญาตสำหรับ {{ $parentValue }}">
                        @foreach ($childValues as $childValue)
                            <button
                                type="button"
                                class="tag-chip dependency-chip {{ in_array($childValue, $allowed, true) ? 'is-selected' : '' }}"
                                wire:click="{{ $toggleMethod }}({{ $togglePrefix }}{{ \Illuminate\Support\Js::from($parentValue) }}, {{ \Illuminate\Support\Js::from($childValue) }})"
                            >
                                {{ $childValue }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="meta">เพิ่มค่าในกลุ่มต้นทางก่อน</p>
            @endforelse
        </div>
    @endif
</div>
