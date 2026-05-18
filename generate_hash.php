<?php
$password = "pass123";
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Hash for password 'pass123'</h2>";
echo "<pre>" . htmlspecialchars($hash) . "</pre>";
echo "<p>Copy the hash above and use it in the UPDATE query.</p>";
?>