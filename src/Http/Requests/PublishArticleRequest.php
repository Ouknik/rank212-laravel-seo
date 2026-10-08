<?php

namespace SeoSaas\LaravelSeo\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class PublishArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Middleware handles cryptographic authorization
    }

    /**
     * Get the data to be validated, strictly bound to the signed JSON body.
     *
     * @return array
     */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : $this->all();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contract_version'       => ['required', 'string', 'in:1.0'],
            'store_id'               => ['required', 'integer', 'min:1'],
            'article_id'             => ['required', 'integer', 'min:1'],
            'content_version_hash'   => ['required', 'string', 'size:64'],
            'slug'                   => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'title'                  => ['required', 'string', 'max:255'],
            'h1'                     => ['required', 'string', 'max:255'],
            'content_html'           => ['required', 'string', 'min:10'],
            'seo'                    => ['required', 'array'],
            'seo.meta_title'         => ['required', 'string', 'max:255'],
            'seo.meta_description'   => ['required', 'string', 'max:500'],
            'seo.primary_keyword'    => ['required', 'string', 'max:100'],
            'seo.secondary_keywords' => ['nullable', 'array'],
            'featured_image_url'     => ['nullable', 'url', 'max:2048'],
            'published_at'           => ['nullable', 'date'],
            'action'                 => ['nullable', 'string', 'in:publish,receive'],
            'publish'                => ['nullable', 'boolean'],
        ];
    }

    /**
     * Return custom validation messages.
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug must be URL-safe (lowercase alphanumeric separated by single hyphens).',
        ];
    }

    /**
     * Handle failed validation and return consistent JSON response.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success'    => false,
            'error_code' => 'VALIDATION_FAILED',
            'message'    => 'Invalid publishing payload structure.',
            'errors'     => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
