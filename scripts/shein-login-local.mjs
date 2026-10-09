import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import readline from 'node:readline/promises';
import { chromium } from 'playwright-chromium';

const directory = path.resolve('storage/app/shein-session');
const profileDirectory = path.join(directory, 'browser');
const outputFile = path.join(directory, 'session.json');
fs.mkdirSync(directory, { recursive: true, mode: 0o700 });
fs.chmodSync(directory, 0o700);

const terminal = readline.createInterface({ input: process.stdin, output: process.stdout });
let browser;
try {
  browser = await chromium.launchPersistentContext(profileDirectory, {
    headless: false,
    // English UAE browser locale avoids preferring the ar.shein.com storefront.
    locale: 'en-AE',
    viewport: { width: 1250, height: 900 },
  });
  const page = browser.pages()[0] || await browser.newPage();
  await page.goto('https://www.shein.com/', { waitUntil: 'domcontentloaded', timeout: 45_000 });
  process.stdout.write([
    '',
    'افتحي حسابك في SHEIN عبر https://www.shein.com في نافذة Chromium وكمّلي تسجيل الدخول بنفسك.',
    'تأكدي أن بلد التسوق الإمارات وأن حساب الشراء ظاهر.',
    'لا تكتبي كلمة مرور SHEIN في Terminal أو في سلتك.',
    '',
  ].join('\n'));
  await terminal.question('بعد تسجيل الدخول الصحيح اضغطي Enter لحفظ الجلسة محليًا (Ctrl+C للإلغاء)... ');

  const raw = await browser.storageState();
  const sheinHost = host => host === 'shein.com' || host.endsWith('.shein.com');
  const cookies = (raw.cookies || []).filter(c => {
    const domain = String(c.domain || '').replace(/^\./, '').toLowerCase();
    return sheinHost(domain) && c.secure === true;
  });
  const origins = (raw.origins || []).filter(o => {
    try { const u = new URL(o.origin); return u.protocol === 'https:' && sheinHost(u.hostname); }
    catch { return false; }
  });
  if (cookies.length === 0) throw new Error('لم تُحفظ Cookies SHEIN. تأكدي من تسجيل الدخول وحاولي مجددًا.');
  const state = JSON.stringify({ cookies, origins }, null, 2);
  // Cookies and localStorage are sensitive bearer credentials.
  fs.writeFileSync(outputFile, state, { encoding: 'utf8', mode: 0o600, flag: 'w' });
  fs.chmodSync(outputFile, 0o600);
  process.stdout.write('\nنجح حفظ ملف الجلسة محليًا.\n');
  process.stdout.write('ارفعي الملف من لوحة مدير سلتك فقط: ' + outputFile + '\n');
  process.stdout.write('الجلسة لا يتم تفعيلها للزبائن إلا بعد اختبار تطابق الأسعار.\n');
} catch (error) {
  process.stderr.write(String(error?.message || 'تعذر حفظ الجلسة') + '\n');
  process.exitCode = 1;
} finally {
  await browser?.close().catch(() => {});
  terminal.close();
}
