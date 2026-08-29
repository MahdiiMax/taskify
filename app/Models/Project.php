<?php

namespace App\Models;

use App\Enums\ProjectColor;
use App\Http\Resources\Api\V1\ProjectResource;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseResource(ProjectResource::class)]
class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'color',
        'user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, class-string>
     */
    protected function casts(): array
    {
        return [
            'color' => ProjectColor::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
