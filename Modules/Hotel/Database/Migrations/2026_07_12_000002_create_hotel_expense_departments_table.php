<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_expense_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('name');
            $table->string('slug')->nullable()->index();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // Add department_id to hotel_expenses table
        Schema::table('hotel_expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('restaurant_id')->index();
        });

        // Populate default departments for all existing restaurants and branches
        $restaurants = DB::table('restaurants')->get();
        $defaults = [
            'housekeeping'  => 'Housekeeping',
            'front_desk'    => 'Front Desk',
            'maintenance'   => 'Maintenance',
            'laundry'       => 'Laundry',
            'utilities'     => 'Utilities',
            'security'      => 'Security',
            'marketing'     => 'Marketing',
            'administration'=> 'Administration',
            'f_and_b'       => 'F&B / Restaurant',
            'other'         => 'Other',
        ];

        foreach ($restaurants as $restaurant) {
            $branches = DB::table('branches')->where('restaurant_id', $restaurant->id)->get();
            
            if ($branches->isEmpty()) {
                foreach ($defaults as $slug => $name) {
                    DB::table('hotel_expense_departments')->insert([
                        'restaurant_id' => $restaurant->id,
                        'branch_id' => null,
                        'name' => $name,
                        'slug' => $slug,
                        'is_system' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                foreach ($branches as $branch) {
                    foreach ($defaults as $slug => $name) {
                        DB::table('hotel_expense_departments')->insert([
                            'restaurant_id' => $restaurant->id,
                            'branch_id' => $branch->id,
                            'name' => $name,
                            'slug' => $slug,
                            'is_system' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // Migrate existing records mapping department string values to the new department IDs
        $expenses = DB::table('hotel_expenses')->get();
        foreach ($expenses as $expense) {
            $deptId = DB::table('hotel_expense_departments')
                ->where('restaurant_id', $expense->restaurant_id)
                ->where('slug', $expense->department)
                ->value('id');
            
            if (!$deptId) {
                // Default to 'other' department
                $deptId = DB::table('hotel_expense_departments')
                    ->where('restaurant_id', $expense->restaurant_id)
                    ->where('slug', 'other')
                    ->value('id');
            }

            if ($deptId) {
                DB::table('hotel_expenses')
                    ->where('id', $expense->id)
                    ->update(['department_id' => $deptId]);
            }
        }

        // Drop the old department string column
        Schema::table('hotel_expenses', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }

    public function down(): void
    {
        // Re-create the old department column
        Schema::table('hotel_expenses', function (Blueprint $table) {
            $table->string('department')->nullable()->after('department_id');
        });

        // Restore slug values from relationship IDs
        $expenses = DB::table('hotel_expenses')->get();
        foreach ($expenses as $expense) {
            if ($expense->department_id) {
                $slug = DB::table('hotel_expense_departments')
                    ->where('id', $expense->department_id)
                    ->value('slug');
                if ($slug) {
                    DB::table('hotel_expenses')
                        ->where('id', $expense->id)
                        ->update(['department' => $slug]);
                }
            }
        }

        // Drop department_id
        Schema::table('hotel_expenses', function (Blueprint $table) {
            $table->dropColumn('department_id');
        });

        Schema::dropIfExists('hotel_expense_departments');
    }
};
