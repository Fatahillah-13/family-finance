<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class Audit
{
    public static function log(
        int $householdId,
        ?User $actor,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        array $meta = []
    ): AuditLog {
        return AuditLog::create([
            'household_id' => $householdId,
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $meta ?: null,
            'occurred_at' => now(),
        ]);
    }
}
