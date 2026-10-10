import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const controller = fs.readFileSync('app/Http/Controllers/CartController.php', 'utf8');
const importer = fs.readFileSync('app/Services/CartImport/Browser/SheinBrowserImporter.php', 'utf8');
const adapter = fs.readFileSync('app/Services/CartImport/Adapters/SheinShareAdapter.php', 'utf8');
const config = fs.readFileSync('config/services.php', 'utf8');

test('PHP cart import has scoped execution budget beyond built-in 30-second default', () => {
  assert.match(controller, /parse_url\(\$data\['source_url'\], PHP_URL_HOST\)/);
  assert.match(controller, /str_ends_with\(\$host, '\.shein\.com'\)/);
  assert.match(controller, /set_time_limit\(\$importBudget \+ 45\)/);
  assert.match(controller, /\$this->importer->import\(\$data\['source_url'\]\)/);
  assert.match(config, /SHEIN_BROWSER_TOTAL_BUDGET_SECONDS/);
});

test('all SHEIN browser worker attempts share one deadline', () => {
  assert.match(importer, /private \?float \$importDeadline = null/);
  assert.match(importer, /\$this->importDeadline = microtime\(true\) \+ \$budget/);
  assert.match(importer, /\$secondsLeft = \(\$this->importDeadline/);
  assert.match(importer, /if \(\$secondsLeft < 12\)/);
  assert.match(importer, /'import_budget_exhausted' => true/);
  assert.match(importer, /->timeout\(min\(\$workerSeconds,/);
  assert.match(importer, /'timeoutMs' => \$browserTimeoutMs/);
  assert.match(importer, /str_contains\(class_basename\(\$e\), 'TimedOut'\)/);
  assert.match(importer, /'status' => \$timedOut \? 'timeout' : 'unavailable'/);

  assert.match(importer, /'shared_www'/);
  assert.match(importer, /'shared_mobile_fallback'/);
});

test('timed-out SHEIN carts return review feedback instead of fatal error or guest success', () => {
  assert.match(adapter, /if \(\$browserStatus === 'timeout'\)/);
  assert.match(adapter, /ImportResult::needsReview\(/);
  assert.match(adapter, /انتهت مهلة جلب سلة SHEIN/);
});
