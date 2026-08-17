<div
    id="storefront-toast"
    wire:persist="storefront-toast"
    data-storefront-toast
    class="pointer-events-none fixed inset-x-0 bottom-32 z-30 flex justify-center px-4"
    role="alert"
    aria-live="assertive"
    aria-atomic="true"
    x-data="{
        message: '',
        visible: false,
        timer: null,
        show(message) {
            if (! message) {
                return
            }

            this.message = message
            this.visible = true
            clearTimeout(this.timer)
            this.timer = setTimeout(() => { this.visible = false }, 4000)
        },
    }"
    x-on:storefront-toast.window="show($event.detail.message)"
>
    <p
        x-show="visible"
        x-cloak
        x-text="message"
        class="max-w-sm rounded-full bg-danger px-3.5 py-2.5 text-center text-sm font-medium text-surface shadow-sm"
        x-transition:enter="transition duration-200 ease-out motion-reduce:transition-none"
        x-transition:enter-start="translate-y-3 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition duration-200 ease-in motion-reduce:transition-none"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></p>
</div>
