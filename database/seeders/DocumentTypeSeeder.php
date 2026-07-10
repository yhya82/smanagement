<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        DocumentType::firstOrCreate(
            ['key' => 'birth_certificate'],
            [
                'label' => 'Birth Certificate',
                'is_required' => true,
                'is_active' => true,
                'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png'],
                'max_file_size_bytes' => 5 * 1024 * 1024, // 5MB
            ]
        );

        DocumentType::firstOrCreate(
            ['key' => 'passport_photo'],
            [
                'label' => 'Passport-Size Photo',
                'is_required' => true,
                'is_active' => true,
                'allowed_mime_types' => ['image/jpeg', 'image/png'],
                'max_file_size_bytes' => 2 * 1024 * 1024, // 2MB
            ]
        );
    }
}
