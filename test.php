<?php
// Показати всі PHP помилки
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Тестовий файл</h1>";
echo "<pre>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "SERVER_NAME: " . $_SERVER['SERVER_NAME'] . "\n";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\n";
echo "</pre>";

// Перевірка .htaccess
echo "<h2>Перевірка .htaccess</h2>";
echo "<p>Якщо ви бачите це повідомлення, файл .htaccess працює правильно.</p>"; 