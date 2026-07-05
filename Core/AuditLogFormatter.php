<?php
declare(strict_types=1);

namespace Core;

final class AuditLogFormatter
{
    private const ACTION_PHRASES = [
        'login_success'          => 'Logged In',
        'login_failed'           => 'Failed to Log In',
        'logout'                 => 'Logged Out',
        'create'                 => 'Created',
        'update'                 => 'Updated',
        'delete'                 => 'Deleted',
        'deactivate'             => 'Deactivated',
        'activate'               => 'Activated',
        'assignSubject'          => 'Assigned a Subject to',
        'removeSubject'          => 'Removed a Subject from',
        'setCurrent'             => 'Set as Current',
        'mark'                   => 'Marked Attendance for',
        'provision'              => 'Provisioned',
        'suspend'                => 'Suspended',
        'reactivate'             => 'Reactivated',
        'subscribe'              => 'Subscribed',
        'cancel'                 => 'Cancelled',
        'payment_recorded'       => 'Recorded a Payment for',
        'payment_confirmed'      => 'Confirmed Payment for',
        'invoice_generated'      => 'Generated an Invoice for',
        'demo_request_submitted' => 'Submitted a Demo Request',
        'demo_request_contacted' => 'Marked as Contacted',
        'demo_request_declined'  => 'Declined',
        'demo_request_approved'  => 'Approved',
        'resetPassword'          => 'Reset the Password for',
    ];

    private const OMIT_ENTITY = ['login_success', 'login_failed', 'logout'];

    public static function format(array $row): string
    {
        $actor  = self::actorName($row);
        $phrase = self::actionPhrase($row['action']);
        $entity = in_array($row['action'], self::OMIT_ENTITY, true)
            ? ''
            : ' ' . self::humanizeEntity($row['entity']);
        $when   = self::formatWhen($row['created_at']);

        return trim("{$actor} {$phrase}{$entity}") . " at {$when}";
    }

    /** @return string[] one formatted sentence per row, same order as input. */
    public static function formatMany(array $rows): array
    {
        return array_map(self::format(...), $rows);
    }

    private static function actorName(array $row): string
    {
        $first = trim((string) ($row['actor_first_name'] ?? ''));
        $last  = trim((string) ($row['actor_last_name'] ?? ''));
        $name  = trim("{$first} {$last}");

        if ($name !== '') {
            return $name;
        }

        $meta = self::decodeMeta($row['meta'] ?? null);
        if (!empty($meta['email'])) {
            return $meta['email'];
        }

        return 'Someone';
    }

    private static function actionPhrase(string $action): string
    {
        return self::ACTION_PHRASES[$action]
            ?? ucfirst(str_replace('_', ' ', $action)); // graceful fallback for any action not in the map yet
    }

    private static function humanizeEntity(string $entity): string
    {
        return ucwords(str_replace('_', ' ', $entity));
    }

    private static function formatWhen(string $createdAt): string
    {
        $ts = strtotime($createdAt);
        if ($ts === false) {
            return $createdAt; 
        }
        
        return date('h:i:s a', $ts) . ' on ' . date('d/m/Y', $ts);
    }

    private static function decodeMeta(?string $meta): array
    {
        if (!$meta) {
            return [];
        }
        $decoded = json_decode($meta, true);
        return is_array($decoded) ? $decoded : [];
    }
}