<?php
header('Content-Type: application/json');

$input_file = $_GET['file'] ?? '';
$input_file = trim($input_file);

$results_dir = realpath(__DIR__ . '/results');

function json_error($msg) {
    echo json_encode([
        'status' => 'error',
        'message' => $msg
    ]);
    exit;
}

if ($input_file === '') {
    json_error('No input FASTA file was provided.');
}

$relative_input = ltrim($input_file, '/');
$absolute_input = realpath(__DIR__ . '/' . $relative_input);

if ($absolute_input === false || strpos($absolute_input, $results_dir) !== 0) {
    json_error('Invalid input file path.');
}

$input_basename = pathinfo($absolute_input, PATHINFO_FILENAME);

$aligned_absolute = $results_dir . '/aligned_' . $input_basename . '.fasta';
$matrix_absolute  = $results_dir . '/aligned_' . $input_basename . '_identity_matrix.tsv';
$summary_absolute = $results_dir . '/aligned_' . $input_basename . '_conservation_summary.json';
$lock_absolute    = $results_dir . '/aligned_' . $input_basename . '.lock';

if (file_exists($aligned_absolute) && file_exists($matrix_absolute) && file_exists($summary_absolute)) {
    echo json_encode([
        'status' => 'complete'
    ]);
    exit;
}

if (file_exists($lock_absolute)) {
    echo json_encode([
        'status' => 'running'
    ]);
    exit;
}

echo json_encode([
    'status' => 'pending'
]);