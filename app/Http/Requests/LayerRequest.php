<?php

namespace App\Http\Requests;

use App\Models\Layer;
use App\Models\Layup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LayerRequest extends FormRequest
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
        /** @var Layup $layup */
        $layup = $this->route('layup');
        /** @var Layer|null $layer */
        $layer = $this->route('layer');

        return [
            'layer_order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('clt_layers', 'layer_order')
                    ->where(fn ($query) => $query->where('layup_id', $layup->id))
                    ->ignore($layer),
            ],
            'thickness' => ['required', 'numeric', 'min:0'],
            'width' => ['required', 'numeric', 'min:0'],
            'angle' => ['required', 'numeric'],
        ];
    }
}
