<?php
/**
 * Migration script to add 2FA support to users table
 * Run this once to update the database schema
 */

require_once 'config.php';

try {
    $db = getDb();
    
    // Check if columns already exist
    $result = $db->query("PRAGMA table_info(users)");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN, 1);
    
    $migrations = [];
    
    if (!in_array('two_factor_secret', $columns)) {
        $migrations[] = "ALTER TABLE users ADD COLUMN two_factor_secret TEXT DEFAULT NULL";
    }
    
    if (!in_array('two_factor_enabled', $columns)) {
        $migrations[] = "ALTER TABLE users ADD COLUMN two_factor_enabled INTEGER DEFAULT 0";
    }
    
    if (!in_array('two_factor_backup_codes', $columns)) {
        $migrations[] = "ALTER TABLE users ADD COLUMN two_factor_backup_codes TEXT DEFAULT NULL";
    }
    
    if (empty($migrations)) {
        echo "✅ Database is already up to date!\n";
        exit(0);
    }
    
    // Run migrations
    foreach ($migrations as $migration) {
        echo "Running: $migration\n";
        $db->exec($migration);
    }
    
    echo "\n✅ Successfully added 2FA support to database!\n";
    echo "Columns added:\n";
    echo "  - two_factor_secret (stores TOTP secret)\n";
    echo "  - two_factor_enabled (0=disabled, 1=enabled)\n";
    echo "  - two_factor_backup_codes (JSON array of backup codes)\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
