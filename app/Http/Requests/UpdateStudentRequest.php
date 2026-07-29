<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $studentId = $this->route('student');

        return [
            // Basic Information
            'name_bn' => 'sometimes|required|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => [
                'nullable', 'email:rfc', 'max:255',
                Rule::unique('users', 'email')->ignore($this->route('student')?->user_id),
            ],
            'phone' => 'sometimes|required|string|max:20',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female,Other',
            'blood_group' => 'nullable|string|max:5',
            'religion' => 'nullable|string|max:50',
            // Use file validation with mimes instead of image rule for better compatibility
            'profile_image_file' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:204800',
            'profile_image_url' => 'nullable|string|max:500',

            // Academic Information
            'class' => 'nullable|string|max:50',
            'batch_id' => 'nullable|exists:batches,id',
            'registration_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('students', 'registration_no')->ignore($studentId),
            ],
            'course_id' => 'nullable|exists:courses,id',
            'profession' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:single,married',
            'admission_purpose' => 'nullable|string|max:1000',
            'admission_mode' => 'nullable|in:online,offline',
            'admission_status' => 'nullable|in:pending,approved,rejected',

            // Parent/Guardian Information
            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'father_occupation' => 'nullable|string|max:100',
            'mother_occupation' => 'nullable|string|max:100',
            'father_phone' => 'nullable|string|max:20',
            'mother_phone' => 'nullable|string|max:20',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',

            // Present Address
            'present_village' => 'nullable|string|max:255',
            'present_po' => 'nullable|string|max:255',
            'present_ps' => 'nullable|string|max:255',
            'present_dist' => 'nullable|string|max:255',
            'present_holding' => 'nullable|string|max:100',

            // Permanent Address
            'permanent_village' => 'nullable|string|max:255',
            'permanent_po' => 'nullable|string|max:255',
            'permanent_ps' => 'nullable|string|max:255',
            'permanent_dist' => 'nullable|string|max:255',
            'permanent_holding' => 'nullable|string|max:100',

            // Educational Background - SSC
            'ssc_institute' => 'nullable|string|max:255',
            'ssc_board' => 'nullable|string|max:100',
            'ssc_year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'ssc_gpa' => 'nullable|numeric|min:0|max:5',
            'ssc_group' => 'nullable|string|max:100',

            // Educational Background - HSC
            'hsc_institute' => 'nullable|string|max:255',
            'hsc_board' => 'nullable|string|max:100',
            'hsc_year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'hsc_gpa' => 'nullable|numeric|min:0|max:5',
            'hsc_group' => 'nullable|string|max:100',

            // Educational Background - Undergraduate
            'undergrad_institute' => 'nullable|string|max:255',
            'undergrad_board' => 'nullable|string|max:100',
            'undergrad_year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'undergrad_gpa' => 'nullable|numeric|min:0|max:4',
            'undergrad_group' => 'nullable|string|max:100',
            'undergrad_department' => 'nullable|string|max:255',

            // Payment Information
            'course_name' => 'nullable|string|max:255',
            'total_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',

            // Other
            'featured' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name_bn.required' => 'Student name is required.',
            'phone.required' => 'Phone number is required.',
            'registration_no.unique' => 'This registration number is already in use by another student.',
            'batch_id.exists' => 'The selected batch does not exist.',
            'profile_image_file.file' => 'The profile image must be a valid file.',
            'profile_image_file.mimes' => 'The profile image must be a JPEG, PNG, GIF, or WebP file.',
            'profile_image_file.max' => 'The profile image must not exceed 20MB.',
            'profile_image_url.url' => 'The profile image URL must be a valid URL.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Log file upload details for debugging
        if ($this->hasFile('profile_image_file')) {
            $file = $this->file('profile_image_file');
            \Log::info('Profile image file upload attempt (update)', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'is_valid' => $file->isValid(),
                'error_code' => $file->getError(),
            ]);
        }

        // Convert checkbox to boolean
        if ($this->has('featured')) {
            $this->merge([
                'featured' => filter_var($this->featured, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
