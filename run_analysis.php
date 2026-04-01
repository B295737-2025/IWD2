<?php
header('Content-Type: application/json');
ignore_user_abort(true);
set_time_limit(0);

$input_file = $_GET['file'] ?? '';
$input_file = trim($input_file);

$results_dir = realpath(__DIR__ . '/results');

function json_error($msg, $extra = []) {
    echo json_encode(array_merge([
        'status' => 'error',
        'message' => $msg
    ], $extra));
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

if (!file_exists($absolute_input)) {
    json_error('Input FASTA file does not exist.');
}

$input_basename = pathinfo($absolute_input, PATHINFO_FILENAME);

$aligned_absolute = $results_dir . '/aligned_' . $input_basename . '.fasta';
$matrix_absolute  = $results_dir . '/aligned_' . $input_basename . '_identity_matrix.tsv';
$summary_absolute = $results_dir . '/aligned_' . $input_basename . '_conservation_summary.json';
$lock_absolute    = $results_dir . '/aligned_' . $input_basename . '.lock';

$clustalo = '/usr/bin/clustalo';
$python = '/usr/bin/python3';
$conservation_script = __DIR__ . '/lib/conservation_analysis.py';

if (file_exists($aligned_absolute) && file_exists($matrix_absolute) && file_exists($summary_absolute)) {
    echo json_encode([
        'status' => 'complete',
        'message' => 'Cached results already exist.'
    ]);
    exit;
}

if (file_exists($lock_absolute)) {
    echo json_encode([
        'status' => 'running',
        'message' => 'Analysis is already running.'
    ]);
    exit;
}

file_put_contents($lock_absolute, date('c'));

try {
    if (!file_exists($aligned_absolute)) {
        $align_command = $clustalo
            . ' --threads=4'
            . ' -i ' . escapeshellarg($absolute_input)
            . ' -o ' . escapeshellarg($aligned_absolute)
            . ' --force --outfmt=fasta 2>&1';

        $align_output = shell_exec($align_command);

        if (!file_exists($aligned_absolute)) {
            @unlink($lock_absolute);
            json_error('Alignment failed.', ['detail' => $align_output]);
        }
    }

    $cons_command = $python . ' '
        . escapeshellarg($conservation_script) . ' '
        . escapeshellarg($aligned_absolute) . ' 2>&1';

    $cons_output = shell_exec($cons_command);
    $cons_result = json_decode($cons_output, true);

    if (!is_array($cons_result)) {
        @unlink($lock_absolute);
        json_error('Conservation script did not return valid JSON.', ['detail' => $cons_output]);
    }

    if (($cons_result['status'] ?? '') !== 'ok') {
        @unlink($lock_absolute);
        json_error('Conservation analysis failed.', ['detail' => $cons_output]);
    }

    @unlink($lock_absolute);

    echo json_encode([
        'status' => 'complete',
        'message' => 'Analysis finished successfully.'
    ]);
} catch (Throwable $e) {
    @unlink($lock_absolute);
    json_error($e->getMessage());
}