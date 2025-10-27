<?php
// File: includes/init.php - Include this at the top of every page
require_once 'autoload.php';

// Initialize session and database

$database = new Database();
$db = $database->connect();

