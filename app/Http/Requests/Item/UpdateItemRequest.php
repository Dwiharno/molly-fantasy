<?php

namespace App\Http\Requests\Item;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->user() && ! $this->user()->isSuperAdmin()) {
            $this->merge(['store_id' => $this->user()->store_id ?? \App\Models\Store::where('code', 'S040')->value('id')]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->canWrite() ?? false;
    }

    public function rules(): array
    {
        $item = $this->route('item');
        $storeId = $this->user()->isSuperAdmin() ? $this->input('store_id') : $this->user()->store_id;

        return [
            'store_id' => ['required', Rule::exists('stores', 'id')->where('is_active', true)],
            'barcode' => ['required', 'string', 'max:50', Rule::unique('items', 'barcode')->where('store_id', $storeId)->ignore($item->id)],
            'name' => ['required', 'string', 'max:150'],
            'allocation' => ['required', Rule::in(Item::ALLOCATIONS)],
            'category' => ['required', Rule::in(Item::CATEGORIES)],
            'sub_category' => ['required', Rule::in(Item::SUB_CATEGORIES)],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'ticket_redeem_qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'barcode.required' => 'Barcode wajib diisi.',
            'barcode.unique' => 'Barcode sudah digunakan oleh item lain.',
            'name.required' => 'Nama item wajib diisi.',
            'allocation.in' => 'Pilihan Allocation tidak valid.',
            'category.in' => 'Pilihan Category tidak valid.',
            'sub_category.in' => 'Pilihan Sub Category tidak valid.',
        ];
    }
}
