<?php
$password = 'admin123';
$hash = '$2y$10$QbHsSCmOvc9bhHKoKYQK7OsudNB9PNr4siw/lDEVywn2x1RrSNaWu';

echo "Testing password verification:\n";
echo "Password: $password\n";
echo "Hash: $hash\n";
echo "Verification result: " . (password_verify($password, $hash) ? 'TRUE' : 'FALSE') . "\n";

// Test generating a new hash
$newHash = password_hash($password, PASSWORD_DEFAULT);
echo "New hash for 'admin123': $newHash\n";
echo "Verification with new hash: " . (password_verify($password, $newHash) ? 'TRUE' : 'FALSE') . "\n";
?> 