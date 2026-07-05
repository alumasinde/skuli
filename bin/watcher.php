<?php
declare(strict_types=1);

/**
 * bin/watcher.php — a simple health monitor, run on a schedule (cron):
 *   every 5 minutes:  ../5 * * * * php /path/to/project/bin/watcher.php
 *
 * Deliberately NOT a Sentry/Datadog integration — this project has one
 * Composer dependency and no external monitoring service configured yet.
 * This reuses infrastructure that already exists (Logger's file-based
 * error log, MailerInterface, the subscription tables) rather than adding
 * a new one. It checks three things, in order of severity:
 *
 *   1. Can we even connect to the database? (if not, stop immediately —
 *      nothing else below can be checked meaningfully anyway)
 *   2. Have any ERROR-level lines been written to today's Logger log
 *      since the last time this script ran?
 *   3. Are there overdue (past due_date, still 'open') subscription
 *      invoices — i.e. tenants who should have been billed/dunned?
 *
 * If anything is found, ONE consolidated email goes to
 * SUPERADMIN_ALERT_EMAIL via the existing MailerInterface — LogMailer
 * today, a real SMTP/API mailer once one is wired in, with zero changes
 * needed here. If nothing is found, it just prints "All clear" for the
 * cron log and exits — no email noise on a normal run.
 */

require __DIR__ . '/../vendor/autoload.php';

use Core\Env;
use Core\Mail\MailerInterface;

Env::load(dirname(__DIR__) . '/.env');

$container = require __DIR__ . '/../bootstrap/app.php';

$issues = [];

// ── 1. Database connectivity ────────────────────────────────────────────────
try {
    $pdo = \Core\Database::connection();
    $pdo->query('SELECT 1');
} catch (\Throwable $e) {
    $issues[] = "CRITICAL: Cannot connect to the database — {$e->getMessage()}";

    // Nothing else below is meaningful if the DB itself is unreachable —
    // send the alert now and stop, rather than let every subsequent check
    // throw the same underlying error redundantly.
    sendAlert($container, $issues);
    fwrite(STDERR, "Watcher: DATABASE DOWN — alert sent, exiting.\n");
    exit(1);
}

// ── 2. Recent ERROR-level log lines since last run ──────────────────────────
$markerFile = dirname(__DIR__) . '/storage/logs/.watcher_last_run';
$lastRun    = is_file($markerFile) ? (int) file_get_contents($markerFile) : (time() - 300);

$logFile = dirname(__DIR__) . "/storage/logs/app-" . date('Y-m-d') . '.log';
$errorCount = 0;
$sampleErrors = [];

if (is_file($logFile)) {
    foreach (file($logFile, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        $entry = json_decode($line, true);
        if (!is_array($entry) || ($entry['level'] ?? '') !== 'ERROR') {
            continue;
        }
        $ts = strtotime($entry['timestamp'] ?? '');
        if ($ts !== false && $ts >= $lastRun) {
            $errorCount++;
            if (count($sampleErrors) < 5) {
                $sampleErrors[] = "[{$entry['timestamp']}] {$entry['message']}";
            }
        }
    }
}

if ($errorCount > 0) {
    $issues[] = "{$errorCount} ERROR-level log entr" . ($errorCount === 1 ? 'y' : 'ies') . " since the last check:\n"
        . implode("\n", $sampleErrors)
        . ($errorCount > 5 ? "\n...and " . ($errorCount - 5) . ' more.' : '');
}

// ── 3. Overdue subscription invoices ─────────────────────────────────────────
try {
    $billing = $container->get(\Modules\Billing\Services\SubscriptionBillingService::class);
    $overdue = array_filter(
        $billing->listOpenInvoices(),
        static fn ($inv) => strtotime($inv['due_date']) < strtotime('today')
    );
    if (!empty($overdue)) {
        $names = array_map(static fn ($inv) => $inv['tenant_name'] . ' (' . $inv['invoice_no'] . ')', $overdue);
        $issues[] = count($overdue) . ' overdue invoice(s): ' . implode(', ', $names);
    }
} catch (\Throwable $e) {
    // Don't let a billing-check failure hide a genuine DB-down alert that
    // may have already fired above — just note it and move on.
    $issues[] = "Could not check overdue invoices: {$e->getMessage()}";
}

// ── Report ───────────────────────────────────────────────────────────────────
file_put_contents($markerFile, (string) time());

if (empty($issues)) {
    echo "[" . date('Y-m-d H:i:s') . "] Watcher: All clear.\n";
    exit(0);
}

sendAlert($container, $issues);
echo "[" . date('Y-m-d H:i:s') . "] Watcher: " . count($issues) . " issue(s) found — alert sent.\n";

function sendAlert(\DI\Container $container, array $issues): void
{
    $mailer = $container->get(MailerInterface::class);
    $to     = Env::get('SUPERADMIN_ALERT_EMAIL', 'admin@schoolms.co.ke');

    $body = "<p>The watcher found the following issue(s):</p><ul>";
    foreach ($issues as $issue) {
        $body .= '<li><pre style="white-space:pre-wrap;">' . htmlspecialchars($issue) . '</pre></li>';
    }
    $body .= '</ul>';

    $mailer->send($to, '[SchoolMS Watcher] ' . count($issues) . ' issue(s) detected', $body);
}
