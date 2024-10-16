<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    // manipula falhas de validação
    protected function failedValidation(Validator $validator){
        throw new HttpresponseException(response()->json([
            'status' => false,
            'erros' => $validator->errors(),
        ], 422));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user');
        return [
            'name' => 'required',
            'email' => ['required', 'email', 'unique:users,email,' . ($userId ? $userId->id : 'NULL'),
                'regex:/^[\w\.-]+@educacao\.am\.gov\.br$/',
            ],
            'password' => 'required|min:6',
        ];
    }

    public function messages(): array
    {
        return[
            'name.required' => "Campo nome é obrigatorio",
            'email.required' => "Campo email é obrigatorio",
            'email.email' => "Campo email precisa ser do tipo email",
            'email.unique' => "Este email já está cadastrado",
            'email.regex' => "O email precisa ser da rede seduc",
            'password.required' => "Campo senha obrigatorio",
            'password.min' => "A senha precisa ter no minimo 6 caracteres",
        ];
    }
}
