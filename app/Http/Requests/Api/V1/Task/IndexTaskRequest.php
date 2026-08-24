<?php

namespace App\Http\Requests\Api\V1\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('user_id', $this->user()->id)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail) {
                    foreach (explode(',', (string) $value) as $field) {
                        if (! in_array(ltrim(trim($field), '-'), Task::SORTABLE_FIELDS, true)) {
                            $fail("The {$field} sort field is not supported.");
                        }
                    }
                },
            ],
        ];
    }
}
