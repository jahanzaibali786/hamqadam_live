<?php

use App\Support\ProfileDropdownReferenceData;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ProfileDropdownReferenceData::seed();
    }

    public function down(): void
    {
        // Reference dropdown data is intentionally left in place.
        // Admins may edit these rows after deployment, so rollback should not delete user-managed options.
    }
};
