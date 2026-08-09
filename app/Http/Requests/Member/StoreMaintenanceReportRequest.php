<?php

namespace App\Http\Requests\Member;

use App\Models\MaintenanceReport;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isMember() || $this->user()->isStaff();
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'category'    => ['required', 'string', 'in:' . implode(',', [
                MaintenanceReport::CATEGORY_BUILDING,
                MaintenanceReport::CATEGORY_FURNITURE,
                MaintenanceReport::CATEGORY_BOOKS,
                MaintenanceReport::CATEGORY_ELECTRONICS,
                MaintenanceReport::CATEGORY_OTHERS,
            ])],
            'description' => ['required', 'string'],
            'priority'    => ['required', 'in:low,medium,high'],
            'image'       => ['nullable', 'image', 'max:2048'],
            'library_id'  => ['required', 'exists:libraries,id'],
        ];
    }
}
