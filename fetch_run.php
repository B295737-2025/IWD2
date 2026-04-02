<?php
header('Content-Type: application/json');
ignore_user_abort(true);
set_time_limit(0);

require_once __DIR__ . '/lib/db_connect.php';
require_once __DIR__ . '/lib/cleanup_results.php';

cleanup_old_results(__DIR__ . '/results', 60);

$config = require '/home/s2809725/ica_private/config.php';

putenv('ENTREZ_EMAIL=' . ($config['entrez_email'] ?? ''));
putenv('ENTREZ_API_KEY=' . ($config['entrez_api_key'] ?? ''));

$results_dir = realpath(__DIR__ . '/results');
if ($results_dir === false) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Results directory not found.'
    ]);
    exit;
}

function write_fetch_job_and_exit($results_dir, $payload) {
    $job_name = 'fetch_job_' . date('Ymd_His') . '_' . substr(uniqid('', true), -6) . '.json';
    $job_absolute = $results_dir . '/' . $job_name;
    $job_relative = 'results/' . $job_name;

    file_put_contents(
        $job_absolute,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    echo json_encode([
        'status' => 'complete',
        'redirect' => 'fetch.php?job=' . urlencode($job_relative)
    ]);
    exit;
}

$user_name = trim($_POST['user_name'] ?? 'guest');
$family = trim($_POST['family'] ?? '');
$taxon  = trim($_POST['taxon'] ?? '');
$max_sequences = trim($_POST['max_sequences'] ?? '50');

if ($user_name === '') {
    $user_name = 'guest';
}

if ($max_sequences === '' || !ctype_digit($max_sequences)) {
    $max_sequences = '50';
}

$max_sequences_int = (int)$max_sequences;
if ($max_sequences_int < 1) {
    $max_sequences_int = 1;
}
if ($max_sequences_int > 1000) {
    $max_sequences_int = 1000;
}

if ($family === '' || $taxon === '') {
    write_fetch_job_and_exit($results_dir, [
        'status' => 'error',
        'message' => 'Missing protein family or taxonomic group.',
        'user_name' => $user_name,
        'family' => $family,
        'taxon' => $taxon,
        'max_sequences_requested' => $max_sequences_int,
        'raw_output' => '',
        'history_saved' => false,
        'history_error' => ''
    ]);
}

$python = '/usr/bin/python3';
$script = __DIR__ . '/lib/fetch_sequences.py';

$command = $python . ' '
         . escapeshellarg($script) . ' '
         . escapeshellarg($family) . ' '
         . escapeshellarg($taxon) . ' '
         . escapeshellarg((string)$max_sequences_int) . ' 2>&1';

$raw_output = shell_exec($command);
$result = json_decode($raw_output, true);

if (!is_array($result)) {
    write_fetch_job_and_exit($results_dir, [
        'status' => 'error',
        'message' => 'Python script did not return valid JSON.',
        'user_name' => $user_name,
        'family' => $family,
        'taxon' => $taxon,
        'max_sequences_requested' => $max_sequences_int,
        'raw_output' => $raw_output,
        'history_saved' => false,
        'history_error' => ''
    ]);
}

$history_saved = false;
$history_error = '';

if (($result['status'] ?? '') === 'ok') {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO history (user, family, taxon, result_link) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $user_name,
            $family,
            $taxon,
            $result['result_file']
        ]);
        $history_saved = true;
    } catch (PDOException $e) {
        $history_error = $e->getMessage();
    }
}

$payload = [
    'status' => $result['status'] ?? 'error',
    'message' => $result['message'] ?? '',
    'term' => $result['term'] ?? '',
    'sequence_count' => $result['sequence_count'] ?? 0,
    'result_file' => $result['result_file'] ?? '',
    'preview' => $result['preview'] ?? [],
    'user_name' => $user_name,
    'family' => $family,
    'taxon' => $taxon,
    'max_sequences_requested' => $result['max_sequences_requested'] ?? $max_sequences_int,
    'raw_output' => $raw_output,
    'history_saved' => $history_saved,
    'history_error' => $history_error
];

write_fetch_job_and_exit($results_dir, $payload);