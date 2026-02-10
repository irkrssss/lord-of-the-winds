<?php
// db.php
$host = 'localhost';
$db   = 'u123456_windlord'; // Твое имя базы
$user = 'u123456_user';     // Твой логин
$pass = 'password';         // Твой пароль
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $opt);
} catch (\PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>
