<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Resources\Api\V1\TaskResource;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseResource(TaskResource::class)]
class Task extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Fields that may be used to sort task listings.
     *
     * @var list<string>
     */
    public const SORTABLE_FIELDS = ['title', 'status', 'priority', 'due_date', 'created_at'];

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'user_id',
        'project_id',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereLike('title', "%{$search}%")
                        ->orWhereLike('description', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->when($filters['project_id'] ?? null, fn (Builder $query, int|string $projectId) => $query->where('project_id', $projectId));
    }

    /**
     * Apply sorting from a comma-separated sort string ("-" prefix = descending).
     * Falls back to newest-first when no sort is given.
     */
    public function scopeSort(Builder $query, ?string $sort): Builder
    {
        $fields = array_filter(array_map('trim', explode(',', (string) $sort)));
        if ($fields === []) {
            return $query->latest();
        }
        foreach ($fields as $field) {
            $direction = str_starts_with($field, '-') ? 'desc' : 'asc';
            $column = ltrim($field, '-');
            if (in_array($column, self::SORTABLE_FIELDS, true)) {
                $query->orderBy($column, $direction);
            }
        }

        return $query;
    }
}
