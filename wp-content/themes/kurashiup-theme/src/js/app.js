import '../css/app.css';

console.log('KurashiUp theme loaded');

const trackAmazonClicks = () => {
    const links = document.querySelectorAll('[data-kurashiup-track-click="true"]');

    if (!links.length || !window.kurashiupData?.ajaxUrl || !window.kurashiupData?.nonce) {
        return;
    }

    links.forEach((link) => {
        link.addEventListener('click', () => {
            const productId = link.dataset.productId;

            if (!productId) {
                return;
            }

            const payload = new URLSearchParams({
                action: 'kurashiup_track_amazon_click',
                nonce: window.kurashiupData.nonce,
                productId,
            });

            if (navigator.sendBeacon) {
                const blob = new Blob([payload.toString()], {
                    type: 'application/x-www-form-urlencoded; charset=UTF-8',
                });

                navigator.sendBeacon(window.kurashiupData.ajaxUrl, blob);
                return;
            }

            fetch(window.kurashiupData.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                },
                body: payload.toString(),
                credentials: 'same-origin',
                keepalive: true,
            }).catch(() => {});
        });
    });
};

trackAmazonClicks();
