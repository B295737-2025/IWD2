<?php
header('Content-Type: application/json');
ignore_user_abort(true);
set_time_limit(0);

$input_file = $_GET['file'] ?? '';
$input_file = trim($input_file);

$results_dir = realpath(__DIR__ . '/results');

function write_motif_job_and_exit($results_dir, $payload) {
    $job_name = 'motif_job_' . date('Ymd_His') . '_' . substr(uniqid('', true), -6) . '.json';
    $job_absolute = $results_dir . '/' . $job_name;
    $job_relative = 'results/' . $job_name;

    file_put_contents(
        $job_absolute,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    echo json_encode([
        'status' => 'complete',
        'redirect' => 'motif.php?job=' . urlencode($job_relative)
    ]);
    exit;
}

if ($results_dir === false) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Results directory not found.'
    ]);
    exit;
}

if ($input_file === '') {
    write_motif_job_and_exit($results_dir, [
        'status' => 'error',
        'message' => 'No input FASTA file was provided.',
        'detail' => '',
        'input_file' => ''
    ]);
}

$relative_input = ltrim($input_file, '/');
$absolute_input = realpath(__DIR__ . '/' . $relative_input);

if ($absolute_input === false || strpos($absolute_input, $results_dir) !== 0) {
    write_motif_job_and_exit($results_dir, [
        'status' => 'error',
        'message' => 'Invalid input file path.',
        'detail' => '',
        'input_file' => $relative_input
    ]);
}

if (!file_exists($absolute_input)) {
    write_motif_job_and_exit($results_dir, [
        'status' => 'error',
        'message' => 'Input FASTA file does not exist.',
        'detail' => '',
        'input_file' => $relative_input
    ]);
}

$input_basename = pathinfo($absolute_input, PATHINFO_FILENAME);

$summary_basename = $input_basename . '_motif_summary.json';
$summary_absolute = $results_dir . '/' . $summary_basename;
$summary_relative = 'results/' . $summary_basename;

$raw_basename = $input_basename . '_motif_raw.txt';
$raw_absolute = $results_dir . '/' . $raw_basename;
$raw_relative = 'results/' . $raw_basename;

$python = '/usr/bin/python3';
$script = __DIR__ . '/lib/motif_scan.py';

$data = null;
$scan_output = '';

if (!file_exists($summary_absolute)) {
    $command = $python . ' '
        . escapeshellarg($script) . ' '
        . escapeshellarg($absolute_input) . ' 2>&1';

    $scan_output = shell_exec($command);
    $scan_result = json_decode($scan_output, true);

    if (!is_array($scan_result)) {
        write_motif_job_and_exit($results_dir, [
            'status' => 'error',
            'message' => 'Motif scan did not return valid JSON.',
            'detail' => $scan_output,
            'input_file' => $relative_input
        ]);
    }

    if (($scan_result['status'] ?? '') !== 'ok') {
        $msg = $scan_result['message'] ?? 'Unknown error';
        write_motif_job_and_exit($results_dir, [
            'status' => 'error',
            'message' => 'Motif scan failed: ' . $msg,
            'detail' => $scan_output,
            'input_file' => $relative_input
        ]);
    }

    file_put_contents(
        $summary_absolute,
        json_encode($scan_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    if (!empty($scan_result['combined_raw_report'])) {
        file_put_contents($raw_absolute, $scan_result['combined_raw_report']);
    }

    $data = $scan_result;
} else {
    $json_text = file_get_contents($summary_absolute);

    if ($json_text === false) {
        write_motif_job_and_exit($results_dir, [
            'status' => 'error',
            'message' => 'Could not read motif summary JSON file.',
            'detail' => '',
            'input_file' => $relative_input
        ]);
    }

    $data = json_decode($json_text, true);

    if (!is_array($data)) {
        write_motif_job_and_exit($results_dir, [
            'status' => 'error',
            'message' => 'Saved motif summary JSON is invalid.',
            'detail' => '',
            'input_file' => $relative_input
        ]);
    }

    if (!file_exists($raw_absolute) && !empty($data['combined_raw_report'])) {
        file_put_contents($raw_absolute, $data['combined_raw_report']);
    }
}

if (($data['status'] ?? '') !== 'ok') {
    write_motif_job_and_exit($results_dir, [
        'status' => 'error',
        'message' => 'Saved motif summary JSON does not contain a successful result.',
        'detail' => '',
        'input_file' => $relative_input
    ]);
}

write_motif_job_and_exit($results_dir, [
    'status' => 'ok',
    'message' => '',
    'detail' => '',
    'input_file' => $relative_input,
    'summary_file' => $summary_relative,
    'raw_file' => file_exists($raw_absolute) ? $raw_relative : '',
    'sequence_count' => $data['sequence_count'] ?? 0,
    'sequences_with_hits' => $data['sequences_with_hits'] ?? 0,
    'total_hits' => $data['total_hits'] ?? 0,
    'results' => $data['results'] ?? [],
    'combined_raw_report' => $data['combined_raw_report'] ?? ''
]);