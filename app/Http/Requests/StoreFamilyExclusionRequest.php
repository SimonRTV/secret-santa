<?php

namespace App\Http\Requests;

use App\Models\Family;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFamilyExclusionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Family $family */
        $family = $this->route('family');
        $inFamily = Rule::exists('family_members', 'id')->where('family_id', $family->id);

        return [
            'member_id' => ['required', 'uuid', $inFamily],
            'excluded_member_id' => ['required', 'uuid', 'different:member_id', $inFamily],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['excluded_member_id.different' => 'Choisissez deux personnes différentes.'];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['member_id' => 'première personne', 'excluded_member_id' => 'seconde personne'];
    }
}
