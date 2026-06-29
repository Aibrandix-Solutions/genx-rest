<?php

namespace App\Support;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function record(
        string $module,
        string $category,
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?int $restaurantId = null,
        ?int $branchId = null,
        ?int $causerId = null,
        ?string $causerName = null,
        ?string $legacySource = null,
        ?int $legacyId = null,
        ?\DateTimeInterface $createdAt = null,
    ): ?ActivityLog {
        try {
            [$resolvedRestaurantId, $resolvedBranchId] = self::resolveTenantIds(
                $subject,
                $restaurantId,
                $branchId
            );

            $user = user();
            $resolvedCauserId = $causerId ?? $user?->id;
            $resolvedCauserName = $causerName ?? $user?->name;

            $attributes = [
                'restaurant_id' => $resolvedRestaurantId,
                'branch_id' => $resolvedBranchId,
                'causer_id' => $resolvedCauserId,
                'causer_name' => $resolvedCauserName,
                'module' => $module,
                'category' => $category,
                'event' => $event,
                'description' => $description,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'properties' => self::sanitizeProperties($properties),
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'legacy_source' => $legacySource,
                'legacy_id' => $legacyId,
                'created_at' => $createdAt ?? now(),
            ];

            if ($legacySource && $legacyId) {
                return ActivityLog::firstOrCreate(
                    ['legacy_source' => $legacySource, 'legacy_id' => $legacyId],
                    $attributes
                );
            }

            return ActivityLog::create($attributes);
        } catch (\Throwable $exception) {
            Log::warning('Activity log write failed: ' . $exception->getMessage(), [
                'event' => $event,
                'description' => $description,
            ]);

            return null;
        }
    }

    public static function recordEvent(
        ActivityEvent $activityEvent,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?int $restaurantId = null,
        ?int $branchId = null,
        ?int $causerId = null,
        ?string $causerName = null,
        ?string $legacySource = null,
        ?int $legacyId = null,
        ?\DateTimeInterface $createdAt = null,
    ): ?ActivityLog {
        return self::record(
            module: $activityEvent->module(),
            category: $activityEvent->category(),
            event: $activityEvent->value,
            description: $description,
            subject: $subject,
            properties: $properties,
            restaurantId: $restaurantId,
            branchId: $branchId,
            causerId: $causerId,
            causerName: $causerName,
            legacySource: $legacySource,
            legacyId: $legacyId,
            createdAt: $createdAt,
        );
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    protected static function resolveTenantIds(
        ?Model $subject,
        ?int $restaurantId,
        ?int $branchId
    ): array {
        if ($restaurantId !== null || $branchId !== null) {
            if ($restaurantId === null && $branchId !== null) {
                $restaurantId = Branch::query()->where('id', $branchId)->value('restaurant_id');
            }

            return [$restaurantId, $branchId];
        }

        if ($subject) {
            if (isset($subject->restaurant_id)) {
                return [(int) $subject->restaurant_id, isset($subject->branch_id) ? (int) $subject->branch_id : null];
            }

            if (isset($subject->branch_id)) {
                $branch = Branch::query()->find($subject->branch_id);

                return [$branch?->restaurant_id ? (int) $branch->restaurant_id : null, (int) $subject->branch_id];
            }
        }

        $currentRestaurant = restaurant();
        $currentBranch = branch();

        return [
            $currentRestaurant ? (int) $currentRestaurant->id : null,
            $currentBranch ? (int) $currentBranch->id : null,
        ];
    }

    protected static function sanitizeProperties(array $properties): array
    {
        $blockedKeys = ['password', 'password_confirmation', 'token', 'api_token', 'secret'];

        $sanitized = [];

        foreach ($properties as $key => $value) {
            if (in_array(strtolower((string) $key), $blockedKeys, true)) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeProperties($value);
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
