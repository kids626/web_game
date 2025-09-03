<?php
header('Content-Type: application/json; charset=UTF-8');

// 允許同路徑的簡單呼叫
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	echo json_encode(['ok' => true]);
	exit;
}

try{
	$input = file_get_contents('php://input');
	$payload = json_decode($input, true) ?: [];
	$page = isset($payload['page']) ? preg_replace('/[^a-zA-Z0-9_\-]/','', (string)$payload['page']) : 'default';
	$mode = isset($payload['mode']) ? (string)$payload['mode'] : 'get';

	$baseDir = dirname(__DIR__);
	$projectDir = $baseDir . DIRECTORY_SEPARATOR . 'project';
	if (!is_dir($projectDir)) {
		@mkdir($projectDir, 0777, true);
	}
	$counterFile = $projectDir . DIRECTORY_SEPARATOR . $page . '_views.txt';
	if (!file_exists($counterFile)) {
		@file_put_contents($counterFile, '0', LOCK_EX);
	}

	$count = 0;
	$raw = @file_get_contents($counterFile);
	$current = is_numeric(trim((string)$raw)) ? (int)trim((string)$raw) : 0;

	if ($mode === 'inc') {
		$current++;
		@file_put_contents($counterFile, (string)$current, LOCK_EX);
	}
	$count = $current;

	echo json_encode(['ok'=>true,'count'=>$count]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['ok'=>false,'error'=>'server_error']);
}

