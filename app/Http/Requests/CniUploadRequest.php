<?php
// app/Http/Requests/CniUploadRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CniUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Adapter selon ton système d'auth admin
        return true; // ou : auth()->check() && auth()->user()->isAdmin()
    }

    public function rules(): array
    {
        return [
            'fichier' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',     // 5 Mo en Ko
                'dimensions:min_width=300,min_height=200', // image lisible
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fichier.required'   => 'Veuillez sélectionner une image CNI.',
            'fichier.mimes'      => 'Format non supporté. Utilisez JPG, PNG ou WEBP.',
            'fichier.max'        => 'L\'image ne doit pas dépasser 5 Mo.',
            'fichier.dimensions' => 'L\'image est trop petite (min 300×200 px).',
        ];
    }
}
