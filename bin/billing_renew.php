<?php
declare(strict_types=1);

/**
 * bin/billing-renew.php — run daily via cron:
 *   0 1 * * * php /path/to/project/bin/billing-renew.php >> storage/logs/billing.log 2>&1
 *
 * Sweeps subscriptions whose current period has ended: rolls the period
 * forward and generates the next invoice, or marks them past_due if the
 * prior invoice was never paid. Does NOT attempt to charge anyone — this
 * project has no auto-charge gateway wired up yet (ManualGateway requires a
 * human to record the payment). When an async gateway is added, extend
 * SubscriptionBillingService::runRenewalSweep() to also call
 * $gateway->charge() automatically for tenants who have a saved payment
 * method, instead of just generating an invoice and waiting.
 */

require __DIR__ . '/../vendor/autoload.php';

use Core\Env;

Env::load(dirname(__DIR__) . '/.env');

$container = require __DIR__ . '/../bootstrap/app.php';

$billing = $container->get(\Modules\Billing\Services\SubscriptionBillingService::class);

$results = $billing->runRenewalSweep();

foreach ($results as $r) {
    printf(
        "[%s] subscription #%d -> %s\n",
        date('Y-m-d H:i:s'),
        $r['subscription_id'],
        $r['action']
    );
}

printf("[%s] Renewal sweep complete. %d subscription(s) processed.\n", date('Y-m-d H:i:s'), count($results));
