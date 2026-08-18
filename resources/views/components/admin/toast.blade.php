<div
    id="admin-toast"
    wire:persist="admin-toast"
    data-admin-toast
    class="toast-host"
    role="status"
    aria-live="polite"
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
    x-init="show(@js(session('status')))"
    x-on:admin-toast.window="show($event.detail.message)"
>
    <div
        class="toast"
        x-show="visible"
        x-cloak
        x-text="message"
    ></div>
</div>
