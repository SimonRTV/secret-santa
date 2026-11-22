<?php

namespace App\Http\Requests;

use App\Models\Family;
use App\Models\FamilyMember;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFamilyMemberRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', $this->uniqueInFamily()],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'prénom'];
    }

    /**
     * Two participants with the same name would make every invitation ambiguous.
     */
    protected function uniqueInFamily(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            /** @var Family $family */
            $family = $this->route('family');
            /** @var FamilyMember|null $current */
            $current = $this->route('member');
            $name = mb_strtolower(trim((string) $value));
            $taken = $family->members()
                ->when($current, fn ($query) => $query->whereKeyNot($current->id))
                ->pluck('name')
                ->contains(fn (string $existing): bool => mb_strtolower($existing) === $name);

            if ($taken) {
                $fail('Ce prénom est déjà dans la liste. Ajoutez une initiale pour les distinguer.');
            }
        };
    }
}
