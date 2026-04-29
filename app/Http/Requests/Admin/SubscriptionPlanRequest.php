<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionPlanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'              => 'required|string|max:255',
            'badge_color'       => 'nullable|string|max:50',
            'badge_text'        => 'nullable|string|max:100',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'monthly_price'     => 'required|numeric|min:0',
            'yearly_price'      => 'required|numeric|min:0',
            
            'features'          => 'nullable|array',
            'sort_order'        => 'integer|min:0',
            'is_popular'        => 'boolean',
            'is_active'         => 'boolean',
            'products_limit'    => 'required',
            'customer_limit'    => 'required',
        'yearly_products_limit' => 'required',
        'yearly_customer_limit' => 'required'
        ];
    }

    protected function prepareForValidation()
    {
        $featuresInput = $this->input('features');

        if ($featuresInput !== null) {
            if (is_string($featuresInput) && !empty($featuresInput)) {
                $featuresArray = preg_split('/\r\n|\r|\n/', $featuresInput);
                $featuresArray = array_filter(array_map('trim', $featuresArray));
                $featuresArray = array_values($featuresArray);
            } elseif (is_array($featuresInput)) {
                $featuresArray = array_filter(array_map('trim', $featuresInput));
                $featuresArray = array_values($featuresArray);
            } else {
                $featuresArray = [];
            }
        } else {
            $featuresArray = [];
        }

        $yearlyTotal = $this->input('yearly_total');
        if (empty($yearlyTotal) && $this->has('yearly_price')) {
            $yearlyTotal = $this->yearly_price;
        }

        $this->merge([
            'is_popular' => $this->has('is_popular') ? 1 : 0,
            'is_active'  => $this->has('is_active') ? 1 : 0,
            'yearly_total' => $yearlyTotal,
            'features' => $featuresArray,
        ]);
    }

    public function messages()
    {
        return [
            'name.required'      => 'The plan name is required.',
            'name.unique'        => 'This plan name already exists.',

            'monthly_price.required' => 'Monthly price is required.',
            'monthly_price.min'  => 'Monthly price cannot be negative.',
            
            'yearly_price.required' => 'Yearly price is required.',
            'yearly_price.min'   => 'Yearly price cannot be negative.',
        ];
    }
}
