<?php

declare(strict_types=1);

use Core\Env;
use Core\Mail\LogMailer;
use Core\Mail\MailerInterface;
use Core\Billing\Gateways\ManualGateway;
use Core\Billing\Gateways\PaymentGatewayInterface;
use DI\ContainerBuilder;

require __DIR__ . '/../vendor/autoload.php';

Env::load(dirname(__DIR__) . '/.env');

$builder = new ContainerBuilder();
$builder->useAutowiring(true);

$builder->addDefinitions([

    // ── Database ─────────────────────────────────────────────────────────────
    PDO::class => function () {
        $host    = Env::get('DB_HOST', '127.0.0.1');
        $port    = Env::get('DB_PORT', '3306');
        $db      = Env::get('DB_DATABASE', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');
        $user    = Env::get('DB_USERNAME', 'root');
        $pass    = Env::get('DB_PASSWORD', '');

        return new PDO(
            "mysql:host={$host};port={$port};dbname={$db};charset={$charset}",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ]
        );
    },

    // ── Phase 2: Billing ─────────────────────────────────────────────────────
    // Swap ManualGateway for MpesaGateway/StripeGateway here later — nothing
    // in Modules\Billing needs to change.
    PaymentGatewayInterface::class => \DI\autowire(ManualGateway::class),

    // ── Phase 3: Mail + Demo Requests ────────────────────────────────────────
    // Swap LogMailer for a real SMTP/API mailer here later.
    MailerInterface::class => \DI\autowire(LogMailer::class),

    // DemoRequestService needs the super admin alert address — pulled from
    // .env so it's not hardcoded. Add SUPERADMIN_ALERT_EMAIL to .env.example
    // alongside the existing DB_*/JWT_SECRET vars.
    \Modules\Marketing\Services\DemoRequestService::class => \DI\autowire()
        ->constructorParameter('superAdminEmail', \DI\factory(
            fn () => Env::get('SUPERADMIN_ALERT_EMAIL', 'admin@schoolms.co.ke')
        )),

]);

return $builder->build();