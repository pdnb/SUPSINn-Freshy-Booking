function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');

    return meta instanceof HTMLMetaElement ? meta.content : '';
}

function loadLiffSdk() {
    return new Promise((resolve, reject) => {
        if (window.liff) {
            resolve(window.liff);

            return;
        }

        const script = document.createElement('script');
        script.src = 'https://static.line-scdn.net/liff/edge/2/sdk.js';
        script.async = true;
        script.onload = () => {
            if (window.liff) {
                resolve(window.liff);
            } else {
                reject(new Error('LIFF SDK missing after load'));
            }
        };
        script.onerror = () => reject(new Error('LIFF SDK failed to load'));
        document.head.appendChild(script);
    });
}

async function rememberLineSession(sessionUrl, idToken) {
    const response = await fetch(sessionUrl, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ id_token: idToken }),
    });

    if (!response.ok) {
        throw new Error(`LINE session failed (${response.status})`);
    }
}

async function bootstrapLiff() {
    const liffId = document.body?.dataset?.liffId;
    const sessionUrl = document.body?.dataset?.lineSessionUrl;

    if (!liffId || !sessionUrl) {
        return;
    }

    try {
        const liff = await loadLiffSdk();
        await liff.init({ liffId });

        if (!liff.isInClient()) {
            return;
        }

        if (!liff.isLoggedIn()) {
            liff.login();

            return;
        }

        const idToken = liff.getIDToken();

        if (!idToken) {
            return;
        }

        await rememberLineSession(sessionUrl, idToken);
    } catch (error) {
        console.warn('[liff]', error);
    }
}

bootstrapLiff();

document.addEventListener('livewire:navigate', () => {
    document.documentElement.classList.add('is-page-loading');
});

document.addEventListener('livewire:navigated', () => {
    document.documentElement.classList.remove('is-page-loading');
});
