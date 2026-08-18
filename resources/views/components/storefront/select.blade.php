@props([
    'options' => [],
    'placeholder' => '',
])

@php
    $id = $attributes->get('id') ?? 'select';
    $listboxId = $id.'-listbox';
    $name = $attributes->get('name');
    $wireModel = $attributes->whereStartsWith('wire:model');
    $triggerAttributes = $attributes->except([
        'name',
        'value',
        ...array_keys($wireModel->getAttributes()),
    ]);

    /** @var list<array{value: string, label: string}> $normalized */
    $normalized = collect($options)
        ->map(function (mixed $option): array {
            if (is_array($option)) {
                $value = (string) ($option['value'] ?? '');

                return [
                    'value' => $value,
                    'label' => (string) ($option['label'] ?? $value),
                ];
            }

            $value = (string) $option;

            return [
                'value' => $value,
                'label' => $value,
            ];
        })
        ->values()
        ->all();
@endphp

<div
    class="relative mt-1"
    x-data="{
        open: false,
        value: '',
        active: 0,
        placeholder: @js($placeholder),
        options: @js($normalized),
        listboxId: @js($listboxId),
        init() {
            this.syncFromInput()
        },
        syncFromInput() {
            this.value = this.$refs.input?.value ?? ''
            const index = this.options.findIndex((option) => option.value === this.value)
            this.active = index >= 0 ? index : 0
        },
        get selectedLabel() {
            const selected = this.options.find((option) => option.value === this.value)

            return selected ? selected.label : this.placeholder
        },
        optionId(index) {
            return this.listboxId + '-option-' + index
        },
        close() {
            this.open = false
        },
        toggle() {
            this.open = ! this.open
            if (this.open) {
                this.syncFromInput()
            }
        },
        choose(value) {
            this.value = value
            this.$refs.input.value = value
            this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }))
            this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }))
            this.close()
            this.$nextTick(() => this.$refs.trigger.focus())
        },
        move(delta) {
            if (! this.open) {
                this.open = true
                this.syncFromInput()

                return
            }

            const count = this.options.length

            if (count === 0) {
                return
            }

            this.active = (this.active + delta + count) % count
            this.$nextTick(() => {
                document.getElementById(this.optionId(this.active))?.scrollIntoView({ block: 'nearest' })
            })
        },
        chooseActive() {
            if (! this.open) {
                this.open = true
                this.syncFromInput()

                return
            }

            const option = this.options[this.active]

            if (option) {
                this.choose(option.value)
            }
        },
    }"
    x-on:click.outside="close()"
    x-on:keydown.escape.prevent="close()"
>
    <input
        type="hidden"
        x-ref="input"
        @if (filled($name))
            name="{{ $name }}"
        @endif
        {{ $wireModel }}
    >

    <button
        type="button"
        x-ref="trigger"
        role="combobox"
        aria-haspopup="listbox"
        aria-controls="{{ $listboxId }}"
        x-on:click="toggle()"
        x-on:keydown.arrow-down.prevent="move(1)"
        x-on:keydown.arrow-up.prevent="move(-1)"
        x-on:keydown.enter.prevent="chooseActive()"
        x-on:keydown.space.prevent="chooseActive()"
        x-on:keydown.home.prevent="open = true; active = 0"
        x-on:keydown.end.prevent="open = true; active = Math.max(options.length - 1, 0)"
        x-bind:aria-expanded="open.toString()"
        x-bind:aria-activedescendant="open ? optionId(active) : false"
        {{ $triggerAttributes->class('flex min-h-11 w-full items-center justify-between gap-2 rounded-brand border border-border bg-surface px-3 text-left focus:ring-2 focus:ring-accent') }}
    >
        <span class="min-w-0 flex-1 truncate" x-bind:class="value ? 'text-fg' : 'text-muted'" x-text="selectedLabel"></span>
        <span class="shrink-0 text-muted transition-transform motion-reduce:transition-none" x-bind:class="open && 'rotate-180'">
            <x-icon name="chevron-down" size="sm" />
        </span>
    </button>

    <ul
        id="{{ $listboxId }}"
        role="listbox"
        x-cloak
        x-show="open"
        x-transition:enter="transition duration-150 ease-out motion-reduce:transition-none"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition duration-100 ease-in motion-reduce:transition-none"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto overscroll-contain rounded-brand border border-border bg-surface py-1 shadow-sm"
    >
        @foreach ($normalized as $index => $option)
            <li
                id="{{ $listboxId }}-option-{{ $index }}"
                role="option"
                x-bind:aria-selected="value === @js($option['value'])"
                x-on:mouseenter="active = {{ $index }}"
                x-on:mousedown.prevent
                x-on:click="choose(@js($option['value']))"
                x-bind:class="{
                    'bg-accent/15 text-accent': value === @js($option['value']),
                    'bg-surface-2': active === {{ $index }} && value !== @js($option['value']),
                }"
                class="flex min-h-11 cursor-pointer items-center justify-between gap-2 px-3 text-sm"
            >
                <span>{{ $option['label'] }}</span>
                <span class="shrink-0 text-accent" x-show="value === @js($option['value'])" x-cloak>
                    <x-icon name="check" size="sm" />
                </span>
            </li>
        @endforeach
    </ul>
</div>
