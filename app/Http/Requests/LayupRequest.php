<?php

namespace App\Http\Requests;

use App\Models\Layup;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LayupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Supplier $supplier */
        $supplier = $this->route('supplier');
        /** @var Layup|null $layup */
        $layup = $this->route('layup');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clt_layups', 'name')
                    ->where(fn ($query) => $query->where('supplier_id', $supplier->id))
                    ->ignore($layup),
            ],
        ];
    }
}
