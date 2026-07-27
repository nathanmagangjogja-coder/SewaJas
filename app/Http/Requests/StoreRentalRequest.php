<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ganti false → true
    }

    public function rules(): array
    {
        return [
            'customer_id'           => 'required|exists:customers,id',
            'rental_date'           => 'required|date',
            'duration_days'         => 'required|integer|min:1',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'guarantee.type'        => 'required|string',
            'guarantee.id_name'     => 'nullable|string',
            'guarantee.id_number'   => 'nullable|string',
            'guarantee.description' => 'nullable|string',
            'discount'              => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string',
        ];
    }
}