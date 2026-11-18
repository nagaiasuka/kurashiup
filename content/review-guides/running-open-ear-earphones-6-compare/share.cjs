const fs = require('fs');
const path = require('path');
const assert = require('node:assert/strict');
const crypto = require('crypto');
const {chromium} = require('playwright');
const {pathToFileURL} = require('url');
const dir = __dirname;
const source = path.resolve(dir, '../../guide-updates/running-open-ear-earphones-6-compare');
(async () => {
 const guide = JSON.parse(fs.readFileSync(path.join(source,'guide.json'),'utf8'));
 const charts = guide.content.match(/<svg\b[\s\S]*?<\/svg>/g);
 assert.equal(charts.length,2);
 const svg = charts.map(chart => chart.replaceAll(/%%GUIDE_UPLOADS%%\/([a-f0-9]+\.jpg)/g, (_,name) => `data:image/jpeg;base64,${fs.readFileSync(path.join(source,'images',name)).toString('base64')}`));
 const html = `<!doctype html><html lang="ja"><meta charset="utf-8"><title>ランニングイヤホン6機種比較・X共有画像</title><style>
 *{box-sizing:border-box}body{margin:0;font-family:-apple-system,BlinkMacSystemFont,'Hiragino Kaku Gothic ProN',sans-serif;color:#233f32}.share{width:1200px;height:630px;display:grid;grid-template-columns:370px minmax(0,1fr);grid-template-rows:minmax(0,1fr);background:white;border:1px solid #dbe4dc;overflow:hidden}.copy{min-height:0;padding:38px 24px 30px 34px;background:#f1f5ee;display:flex;flex-direction:column;align-items:flex-start}.brand{font-size:17px;font-weight:700;letter-spacing:2px;color:#45694f}.kicker{font-size:17px;margin:38px 0 14px}h1{font-size:46px;line-height:1.35;letter-spacing:-2px;margin:0 0 15px}h1 strong{font-size:76px;letter-spacing:-4px}h1 span{font-size:43px}.sub{font-size:21px;font-weight:600;margin:0 0 12px}.note{font-size:14px;line-height:1.8;color:#536b5a;margin:0}.domain{margin-top:auto;font-size:16px;letter-spacing:1px}.visual{min-height:0;padding:12px 14px 12px 12px;display:grid;grid-template-rows:minmax(0,1fr) minmax(0,1fr) 16px;gap:8px}.chart{min-height:0;display:grid;grid-template-rows:24px minmax(0,1fr)}.chart h2{font-size:17px;margin:0 0 3px 6px;font-weight:700}.chart svg{width:100%;height:100%!important;min-height:0;display:block}.visual-note{font-size:11px;color:#536b5a;margin:0 6px}
 </style><div class="share"><div class="copy"><div class="brand">暮らしアップ</div><div class="kicker">耳をふさがず、走りたい。</div><h1>ランニング<br>イヤホン<br><strong>6</strong><span>機種比較</span></h1><p class="sub">重さ × 再生時間</p><p class="note">オープンイヤー・骨伝導を比較<br>メーカー公称値に基づく比較</p><div class="domain">amocreo.com</div></div><div class="visual"><section class="chart"><h2>分離型｜片耳の重量で比較</h2>${svg[0]}</section><section class="chart"><h2>一体型｜バンドを含む重量で比較</h2>${svg[1]}</section><p class="visual-note">※横軸はタイプ別。再生時間は充電ケースを含みません。</p></div></div></html>`;
 fs.writeFileSync(path.join(dir,'Xアイキャッチ確認用.html'),html);
 const browser = await chromium.launch({executablePath:'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',headless:true});
 try {
  const page = await browser.newPage({viewport:{width:1200,height:630},deviceScaleFactor:1});
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto(pathToFileURL(path.join(dir,'Xアイキャッチ確認用.html')).href);
  await page.evaluate(async()=>{await document.fonts.ready;await Promise.all([...document.querySelectorAll('svg image')].map(el=>new Promise((resolve,reject)=>{const i=new Image();i.onload=resolve;i.onerror=reject;i.src=el.getAttribute('href')})))});
  assert.equal(await page.locator('svg image').count(),6);
  assert.equal(await page.locator('.copy').evaluate(el=>el.scrollHeight>el.clientHeight),false);
  assert.equal(await page.locator('svg image, .domain, .visual-note').evaluateAll(els=>els.every(el=>{const r=el.getBoundingClientRect();return r.top>=0&&r.left>=0&&r.bottom<=630&&r.right<=1200})),true,'All product photos and captions fit the image');
  assert.deepEqual(errors,[]);
  const file=path.join(dir,'kurashiup-running-earphones-share.jpg');
  await page.screenshot({path:file,type:'jpeg',quality:92});
  const name=crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex')+'.jpg';
  fs.copyFileSync(file,path.join(source,'images',name));
  guide.featured=name;
  guide.alt='ランニング用イヤホン6機種を重さと最大再生時間で比較。分離型は片耳、一体型はバンドを含む重量で表示';
  fs.writeFileSync(path.join(source,'guide.json'),JSON.stringify(guide,null,2)+'\n');
  console.log(JSON.stringify({image:file,width:1200,height:630,models:6,featured:name,errors}));
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
