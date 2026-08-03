<?php

namespace App\Http\Requests\Api\V1\Task;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
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
            "title" => ["sometimes","required","string" ,"max:255"],
            "description" => ["sometimes","string"],
            "status" => ["sometimes", Rule::in(["pending","in_progress","done"])],
            "priority" => ["sometimes", Rule::in(["low","medium","high"])],
            "due_date" => ["sometimes","date","after_or_equal:today"]
        ];
    }
}
