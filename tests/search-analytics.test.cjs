const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync('wp-content/themes/kurashiup-theme/assets/js/search-analytics.js', 'utf8');
async function run({ installed = true, rows = [], status = 200, code, settings = { propertyID: 'sc-domain:example.test' }, start = '2026-10-01', end = '2026-10-31' } = {}) {
    const label = { textContent: '' };
    const cell = { textContent: '', nextElementSibling: { textContent: '' }, dataset: { searchUrl: 'https://example.test/article/' } };
    const period = { addEventListener() {} };
    const calls = [];
    const config = { root: 'https://example.test/wp-json/google-site-kit/v1/', nonce: 'test', start, end, installed };
    vm.runInNewContext(source, {
        window: { kurashiupSearchAnalytics: config }, URL, AbortSignal, Date: class extends Date { constructor(...args) { super(...(args.length ? args : ['2026-11-10T06:00:00Z'])); } }, Set, Number,
        document: { getElementById: id => id === 'kurashiup-search-status' ? label : period, querySelectorAll: () => [cell] },
        fetch: async (url, options) => {
            calls.push({ url, options });
            const isSettings = url.pathname.endsWith('/settings');
            return { ok: isSettings || status === 200, status: isSettings ? 200 : status,
                json: async () => isSettings ? settings : (status === 200 ? rows : { code, message: 'error' }) };
        },
    });
    for (let i = 0; i < 30; i++) await Promise.resolve();
    return { label, cell, calls };
}
test('unconnected does not request Google or fabricate zero', async () => {
    const r = await run({ installed: false });
    assert.equal(r.calls.length, 0); assert.equal(r.cell.textContent, '未連携');
});
test('daily API rows are summed for month', async () => {
    const r = await run({ rows: [
        { keys: ['2026-10-01'], impressions: 10, clicks: 2 },
        { keys: ['2026-10-31'], impressions: 20, clicks: 3 },
    ] });
    assert.match(r.cell.textContent, /^30（取得2日分/);
    assert.match(r.cell.nextElementSibling.textContent, /^5（取得2日分/);
    assert.equal(r.calls[1].url.searchParams.get('dimensions'), 'date');
    assert.equal(r.calls[1].url.searchParams.get('url'), 'https://example.test/article/');
    assert.equal(r.calls[1].options.headers['X-WP-Nonce'], 'test');
});
test('empty rows mean missing data', async () => assert.equal((await run()).cell.textContent, 'データなし／反映待ち'));
test('permission failure differs from API failure', async () => {
    assert.match((await run({ status: 403 })).cell.textContent, /^権限・認証エラー/);
    assert.match((await run({ status: 500 })).cell.textContent, /^API取得エラー/);
});
test('inactive module and missing property are unconnected', async () => {
    assert.match((await run({ status: 403, code: 'module_not_active' })).cell.textContent, /^未連携/);
    assert.equal((await run({ settings: {} })).cell.textContent, '未連携');
});
test('malformed metrics cannot appear as NaN or real values', async () => {
    assert.match((await run({ rows: [{ keys: ['2026-10-01'], impressions: 'oops', clicks: 1 }] })).cell.textContent, /^API取得エラー/);
});

test('daily selection requests a single date', async () => {
    const r = await run({ start: '2026-10-01', end: '2026-10-01', rows: [{ keys: ['2026-10-01'], impressions: 12, clicks: 3 }] });
    assert.match(r.cell.textContent, /^12（取得1日分/);
    assert.equal(r.calls[1].url.searchParams.get('startDate'), r.calls[1].url.searchParams.get('endDate'));
});
test('future and expired periods cannot be mistaken for zero', async () => {
    assert.match((await run({ start: '2024-01-01', end: '2024-01-31' })).cell.textContent, /取得可能期間外/);
    assert.match((await run({ start: '2026-12-01', end: '2026-12-31' })).cell.textContent, /取得可能期間外/);
});
