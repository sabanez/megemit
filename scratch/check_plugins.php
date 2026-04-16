<?php
require_once dirname(__DIR__) . '/wp-load.php';

$active_plugins = get_option('active_plugins');
echo "Current count: " . count($active_plugins) . "\n";

$unique_plugins = array_unique($active_plugins);
echo "Unique count: " . count($unique_plugins) . "\n";

if (count($active_plugins) !== count($unique_plugins)) {
    echo "Duplicates found!\n";
    foreach ($active_plugins as $index => $plugin) {
        if (substr_count(implode('|', $active_plugins), $plugin) > 1) {
             echo "Index $index: $plugin (duplicate)\n";
        }
    }
    
    if (isset($argv[1]) && $argv[1] == '--fix') {
        echo "Fixing...\n";
        update_option('active_plugins', array_values($unique_plugins));
        echo "Done!\n";
    } else {
        echo "Run with --fix to actually update.\n";
    }
} else {
    echo "No duplicates found.\n";
}
