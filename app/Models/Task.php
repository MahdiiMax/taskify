<?php

namespace App\Models;

use App\Http\Resources\Api\V1\TaskResource;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseResource(TaskResource::class)]
class Task extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'user_id',
        'due_date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
