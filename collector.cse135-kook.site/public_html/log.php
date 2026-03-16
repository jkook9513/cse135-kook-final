<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=utf-8");

$origin = $_SERVER["HTTP_ORIGIN"] ?? "";
$allowed = [
  "https://test.cse135-kook.site",
  "https://www.test.cse135-kook.site",
  "https://cse135-kook.site",
  "https://www.cse135-kook.site",
];

if (in_array($origin, $allowed, true)) {
  header("Access-Control-Allow-Origin: $origin");
  header("Vary: Origin");
  header("Access-Control-Allow-Methods: POST, OPTIONS");
  header("Access-Control-Allow-Headers: Content-Type");
  header("Access-Control-Allow-Credentials: true");
}

if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") {
  http_response_code(204);
  exit;
}

if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
  http_response_code(405);
  echo json_encode(["ok" => false, "error" => "Method not allowed"]);
  exit;
}

$raw = file_get_contents("php://input");
if ($raw === false || trim($raw) === "") {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Empty body"]);
  exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Invalid JSON"]);
  exit;
}
error_log("collector/log.php hit: type=" . ($data["type"] ?? "NULL") . " session=" . ($data["session"] ?? "NULL"));

$eventType = $data["type"] ?? null;
$pageUrl   = $data["url"] ?? null;
$sessionId = $data["session"] ?? null;

$siteOrigin = null;
if (is_string($pageUrl) && $pageUrl !== "") {
  $host = parse_url($pageUrl, PHP_URL_HOST);
  if (is_string($host) && $host !== "") $siteOrigin = "https://" . $host;
}

try {
  $dsn = "mysql:host=127.0.0.1;dbname=cse135_analytics;charset=utf8mb4";

  $pdo = new PDO($dsn, "cse135", "cse135-PW", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  ]);

  $stmt = $pdo->prepare(
    "INSERT INTO collector_events (site_origin, page_url, session_id, event_type, payload)
     VALUES (:site_origin, :page_url, :session_id, :event_type, :payload)"
  );

  $stmt->execute([
    ":site_origin" => $siteOrigin,
    ":page_url"    => $pageUrl,
    ":session_id"  => $sessionId,
    ":event_type"  => $eventType,
    ":payload"     => $raw,
  ]);

  echo json_encode(["ok" => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Server error"]);
}
