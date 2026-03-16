<?php
declare(strict_types=1);

function db(): PDO {
  $dsn = "mysql:host=127.0.0.1;dbname=cse135_analytics;charset=utf8mb4";
  return new PDO($dsn, "cse135", "cse135-PW", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
}
