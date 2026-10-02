<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Adds the unguessable identifier behind a certificate's QR code.
 *
 * Scanning a certificate must land on a public page proving the holder is a
 * recognised student, so every student needs a stable, non-sequential handle
 * for that page. Registration numbers are sequential and therefore guessable,
 * which is why they are not used for this.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('verification_token', 64)->nullable()->after('registration_no')->unique();
        });

        // Backfill existing students so already-issued certificates resolve.
        DB::table('students')
            ->select('id')
            ->whereNull('verification_token')
            ->orderBy('id')
            ->chunkById(200, function ($students) {
                foreach ($students as $student) {
                    DB::table('students')
                        ->where('id', $student->id)
                        ->update(['verification_token' => $this->uniqueToken()]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['verification_token']);
            $table->dropColumn('verification_token');
        });
    }

    /**
     * A 40-character token.
     *
     * Length is bounded so the URL stays short enough to survive being printed
     * as a QR code at certificate scale, while remaining far too long to guess.
     */
    private function uniqueToken(): string
    {
        do {
            $token = Str::random(40);
        } while (DB::table('students')->where('verification_token', $token)->exists());

        return $token;
    }
};
