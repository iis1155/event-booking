<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date'        => ['required', 'date', 'after:now'],
            'location'    => ['required', 'string', 'max:255'],
            'status'      => ['nullable', Rule::enum(EventStatus::class)],
        ];
    }
}
