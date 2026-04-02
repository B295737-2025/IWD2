<?php
header('Content-Type: application/json');
ignore_user_abort(true);
set_time_limit(0);

$input_file = $_GET['file'] ?? '';
$input_file = trim($input_file);

$results_dir = realpath(__DIR__ . '/results');

function write_tree_job_and_exit($results_dir, $payload) {
    $job_name = 'tree_job_' . date('Ymd_His') . '_' . substr(uniqid('', true), -6) . '.json';
    $job_absolute = $results_dir . '/' . $job_name;
    $job_relative = 'results/' . $job_name;

    file_put_contents(
        $job_absolute,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    echo json_encode([
        'status' => 'complete',
        'redirect' => 'tree.php?job=' . urlencode($job_relative)
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
    write_tree_job_and_exit($results_dir, [
        'status' => 'error',
        'message' => 'No aligned FASTA file was provided.',
        'detail' => '',
        'input_file' => ''
    ]);
}

$relative_input = ltrim($input_file, '/');
$absolute_input = realpath(__DIR__ . '/' . $relative_input);

if ($absolute_input === false || strpos($absolute_input, $results_dir) !== 0) {
    write_tree_job_and_exit($results_dir, [
        'status' => 'error',
        'message' => 'Invalid input file path.',
        'detail' => '',
        'input_file' => $relative_input
    ]);
}

if (!file_exists($absolute_input)) {
    write_tree_job_and_exit($results_dir, [
        'status' => 'error',
        'message' => 'Aligned FASTA file does not exist.',
        'detail' => '',
        'input_file' => $relative_input
    ]);
}

$input_basename = pathinfo($absolute_input, PATHINFO_FILENAME);

$summary_basename = $input_basename . '_tree_summary.json';
$summary_absolute = $results_dir . '/' . $summary_basename;
$summary_relative = 'results/' . $summary_basename;

$tree_basename = $input_basename . '_nj_tree.nwk';
$tree_absolute = $results_dir . '/' . $tree_basename;
$tree_relative = 'results/' . $tree_basename;

$png_basename = $input_basename . '_nj_tree.png';
$png_absolute = $results_dir . '/' . $png_basename;
$png_relative = 'results/' . $png_basename;

$python = '/usr/bin/python3';
$script = __DIR__ . '/lib/tree_analysis.py';

$data = null;
$run_output = '';

if (!file_exists($summary_absolute)) {
    $command = $python . ' '
        . escapeshellarg($script) . ' '
        . escapeshellarg($absolute_input) . ' 2>&1';

    $run_output = shell_exec($command);

    $json_text = trim($run_output);
    $json_start = strpos($json_text, '{');
    if ($json_start !== false) {
        $json_text = substr($json_text, $json_start);
    }

    $run_result = json_decode($json_text, true);

    if (!is_array($run_result)) {
        write_tree_job_and_exit($results_dir, [
            'status' => 'error',
            'message' => 'Tree analysis did not return valid JSON.',
            'detail' => $run_output,
            'input_file' => $relative_input
        ]);
    }

    if (($run_result['status'] ?? '') !== 'ok') {
        $msg = $run_result['message'] ?? 'Unknown error';
        write_tree_job_and_exit($results_dir, [
            'status' => 'error',
            'message' => 'Tree analysis failed: ' . $msg,
            'detail' => $run_output,
            'input_file' => $relative_input
        ]);
    }

    file_put_contents(
        $summary_absolute,
        json_encode($run_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    $data = $run_result;
} else {
    $json_text = file_get_contents($summary_absolute);

    if ($json_text === false) {
        write_tree_job_and_exit($results_dir, [
            'status' => 'error',
            'message' => 'Could not read tree summary JSON file.',
            'detail' => '',
            'input_file' => $relative_input
        ]);
    }

    $data = json_decode($json_text, true);

    if (!is_array($data)) {
        write_tree_job_and_exit($results_dir, [
            'status' => 'error',
            'message' => 'Saved tree summary JSON is invalid.',
            'detail' => '',
            'input_file' => $relative_input
        ]);
    }
}

if (($data['status'] ?? '') !== 'ok') {
    write_tree_job_and_exit($results_dir, [
        'status' => 'error',
        'message' => 'Saved tree summary JSON does not contain a successful result.',
        'detail' => '',
        'input_file' => $relative_input
    ]);
}

write_tree_job_and_exit($results_dir, [
    'status' => 'ok',
    'message' => '',
    'detail' => '',
    'input_file' => $relative_input,
    'summary_file' => $summary_relative,
    'tree_file' => file_exists($tree_absolute) ? $tree_relative : '',
    'png_file' => file_exists($png_absolute) ? $png_relative : '',
    'sequence_count' => $data['sequence_count'] ?? 0,
    'min_distance' => $data['min_distance'] ?? '',
    'max_distance' => $data['max_distance'] ?? '',
    'average_distance' => $data['average_distance'] ?? '',
    'png_created' => $data['png_created'] ?? false,
    'png_error' => $data['png_error'] ?? ''
]);