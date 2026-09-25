<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:events,title' . ($this->event ? ',' . $this->event->id : ''),
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'button_link' => 'nullable|url',
            'status' => 'nullable|in:0,1',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->status ? 1 : 0,
        ]);
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'title.unique' => 'This event title already exists.',
            'button_link.url' => 'The button link must be a valid URL.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a jpeg, png, jpg, or gif file.',
            'image.max' => 'The image size must not exceed 2MB.',
        ];
    }
}
