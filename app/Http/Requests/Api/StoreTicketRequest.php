<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTicketRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Auth handled by middleware
    }

    public function rules()
    {
        return [
            'external_ticket_id' => 'nullable|integer',
            'reporter_name'      => 'required|string|max:255',
            'reporter_email'     => 'nullable|email|max:255',
            'ticket_type'        => 'required|in:bug,configuration,question,feature_request',
            'module'             => 'required|string|max:100',
            'sub_module'         => 'nullable|string|max:100',
            'priority'           => 'nullable|in:low,medium,high,critical',
            'subject'            => 'required|string|max:255',
            'description'        => 'required|string',
            'steps_to_reproduce' => 'nullable|string',
            'expected_behavior'  => 'nullable|string',
            'browser_url'        => 'nullable|string|max:500',
            'attachments'        => 'nullable|array',
            'attachments.*.name' => 'required_with:attachments|string|max:255',
            'attachments.*.type' => 'required_with:attachments|in:image,video,document',
            'attachments.*.base64' => 'nullable|string',
            'attachments.*.url'    => 'nullable|string|max:500',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
