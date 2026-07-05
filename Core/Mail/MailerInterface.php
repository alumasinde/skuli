<?php
declare(strict_types=1);

namespace Core\Mail;

/**
 * MailerInterface — same swap-point pattern as PaymentGatewayInterface in
 * Phase 2. Nothing in the app should call mail() or a specific SMTP client
 * directly; everything depends on this interface. Today LogMailer is bound
 * (writes to storage/logs instead of sending real email, since no SMTP/
 * transactional-email provider is configured yet). Swapping in real email
 * later — PHPMailer, Symfony Mailer, or an API-based provider like Postmark/
 * SES — means one new class + one line in the container.
 */
interface MailerInterface
{
    public function send(string $to, string $subject, string $htmlBody, ?string $replyTo = null): bool;
}
