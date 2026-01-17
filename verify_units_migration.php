<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Check units table structure
echo "=== Units Table Structure ===\n";
$columns = Schema::getColumnListing('units');
echo "Columns: " . implode(', ', $columns) . "\n\n";

// Check for branch_id
if (in_array('branch_id', $columns)) {
    echo "❌ ISSUE: branch_id column still exists!\n\n";
} else {
    echo "✅ SUCCESS: branch_id column removed\n\n";
}

// Check unique constraint
echo "=== Unique Constraints ===\n";
$indexes = DB::select("SHOW INDEXES FROM units WHERE Key_name = 'units_name_symbol_unique'");
if (!empty($indexes)) {
    echo "✅ SUCCESS: Unique constraint on (name, symbol) exists\n\n";
} else {
    echo "❌ ISSUE: Unique constraint not found!\n\n";
}

// Check for duplicates
echo "=== Duplicate Check ===\n";
$duplicates = DB::select("
    SELECT name, symbol, COUNT(*) as count 
    FROM units 
    GROUP BY name, symbol 
    HAVING COUNT(*) > 1
");

if (empty($duplicates)) {
    echo "✅ SUCCESS: No duplicate units found\n\n";
} else {
    echo "❌ ISSUE: Found " . count($duplicates) . " duplicate unit sets:\n";
    foreach ($duplicates as $dup) {
        echo "  - {$dup->name} ({$dup->symbol}): {$dup->count} entries\n";
    }
    echo "\n";
}

// List all units
echo "=== All Units ===\n";
$units = DB::table('units')->select('id', 'name', 'symbol')->orderBy('id')->get();
echo "Total units: " . $units->count() . "\n";
foreach ($units as $unit) {
    echo "  ID: {$unit->id} | {$unit->name} ({$unit->symbol})\n";
}
