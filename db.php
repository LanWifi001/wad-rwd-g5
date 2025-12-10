<?php
// Simple SQLite connection using SQLite3 class and table auto-create
function get_db(): SQLite3 {
    static $db = null;
    if ($db !== null) {
        return $db;
    }
    $dbPath = __DIR__ . '/db.db';
    $db = new SQLite3($dbPath);
    $db->enableExceptions(false);

    // Create users table if it does not exist
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE
    );');

    // Create topics table if it does not exist
    $db->exec('CREATE TABLE IF NOT EXISTS topics (
        topic_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        topic_name TEXT NOT NULL,
        term TEXT NOT NULL,
        definition TEXT,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
            ON DELETE CASCADE ON UPDATE CASCADE
    );');

    // Create user_items table if it does not exist
    $db->exec('CREATE TABLE IF NOT EXISTS user_items (
        item_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        item_name TEXT NOT NULL,
        item_description TEXT,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
            ON DELETE CASCADE ON UPDATE CASCADE
    );');

    return $db;
}