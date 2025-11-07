<?php
// Migration script to add timezone column to sites table
require_once __DIR__ . '/config.php';

try {
    $db = getDb();
    
    // Check if timezone column already exists
    $columns = $db->query("PRAGMA table_info(sites)")->fetchAll();
    $hasTimezone = false;
    
    foreach ($columns as $column) {
        if ($column['name'] === 'timezone') {
            $hasTimezone = true;
            break;
        }
    }
    
    if (!$hasTimezone) {
        // Add timezone column with default UTC
        $db->exec("ALTER TABLE sites ADD COLUMN timezone TEXT DEFAULT 'UTC'");
        echo "✅ Timezone column added successfully!\n";
    } else {
        echo "ℹ️  Timezone column already exists.\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
