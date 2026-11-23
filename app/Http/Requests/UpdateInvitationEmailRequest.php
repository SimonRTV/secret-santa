<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvitationEmailRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['present', 'nullable', 'string', 'email', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['email' => 'adresse e-mail'];
    }

    /**
     * The address in lower case, or null when the participant removed it.
     */
    public function email(): ?string
    {
        $email = mb_strtolower(trim((string) $this->validated('email')));

        return $email === '' ? null : $email;
    }
}
