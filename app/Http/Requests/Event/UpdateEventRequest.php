<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'date'        => ['sometimes', 'date', 'after:now'],
            'location'    => ['sometimes', 'string', 'max:255'],
            'status'      => ['sometimes', Rule::enum(EventStatus::class)],
        ];
    }
}
