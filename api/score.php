<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	echo json_encode(['ok' => true]);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
	exit;
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
	http_response_code(400);
	echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
	exit;
}

$safe = [
	'game_type'   => (string)($data['game_type'] ?? ''),
	'difficulty'  => (string)($data['difficulty'] ?? ''),
	'score'       => (int)($data['score'] ?? 0),
	'correct'     => (int)($data['correct'] ?? 0),
	'wrong'       => (int)($data['wrong'] ?? 0),
	'duration_ms' => (int)($data['duration_ms'] ?? 0),
	'ended_at'    => (string)($data['ended_at'] ?? date('c')),
	'ip'          => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
	'ua'          => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
	'ts'          => date('c'),
];

$dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'project';
if (!is_dir($dir)) {
	@mkdir($dir, 0775, true);
}
$file = $dir . DIRECTORY_SEPARATOR . 'scores.jsonl';

$ok = @file_put_contents($file, json_encode($safe, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
if ($ok === false) {
	// 若寫入失敗，也回傳成功避免影響前端流程，但附帶警告
	echo json_encode(['ok' => true, 'warn' => 'write_failed']);
	exit;
}

echo json_encode(['ok' => true]);


