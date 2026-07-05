<?php
declare(strict_types=1);

namespace Core\Mail;


final class LogMailer implements MailerInterface
{
    private string $logPath;

    public function __construct(?string $logPath = null)
    {
        $this->logPath = $logPath ?? dirname(__DIR__, 2) . '/storage/logs/mail.log';
    }

    public function send(string $to, string $subject, string $htmlBody, ?string $replyTo = null): bool
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $entry = sprintf(
            "\n[%s] TO: %s | REPLY-TO: %s\nSUBJECT: %s\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $replyTo ?? '(none)',
            $subject,
            str_repeat('-', 60),
            strip_tags($htmlBody)
        );

        return @file_put_contents($this->logPath, $entry, FILE_APPEND | LOCK_EX) !== false;
    }
}
