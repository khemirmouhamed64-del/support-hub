<?php

namespace App\Http\Requests\Api;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $columns = implode(',', Ticket::BOARD_COLUMNS);

        return [
            'board_column'       => 'required|in:' . $columns,
            'assigned_to'        => 'nullable|integer|exists:team_members,id',
            'resolution_message' => 'nullable|string',
            'notes'              => 'nullable|string',
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
