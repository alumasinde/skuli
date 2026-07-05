<?php
declare(strict_types=1);

namespace Modules\Marketing\Services;

use Core\AuditLogger;
use Core\Mail\MailerInterface;
use Modules\Marketing\Repositories\DemoRequestRepository;
use Modules\Tenants\Services\TenantProvisioningService;
final class DemoRequestService
{
    public function __construct(
        private readonly DemoRequestRepository $repo,
        private readonly TenantProvisioningService $tenants,
        private readonly MailerInterface $mailer,
        private readonly AuditLogger $audit,
        private readonly string $superAdminEmail
    ) {}

    /**
     * @param array{school_name:string, contact_name:string, email:string, phone?:string,
     *              student_count_range?:string, message?:string, source?:string} $d
     */
    public function submit(array $d, ?string $ip): array
    {
        $this->validate($d);

        // Soft anti-spam: silently accept but don't re-notify on rapid
        // resubmission from the same email — avoids flooding the super
        // admin's inbox from a form double-click or a bot loop.
        $isDuplicate = $this->repo->recentDuplicate(strtolower(trim($d['email'])));

        $id = $this->repo->create([
            'school_name'         => trim($d['school_name']),
            'contact_name'        => trim($d['contact_name']),
            'email'               => strtolower(trim($d['email'])),
            'phone'               => trim($d['phone'] ?? '') ?: null,
            'student_count_range' => $d['student_count_range'] ?? null,
            'message'             => trim($d['message'] ?? '') ?: null,
            'source'              => $d['source'] ?? null,
            'ip_address'          => $ip,
        ]);

        $request = $this->repo->findById($id);

        // Confirmation to the prospect — always sent, duplicate or not.
        $this->mailer->send(
            $request['email'],
            'We received your demo request',
            $this->confirmationEmailBody($request)
        );

        if (!$isDuplicate) {
            $this->mailer->send(
                $this->superAdminEmail,
                'New demo request: ' . $request['school_name'],
                $this->internalAlertBody($request),
                replyTo: $request['email']
            );
        }

        $this->audit->log('demo_request_submitted', 'demo_request', $id, ['email' => $request['email']]);

        return $request;
    }

    public function list(?string $status = null): array
    {
        return $this->repo->listAll($status);
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function countNew(): int
    {
        return $this->repo->countNew();
    }

    public function markContacted(int $id, int $reviewerId, ?string $notes = null): void
    {
        $this->repo->updateStatus($id, 'contacted', $reviewerId, $notes);
        $this->audit->log('demo_request_contacted', 'demo_request', $id);
    }

    public function decline(int $id, int $reviewerId, ?string $notes = null): void
    {
        $this->repo->updateStatus($id, 'declined', $reviewerId, $notes);
        $this->audit->log('demo_request_declined', 'demo_request', $id);
    }
    public function approveAndProvision(int $demoRequestId, array $overrides, ?int $reviewerId): array
    {
        $demo = $this->repo->findById($demoRequestId);
        if (!$demo) {
            throw new \InvalidArgumentException('Demo request not found.');
        }
        if ($demo['status'] === 'approved') {
            throw new \RuntimeException('This demo request has already been provisioned.');
        }

        $nameParts = preg_split('/\s+/', trim($demo['contact_name']), 2);

        $provisionData = array_merge([
            'tenant_name'      => $demo['school_name'],
            'plan'             => 'free',
            'school_name'      => $demo['school_name'],
            'school_code'      => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $demo['school_name']), 0, 6)) ?: 'SCH',
            'school_email'     => $demo['email'],
            'school_phone'     => $demo['phone'],
            'admin_first_name' => $nameParts[0] ?? 'Admin',
            'admin_last_name'  => $nameParts[1] ?? '',
            'admin_email'      => $demo['email'],
        ], $overrides);

        $result = $this->tenants->provision($provisionData, $reviewerId);

        $this->repo->linkTenant($demoRequestId, $result['tenant_id']);
        $tenantRow = $this->tenants->getById($result['tenant_id']);
$loginUrl  = $this->buildLoginUrl($tenantRow['domain'] ?? null);
 

        $this->audit->log('demo_request_approved', 'demo_request', $demoRequestId, [
            'tenant_id' => $result['tenant_id'],
        ]);

    
       $this->mailer->send(
    $demo['email'],
    'Your school account is ready',
    $this->approvedEmailBody($demo, $loginUrl)
);

        return $result;
    }

    private function validate(array $d): void
    {
        foreach (['school_name', 'contact_name', 'email'] as $field) {
            if (trim((string) ($d[$field] ?? '')) === '') {
                throw new \InvalidArgumentException("Field \"{$field}\" is required.");
            }
        }
        if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Please enter a valid email address.');
        }
    }

    private function confirmationEmailBody(array $r): string
    {
        $name = htmlspecialchars(explode(' ', $r['contact_name'])[0] ?? $r['contact_name']);
        return "<p>Hi {$name},</p>"
             . "<p>Thanks for requesting a demo of SchoolMS for <strong>" . htmlspecialchars($r['school_name']) . "</strong>. "
             . "Our team will reach out within one business day to schedule a time.</p>"
             . "<p>— The SchoolMS Team</p>";
    }

    private function internalAlertBody(array $r): string
    {
        return "<p>New demo request:</p><ul>"
             . '<li><strong>School:</strong> ' . htmlspecialchars($r['school_name']) . '</li>'
             . '<li><strong>Contact:</strong> ' . htmlspecialchars($r['contact_name']) . ' &lt;' . htmlspecialchars($r['email']) . '&gt;</li>'
             . '<li><strong>Phone:</strong> ' . htmlspecialchars($r['phone'] ?? '—') . '</li>'
             . '<li><strong>Size:</strong> ' . htmlspecialchars($r['student_count_range'] ?? '—') . '</li>'
             . '<li><strong>Message:</strong> ' . nl2br(htmlspecialchars($r['message'] ?? '—')) . '</li>'
             . '</ul>';
    }

    private function approvedEmailBody(array $r, ?string $loginUrl): string
{
    $name = htmlspecialchars(explode(' ', $r['contact_name'])[0] ?? $r['contact_name']);
    $linkHtml = $loginUrl
        ? '<p>Your school\'s sign-in page: <a href="' . htmlspecialchars($loginUrl) . '">' . htmlspecialchars($loginUrl) . '</a><br>'
          . '(Bookmark this — it\'s the direct link straight to your school, not the general site.)</p>'
        : '<p>We\'ll send your sign-in link separately shortly.</p>';
 
    return "<p>Hi {$name},</p>"
         . "<p>Great news — your SchoolMS account for <strong>" . htmlspecialchars($r['school_name']) . "</strong> is set up.</p>"
         . $linkHtml
         . "<p>We'll follow up shortly with your login credentials.</p>"
         . "<p>— The SchoolMS Team</p>";
}

private function buildLoginUrl(?string $domain): ?string
        {
            return $domain ? \Core\UrlBuilder::tenantLoginUrl($domain) : null;
        }
}
