<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create books');
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'author_name'    => ['required', 'string', 'max:255'],
            'isbn'           => ['required', 'string', 'max:20', 'unique:books,isbn'],
            'genre_ids'      => ['required', 'array', 'min:1'],
            'genre_ids.*'    => ['exists:genres,id'],
            'category_id'    => ['required', 'exists:categories,id'],
            'publisher_id'   => ['required', 'exists:publishers,id'],
            'library_id'     => ['nullable', 'exists:libraries,id'],
            'published_year' => ['nullable', 'integer', 'min:1000', 'max:' . date('Y')],
            'format'         => ['required', 'in:hardcover,paperback,ebook,audiobook'],
            'pages'          => ['required', 'integer', 'min:1'],
            'language'       => ['required', 'string', 'max:50'],
            'description'    => ['required', 'string'],
            'cover_image'    => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'Please enter the book title.',
            'author_name.required' => 'Please enter the author name.',
            'isbn.required'        => 'Please enter the ISBN.',
            'isbn.unique'          => 'This ISBN already exists in the library.',
            'genre_ids.required'   => 'Please select at least one genre.',
            'genre_ids.min'        => 'Please select at least one genre.',
            'genre_ids.*.exists'   => 'One or more selected genres are invalid.',
            'category_id.required' => 'Please select a category.',
            'publisher_id.required'=> 'Please select a publisher.',
            'format.required'      => 'Please select a book format.',
            'format.in'            => 'Selected book format is invalid.',
            'pages.required'       => 'Please enter the number of pages.',
            'pages.min'            => 'Number of pages must be at least 1.',
            'language.required'    => 'Please select a language.',
            'description.required' => 'Please enter a book description.',
            'cover_image.image'    => 'The cover image must be an image file.',
            'cover_image.mimes'    => 'Cover image must be JPG, PNG, GIF, or WEBP.',
            'cover_image.max'      => 'Cover image size must not exceed 10MB.',
        ];
    }
}
