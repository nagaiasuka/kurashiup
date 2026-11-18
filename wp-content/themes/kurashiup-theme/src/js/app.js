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

// Imported comparison charts share their product images with model sections.
// Use real SVG links so keyboard navigation and browser fragment history work.
const linkGuideCharts = () => {
    const content = document.querySelector('.review-guide-content');
    if (!content) return;

    const models = new Map();
    content.querySelectorAll(':is(h2, h3, h4, h5, h6)[id^="model-"]').forEach((heading) => {
        let sibling = heading.nextElementSibling;
        while (sibling && !/^H[1-6]$/.test(sibling.tagName)) {
            const img = sibling.matches('img') ? sibling : sibling.querySelector('img');
            if (img) {
                models.set(new URL(img.getAttribute('src'), document.baseURI).href, heading);
                break;
            }
            sibling = sibling.nextElementSibling;
        }
    });

    content.querySelectorAll('svg').forEach((svg) => {
        let linked = false;
        svg.querySelectorAll('image').forEach((img) => {
            if (img.closest('a')) return;
            const src = img.getAttribute('href') || img.getAttributeNS('http://www.w3.org/1999/xlink', 'href');
            if (!src) return;
            const heading = models.get(new URL(src, document.baseURI).href);
            if (!heading) return;

            const link = document.createElementNS('http://www.w3.org/2000/svg', 'a');
            link.setAttribute('href', `#${heading.id}`);
            link.setAttribute('aria-label', `${heading.textContent.trim()}の詳細へ`);
            link.classList.add('guide-chart-link');
            // Include the plotted point, card and caption as click targets.
            const rect = img.previousElementSibling;
            const point = rect?.previousElementSibling;
            const connector = point?.previousElementSibling;
            const caption = img.nextElementSibling;
            img.before(link);
            if (rect?.tagName.toLowerCase() === 'rect' && point?.tagName.toLowerCase() === 'circle') {
                if (connector?.tagName.toLowerCase() === 'path') link.append(connector);
                link.append(point);
            }
            if (rect?.tagName.toLowerCase() === 'rect') link.append(rect);
            link.append(img);
            if (caption?.tagName.toLowerCase() === 'text') link.append(caption);
            linked = true;
        });
        // role=img hides descendant links from assistive technology.
        if (linked) svg.setAttribute('role', 'group');
    });
};

linkGuideCharts();
