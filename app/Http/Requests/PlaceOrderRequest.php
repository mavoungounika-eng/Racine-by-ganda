<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request pour la validation du formulaire de commande
 * 
 * Utilisé dans CheckoutController@placeOrder
 */
class PlaceOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isClient() && auth()->user()->status === 'active';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name'       => 'required|string|max:255',
            'email'           => 'required|email',
            'phone'           => 'required|string|max:50',
            'address_line1'   => 'required|string|max:255',
            'city'            => 'required|string|max:255',
            'country'         => 'required|string|max:255',
            'shipping_method' => 'required|in:home_delivery,showroom_pickup',
            'payment_method'  => 'required|in:mobile_money,monetbil,card,cash_on_delivery',
        ];
    }

    /**
     * Get the URL to redirect to on a validation error.
     *
     * @return string
     */
    protected function getRedirectUrl(): string
    {
        return route('checkout.index');
    }
}

