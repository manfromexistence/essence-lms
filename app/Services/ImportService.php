<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportService
{
    public function parseFile(UploadedFile $file): array
    {
        $extension = $file->getClientOriginalExtension();
        
        if (in_array($extension, ['csv', 'txt'])) {
            return $this->parseCsv($file);
        }

        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseExcel($file);
        }

        throw new \Exception('Unsupported file format. Please use CSV or Excel files.');
    }

    protected function parseCsv(UploadedFile $file): array
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $data[] = array_combine($headers, $row);
            }
        }
        
        fclose($handle);
        return ['headers' => $headers, 'data' => $data];
    }

    protected function parseExcel(UploadedFile $file): array
    {
        $rows = Excel::toArray([], $file)[0];
        
        if (empty($rows)) {
            return ['headers' => [], 'data' => []];
        }

        $headers = array_map('trim', $rows[0]);
        $data = [];

        for ($i = 1; $i < count($rows); $i++) {
            if (count($rows[$i]) === count($headers)) {
                $data[] = array_combine($headers, $rows[$i]);
            }
        }

        return ['headers' => $headers, 'data' => $data];
    }

    public function validateData(array $data, string $type): array
    {
        $errors = [];
        $rules = $this->getValidationRules($type);

        foreach ($data as $index => $row) {
            $validator = Validator::make($row, $rules);
            if ($validator->fails()) {
                $errors[$index + 2] = $validator->errors()->all(); // +2 for 1-indexed + header row
            }
        }

        return $errors;
    }

    protected function getValidationRules(string $type): array
    {
        return match ($type) {
            'students' => [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email',
                'phone' => 'nullable|string|max:20',
                'guardian_name' => 'nullable|string|max:255',
                'guardian_phone' => 'nullable|string|max:20',
            ],
            default => [],
        };
    }

    public function previewImport(array $data, string $type): array
    {
        $errors = $this->validateData($data, $type);
        
        return [
            'total' => count($data),
            'valid' => count($data) - count($errors),
            'invalid' => count($errors),
            'errors' => $errors,
            'preview' => array_slice($data, 0, 10),
        ];
    }

    public function executeImport(array $data, string $type, array $mapping = []): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($data as $index => $row) {
                try {
                    $mappedRow = $this->applyMapping($row, $mapping);
                    $this->importRow($mappedRow, $type);
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[$index + 2] = $e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    protected function applyMapping(array $row, array $mapping): array
    {
        if (empty($mapping)) {
            return $row;
        }

        $mapped = [];
        foreach ($mapping as $dbField => $csvField) {
            if (isset($row[$csvField])) {
                $mapped[$dbField] = $row[$csvField];
            }
        }

        return $mapped;
    }

    protected function importRow(array $row, string $type): void
    {
        match ($type) {
            'students' => $this->importStudent($row),
            default => throw new \Exception("Unknown import type: {$type}"),
        };
    }

    public function importStudents(array $data, array $mapping = []): array
    {
        return $this->executeImport($data, 'students', $mapping);
    }

    protected function importStudent(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            $email = $data['email'] ?? null;
            $name = $data['name'] ?? ($data['name_bn'] ?? 'Student');

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(16)),
                'is_active' => true,
            ]);

            $studentRole = Role::where('slug', 'student')->first();
            if ($studentRole) {
                $user->roles()->attach($studentRole->id);
            }

            return Student::create([
                'user_id' => $user->id,
                'name_bn' => $data['name_bn'] ?? $name,
                'phone' => $data['phone'] ?? null,
                'guardian_name' => $data['guardian_name'] ?? null,
                'guardian_phone' => $data['guardian_phone'] ?? null,
                'admission_status' => 'pending',
                'status' => 'active',
            ]);
        });
    }

    public function getImportTypes(): array
    {
        return [
            'students' => [
                'label' => 'Students',
                'required_fields' => ['name'],
                'optional_fields' => ['email', 'phone', 'guardian_name', 'guardian_phone'],
            ],
        ];
    }
}
