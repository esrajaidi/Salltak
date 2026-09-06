import { chromium } from 'playwright-chromium';

let browser;
try {
  browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  await page.setContent('<main id="ok">Cartly browser worker ready</main>');
  const text = await page.locator('#ok').innerText();
  if (text !== 'Cartly browser worker ready') throw new Error('Unexpected browser output');
  console.log('[PASS] Playwright Chromium is installed and launches correctly.');
} catch (error) {
  console.error('[FAIL] Playwright Chromium is not ready.');
  console.error(String(error?.message || error));
  process.exitCode = 1;
} finally {
  await browser?.close().catch(() => {});
}
