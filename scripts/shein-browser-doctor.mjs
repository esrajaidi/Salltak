/**
 * Local Windows/Laragon Playwright preflight. Does not visit SHEIN,
 * access account credentials, or print user paths.
 * Run: npm run browser:doctor
 */
import process from 'node:process';

function output(stage, success, detail) {
  process.stdout.write('[' + (success ? 'PASS' : 'FAIL') + '] ' + stage + ': ' + detail + '\n');
}

output('Node', true, process.version + ' (' + process.platform + ', ' + process.arch + ')');

if (Number(process.versions.node.split('.')[0]) < 20) {
  output('Node', false, 'Use Node.js 22 LTS or a newer supported version.');
  process.exitCode = 1;
} else {
  let chromium;
  try {
    ({ chromium } = await import('playwright-chromium'));
    output('Playwright', true, 'playwright-chromium module can be imported');
  } catch {
    output('Playwright', false, 'Module unavailable. Run npm install.');
    process.exitCode = 1;
  }

  if (chromium) {
    const { existsSync } = await import('node:fs');
    const binary = chromium.executablePath();
    if (!existsSync(binary)) {
      output('Chromium', false, 'Executable not found. Run npx playwright install chromium.');
      process.exitCode = 1;
    } else {
      output('Chromium', true, 'Executable found (path hidden)');
      let browser;
      try {
        browser = await chromium.launch({ headless: true, timeout: 25000 });
        const page = await browser.newPage();
        await page.setContent('<main id="salltak-smoke">OK</main>');
        const value = await page.locator('#salltak-smoke').innerText();
        if (value !== 'OK') throw new Error('smoke_not_ready');
        output('Launch', true, 'Local headless Chromium opened and rendered a test page');
      } catch (error) {
        const msg = String(error?.message || '');
        const reason = /Executable doesn't exist|doesn't exist/i.test(msg)
          ? 'browser_missing'
          : /Access is denied|EPERM|EACCES/i.test(msg)
            ? 'permission_or_security_software'
            : /Target page, context or browser has been closed|Browser closed/i.test(msg)
              ? 'browser_closed_on_start'
              : /Timeout/i.test(msg)
                ? 'browser_start_timeout'
                : 'browser_launch_failed';
        output('Launch', false, reason + '; check Windows security settings and Playwright installation');
        process.exitCode = 1;
      } finally {
        await browser?.close().catch(() => {});
      }
    }
  }
}
if (process.exitCode) {
  process.stdout.write('Check: where.exe node, node -v, npx playwright install chromium\n');
} else {
  process.stdout.write('If Laravel still fails: compare where.exe node with SHEIN_BROWSER_NODE in .env, then run php artisan optimize:clear.\n');
}
