/* Reuse Site Kit REST permissions and OAuth. No credentials or search queries stored. */
(() => {
    'use strict';
    const config = window.kurashiupSearchAnalytics;
    const status = document.getElementById('kurashiup-search-status');
    if (!config || !status) return;
    const period = document.getElementById('kurashiup-analytics-period');
    const dateInput = document.getElementById('kurashiup-analytics-date');
    period.addEventListener('change', () => {
        const old = dateInput.value;
        dateInput.type = period.value === 'month' ? 'month' : 'date';
        dateInput.value = period.value === 'month' ? old.slice(0, 7) : (old.length === 7 ? old + '-01' : old);
    });
    const cells = [...document.querySelectorAll('[data-search-url]')];
    const show = (cell, text, clicks = text) => {
        cell.textContent = text;
        cell.nextElementSibling.textContent = clicks;
    };
    const all = text => cells.forEach(cell => show(cell, text));
    const request = async (path, params = {}) => {
        const url = new URL(config.root + path);
        Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));
        const response = await fetch(url, {
            credentials: 'same-origin', headers: { 'X-WP-Nonce': config.nonce },
            signal: AbortSignal.timeout(30000),
        });
        const data = await response.json();
        if (!response.ok) {
            const error = new Error(data.message || 'API取得エラー');
            error.status = response.status;
            error.code = data.code;
            throw error;
        }
        return data;
    };
    const errorLabel = error => {
        if (error.code === 'module_not_active' || error.code === 'rest_no_route') return '未連携（Site Kit設定を確認）';
        if (error.status === 401 || error.status === 403) return '権限・認証エラー（Site Kitで再接続／権限を確認）';
        return 'API取得エラー（再読み込みで再試行）';
    };
    async function run() {
        if (!config.installed) {
            status.textContent = 'Search Console：未連携（Google Site Kitを導入・接続してください）';
            all('未連携');
            return;
        }
        try {
            const settings = await request('modules/search-console/data/settings');
            if (!settings.propertyID) {
                status.textContent = 'Search Console：未連携（Site Kit → 設定 → Search Consoleでプロパティを接続）';
                all('未連携');
                return;
            }
            status.textContent = 'Search Console：接続済み・取得中（' + settings.propertyID + '）';
            const ptNow = new Date(new Date().toLocaleString('en-US', { timeZone: 'America/Los_Angeles' }));
            const earliest = new Date(ptNow);
            earliest.setMonth(earliest.getMonth() - 16);
            const day = date => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
            // Do not present a partially available month as a complete total.
            if (config.start < day(earliest) || config.start > day(ptNow)) {
                all('取得可能期間外／期間未完了');
                status.textContent = 'Search Console：接続済み。対象期間は保持期間外、または未来の日付を含みます。完了した日・月を選択してください。';
                return;
            }
            const effectiveEnd = config.end > day(ptNow) ? day(ptNow) : config.end;
            let errors = 0;
            // Sequential requests bound Google API concurrency to one; only 20 visible articles.
            for (const cell of cells) {
                show(cell, '取得中…');
                try {
                    const rows = await request('modules/search-console/data/searchanalytics', {
                        startDate: config.start, endDate: effectiveEnd, dimensions: 'date',
                        url: cell.dataset.searchUrl, rowLimit: 32,
                    });
                    if (!Array.isArray(rows)) throw new Error('Invalid response');
                    if (!rows.length) {
                        show(cell, 'データなし／反映待ち');
                        continue;
                    }
                    const dates = new Set();
                    let impressions = 0, clicks = 0;
                    for (const row of rows) {
                        const date = row.keys?.[0];
                        if (!/^\d{4}-\d{2}-\d{2}$/.test(date) || date < config.start || date > config.end || dates.has(date)
                            || !Number.isFinite(row.impressions) || row.impressions < 0
                            || !Number.isFinite(row.clicks) || row.clicks < 0) throw new Error('Invalid daily row');
                        dates.add(date);
                        impressions += row.impressions;
                        clicks += row.clicks;
                    }
                    const suffix = '（取得' + dates.size + '日分・暫定値を含む場合あり）';
                    show(cell, impressions.toLocaleString('ja-JP') + suffix, clicks.toLocaleString('ja-JP') + suffix);
                } catch (error) {
                    errors++;
                    show(cell, errorLabel(error));
                }
            }
            status.textContent = 'Search Console：接続済み（' + settings.propertyID + '）・取得完了' + (errors ? '（' + errors + '記事でエラー）' : '') + '。取得対象：' + config.start + '〜' + effectiveEnd + '。検索データは太平洋時間の日次合算・未返却日は含みません。';
        } catch (error) {
            const label = errorLabel(error);
            status.textContent = 'Search Console：' + label;
            all(label);
        }
    }
    run();
})();
