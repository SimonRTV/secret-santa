<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWishesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'wishes' => ['present', 'nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['wishes' => 'envies de cadeaux'];
    }

    /**
     * The wishes with one trimmed idea per line, or null when the list is empty.
     */
    public function wishes(): ?string
    {
        $lines = preg_split('/\R/', (string) $this->validated('wishes')) ?: [];
        $wishes = implode("\n", array_filter(array_map(trim(...), $lines), fn (string $line): bool => $line !== ''));

        return $wishes === '' ? null : $wishes;
    }
}
