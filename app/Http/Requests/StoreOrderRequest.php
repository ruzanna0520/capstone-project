<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() != null;
    }

    public function rules(): array
    {
        $rules = [
            'products' => ['required', 'array', 'min:1'],
            'products.*.id' => ['required', 'integer', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
        ];

        if ($this->user()?->is_admin) {
            $rules['user_id'] = [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('is_admin', false);
                }),
            ];
        } else {
            $rules['user_id'] = ['prohibited'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Необходимо выбрать покупателя.',
            'user_id.exists' => 'Выбранный покупатель не существует или не является клиентом.',
            'user_id.prohibited' => 'Невозможно указать пользователя для этого действия.',
            'products.required' => 'Заказ должен содержать хотя бы один товар.',
            'products.min' => 'Заказ должен содержать хотя бы один товар.',
            'products.*.id.required' => 'ID товара обязателен.',
            'products.*.id.exists' => 'Один из выбранных товаров не найден.',
            'products.*.quantity.required' => 'Количество товара обязательно.',
            'products.*.quantity.min' => 'Количество товара должно быть не менее 1.',
        ];
    }
}
