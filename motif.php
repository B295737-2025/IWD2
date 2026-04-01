<?php
$input_file = $_GET['file'] ?? '';
$input_file = trim($input_file);

$results_dir = realpath(__DIR__ . '/results');

function error_page($message, $detail = '') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Motif Scan</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 1100px;
                margin: 40px auto;
                line-height: 1.6;
                padding: 0 20px;
            }
            .box {
                border: 1px solid #ccc;
                padding: 20px;
                border-radius: 8px;
                background: #f9f9f9;
                margin-bottom: 20px;
            }
            .err {
                color: #a10000;
            }
            pre {
                white-space: pre-wrap;
                word-break: break-word;
                background: #f3f3f3;
                padding: 12px;
                border-radius: 6px;
                overflow-x: auto;
            }
        </style>
    </head>
    <body>
        <h1>Motif / Domain Scan</h1>
        <div class="box">
            <p class="err"><strong>Error:</strong> <?php echo htmlspecialchars($message); ?></p>
            <?php if ($detail !== ''): ?>
                <pre><?php echo htmlspecialchars($detail); ?></pre>
            <?php endif; ?>
        </div>
        <p><a href="index.php">Back to home</a></p>
    </body>
    </html>
    <?php
    exit;
}

if ($input_file === '') {
    error_page('No input FASTA file was provided.');
}

$relative_input = ltrim($input_file, '/');
$absolute_input = realpath(__DIR__ . '/' . $relative_input);

if ($absolute_input === false || strpos($absolute_input, $results_dir) !== 0) {
    error_page('Invalid input file path.');
}

if (!file_exists($absolute_input)) {
    error_page('Input FASTA file does not exist.');
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
        error_page('Motif scan did not return valid JSON.', $scan_output);
    }

    if (($scan_result['status'] ?? '') !== 'ok') {
        $msg = $scan_result['message'] ?? 'Unknown error';
        error_page('Motif scan failed: ' . $msg, $scan_output);
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
        error_page('Could not read motif summary JSON file.');
    }

    $data = json_decode($json_text, true);
    if (!is_array($data)) {
        error_page('Saved motif summary JSON is invalid.');
    }

    if (!file_exists($raw_absolute) && !empty($data['combined_raw_report'])) {
        file_put_contents($raw_absolute, $data['combined_raw_report']);
    }
}

if (($data['status'] ?? '') !== 'ok') {
    error_page('Saved motif summary JSON does not contain a successful result.');
}

$sequence_count = $data['sequence_count'] ?? 0;
$sequences_with_hits = $data['sequences_with_hits'] ?? 0;
$total_hits = $data['total_hits'] ?? 0;
$combined_raw_report = $data['combined_raw_report'] ?? '';

$sequence_rows = [];
$hit_rows = [];

foreach (($data['results'] ?? []) as $seq_result) {
    $seq_id = $seq_result['sequence_id'] ?? '';
    $seq_length = $seq_result['length'] ?? '';
    $hit_count = $seq_result['hit_count'] ?? 0;

    if ($hit_count > 0) {
        $sequence_rows[] = [
            'sequence_id' => $seq_id,
            'length' => $seq_length,
            'hit_count' => $hit_count
        ];
    }

    foreach (($seq_result['hits'] ?? []) as $hit) {
        $hit_rows[] = [
            'sequence_id' => $seq_id,
            'motif' => $hit['motif'] ?? '',
            'start' => $hit['start'] ?? '',
            'end' => $hit['end'] ?? '',
            'length' => $hit['length'] ?? ''
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motif Scan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1100px;
            margin: 40px auto;
            line-height: 1.6;
            padding: 0 20px;
        }
        .box {
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 8px;
            background: #f9f9f9;
            margin-bottom: 20px;
        }
        h1, h2 {
            color: #1f4e79;
        }
        a {
            color: #1f4e79;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .note {
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #eaf2f8;
        }
        pre {
            white-space: pre-wrap;
            word-break: break-word;
            background: #f3f3f3;
            padding: 12px;
            border-radius: 6px;
            overflow-x: auto;
        }
        details {
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1>Motif / Domain Scan</h1>

    <div class="box">
        <h2>Input and output files</h2>
        <p><strong>Input FASTA:</strong>
            <a href="<?php echo htmlspecialchars($relative_input); ?>">
                <?php echo htmlspecialchars($relative_input); ?>
            </a>
        </p>
        <p><strong>Motif summary JSON:</strong>
            <a href="<?php echo htmlspecialchars($summary_relative); ?>">
                <?php echo htmlspecialchars($summary_relative); ?>
            </a>
        </p>
        <?php if (file_exists($raw_absolute)): ?>
            <p><strong>Combined raw motif report:</strong>
                <a href="<?php echo htmlspecialchars($raw_relative); ?>">
                    <?php echo htmlspecialchars($raw_relative); ?>
                </a>
            </p>
        <?php endif; ?>
        <p class="note">
            Result files are retained on the server for 60 days.
            You can revisit recent analyses from the history page during this period.
        </p>
    </div>

    <div class="box">
        <h2>Scan summary</h2>
        <p><strong>Total sequences scanned:</strong> <?php echo htmlspecialchars((string)$sequence_count); ?></p>
        <p><strong>Sequences with motif hits:</strong> <?php echo htmlspecialchars((string)$sequences_with_hits); ?></p>
        <p><strong>Total motif hits:</strong> <?php echo htmlspecialchars((string)$total_hits); ?></p>

        <?php if ($total_hits == 0): ?>
            <p>No PROSITE motifs were detected in this sequence set.</p>
        <?php else: ?>
            <p>
                This sequence set contains PROSITE motif hits in a subset of sequences.
                The table below lists the motif type and its position in each sequence.
            </p>
        <?php endif; ?>
    </div>

    <div class="box">
        <h2>Sequences with motif hits</h2>
        <?php if (empty($sequence_rows)): ?>
            <p>No sequences with motif hits were found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Sequence ID</th>
                        <th>Sequence length (aa)</th>
                        <th>Hit count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sequence_rows as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['sequence_id']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['length']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['hit_count']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="box">
        <h2>Motif hit table</h2>
        <?php if (empty($hit_rows)): ?>
            <p>No motif hit positions are available.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Sequence ID</th>
                        <th>Motif</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Motif length</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hit_rows as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['sequence_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['motif']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['start']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['end']); ?></td>
                            <td><?php echo htmlspecialchars((string)$row['length']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="box">
        <h2>Raw report</h2>
        <?php if ($combined_raw_report === ''): ?>
            <p>No raw report text is available.</p>
        <?php else: ?>
            <details>
                <summary>Show raw combined report</summary>
                <pre><?php echo htmlspecialchars($combined_raw_report); ?></pre>
            </details>
        <?php endif; ?>
    </div>

    <p><a href="index.php">Back to home</a></p>
    <p><a href="history.php">View history</a></p>
</body>
</html>