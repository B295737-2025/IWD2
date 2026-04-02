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
    
        $stats_script = __DIR__ . '/lib/motif_stats.py';
        $stats_command = $python . ' '
            . escapeshellarg($stats_script) . ' '
            . escapeshellarg($summary_absolute) . ' 2>&1';
    
        $stats_output = shell_exec($stats_command);
        $stats_result = json_decode($stats_output, true);
    
        if (!is_array($stats_result) || ($stats_result['status'] ?? '') !== 'ok') {
            $scan_result['motif_stats_file'] = '';
            $scan_result['motif_stats_plot_file'] = '';
            $scan_result['motif_stats_plot_created'] = false;
            $scan_result['motif_stats_plot_error'] = is_array($stats_result)
                ? ($stats_result['message'] ?? 'Unknown motif stats error')
                : $stats_output;
        } else {
            $stats_relative = 'results/' . basename($summary_absolute, '_motif_summary.json') . '_motif_stats.json';
            $plot_relative = 'results/' . basename($summary_absolute, '_motif_summary.json') . '_motif_stats.png';
    
            $scan_result['motif_stats_file'] = file_exists($results_dir . '/' . basename($summary_absolute, '_motif_summary.json') . '_motif_stats.json')
                ? $stats_relative
                : '';
            $scan_result['motif_stats_plot_file'] = file_exists($results_dir . '/' . basename($summary_absolute, '_motif_summary.json') . '_motif_stats.png')
                ? $plot_relative
                : '';
            $scan_result['motif_stats_plot_created'] = $stats_result['plot_created'] ?? false;
            $scan_result['motif_stats_plot_error'] = $stats_result['plot_error'] ?? '';
        }
    
        file_put_contents(
            $summary_absolute,
            json_encode($scan_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    
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
        $stats_absolute = $results_dir . '/' . $input_basename . '_motif_stats.json';
    $stats_plot_absolute = $results_dir . '/' . $input_basename . '_motif_stats.png';

    if (empty($data['motif_stats_file']) && file_exists($stats_absolute)) {
        $data['motif_stats_file'] = 'results/' . basename($stats_absolute);
    }

    if (empty($data['motif_stats_plot_file']) && file_exists($stats_plot_absolute)) {
        $data['motif_stats_plot_file'] = 'results/' . basename($stats_plot_absolute);
    }

    if (!isset($data['motif_stats_plot_created'])) {
        $data['motif_stats_plot_created'] = file_exists($stats_plot_absolute);
    }

    if (!isset($data['motif_stats_plot_error'])) {
        $data['motif_stats_plot_error'] = '';
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
    'combined_raw_report' => $data['combined_raw_report'] ?? '',
    'motif_stats_file' => $data['motif_stats_file'] ?? '',
    'motif_stats_plot_file' => $data['motif_stats_plot_file'] ?? '',
    'motif_stats_plot_created' => $data['motif_stats_plot_created'] ?? false,
    'motif_stats_plot_error' => $data['motif_stats_plot_error'] ?? ''
]);