<?php
$page_title = 'Motif Scan';
$active_page = '';

$job_file = $_GET['job'] ?? '';
$job_file = trim($job_file);

$input_file = $_GET['file'] ?? '';
$input_file = trim($input_file);

$results_dir = realpath(__DIR__ . '/results');

function error_page($message, $detail = '') {
    global $page_title, $active_page;
    require_once __DIR__ . '/lib/site_header.php';
    ?>
    <div class="hero">
        <h1>Motif / Domain Scan</h1>
        <p>An error occurred while loading the motif analysis result.</p>
    </div>

    <div class="box">
        <p class="err"><strong>Error:</strong> <?php echo htmlspecialchars($message); ?></p>
        <?php if ($detail !== ''): ?>
            <pre><?php echo htmlspecialchars($detail); ?></pre>
        <?php endif; ?>
    </div>
    <?php
    require_once __DIR__ . '/lib/site_footer.php';
    exit;
}

if ($results_dir === false) {
    error_page('Results directory not found.');
}

if ($job_file === '' && $input_file === '') {
    error_page('No motif input or job file was provided.');
}

$data = null;

/*
 * Mode 1: display from a completed job file
 */
if ($job_file !== '') {
    $relative_job = ltrim($job_file, '/');
    $absolute_job = realpath(__DIR__ . '/' . $relative_job);

    if ($absolute_job === false || strpos($absolute_job, $results_dir) !== 0) {
        error_page('Invalid motif result file path.');
    }

    if (!file_exists($absolute_job)) {
        error_page('Motif result file does not exist.');
    }

    $json_text = file_get_contents($absolute_job);
    if ($json_text === false) {
        error_page('Could not read motif result file.');
    }

    $data = json_decode($json_text, true);
    if (!is_array($data)) {
        error_page('Motif result JSON is invalid.');
    }

    if (($data['status'] ?? '') !== 'ok') {
        error_page($data['message'] ?? 'Unknown error.', $data['detail'] ?? '');
    }
}

/*
 * Mode 2: direct access via ?file=...
 * If cached motif outputs already exist, display them directly.
 * Only redirect to the waiting page if motif outputs are not present yet.
 */
if ($data === null && $input_file !== '') {
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

    if (!file_exists($summary_absolute)) {
        header('Location: motif_status.php?file=' . urlencode($relative_input));
        exit;
    }

    $json_text = file_get_contents($summary_absolute);
    if ($json_text === false) {
        error_page('Could not read motif summary JSON file.');
    }

    $scan_data = json_decode($json_text, true);
    if (!is_array($scan_data)) {
        error_page('Saved motif summary JSON is invalid.');
    }

    if (($scan_data['status'] ?? '') !== 'ok') {
        $msg = $scan_data['message'] ?? 'Unknown error';
        error_page('Saved motif summary JSON does not contain a successful result: ' . $msg);
    }

    if (!file_exists($raw_absolute) && !empty($scan_data['combined_raw_report'])) {
        file_put_contents($raw_absolute, $scan_data['combined_raw_report']);
    }

    $data = [
        'status' => 'ok',
        'message' => '',
        'detail' => '',
        'input_file' => $relative_input,
        'summary_file' => $summary_relative,
        'raw_file' => file_exists($raw_absolute) ? $raw_relative : '',
        'sequence_count' => $scan_data['sequence_count'] ?? 0,
        'sequences_with_hits' => $scan_data['sequences_with_hits'] ?? 0,
        'total_hits' => $scan_data['total_hits'] ?? 0,
        'results' => $scan_data['results'] ?? [],
        'combined_raw_report' => $scan_data['combined_raw_report'] ?? ''
    ];
}

if ($data === null) {
    error_page('Unable to load motif result data.');
}

$raw_relative = $data['input_file'] ?? '';
if ($raw_relative === '') {
    error_page('Motif result data does not contain an input FASTA path.');
}

$raw_basename = pathinfo($raw_relative, PATHINFO_FILENAME);

if (strpos($raw_relative, 'results/examples/') === 0) {
    $aligned_relative = 'results/examples/aligned_' . $raw_basename . '.fasta';
} else {
    $aligned_relative = 'results/aligned_' . $raw_basename . '.fasta';
}

$aligned_exists = file_exists(__DIR__ . '/' . $aligned_relative);

$summary_relative = $data['summary_file'] ?? '';
$raw_report_relative = $data['raw_file'] ?? '';

$motif_stats_relative = $data['motif_stats_file'] ?? '';
$motif_stats_plot_relative = $data['motif_stats_plot_file'] ?? '';
$motif_stats_plot_created = $data['motif_stats_plot_created'] ?? false;
$motif_stats_plot_error = $data['motif_stats_plot_error'] ?? '';

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

require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Motif / Domain Scan</h1>
    <p>
        Review PROSITE motif hits across the selected sequence set, including
        hit counts, motif positions, and the combined raw motif report.
    </p>
</div>

<div class="box">
    <h2>Input and output files</h2>
    <p><strong>Input FASTA:</strong>
        <a href="<?php echo htmlspecialchars($raw_relative); ?>">
            <?php echo htmlspecialchars($raw_relative); ?>
        </a>
    </p>
    <p><strong>Motif summary JSON:</strong>
        <a href="<?php echo htmlspecialchars($summary_relative); ?>">
            <?php echo htmlspecialchars($summary_relative); ?>
        </a>
    </p>
    <?php if ($raw_report_relative !== ''): ?>
        <p><strong>Combined raw motif report:</strong>
            <a href="<?php echo htmlspecialchars($raw_report_relative); ?>">
                <?php echo htmlspecialchars($raw_report_relative); ?>
            </a>
        </p>
    <?php endif; ?>
        <?php if ($motif_stats_relative !== ''): ?>
        <p><strong>Motif statistics JSON:</strong>
            <a href="<?php echo htmlspecialchars($motif_stats_relative); ?>">
                <?php echo htmlspecialchars($motif_stats_relative); ?>
            </a>
        </p>
    <?php endif; ?>

    <?php if ($motif_stats_plot_relative !== '' && $motif_stats_plot_created): ?>
        <p><strong>Motif statistics plot:</strong>
            <a href="<?php echo htmlspecialchars($motif_stats_plot_relative); ?>">
                <?php echo htmlspecialchars($motif_stats_plot_relative); ?>
            </a>
        </p>
    <?php endif; ?>
    <p class="small">
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
    <h2>Motif hits frequency</h2>

    <?php if ($motif_stats_plot_relative !== '' && $motif_stats_plot_created && file_exists(__DIR__ . '/' . $motif_stats_plot_relative)): ?>
        <p>
            This bar chart summarises how often each detected PROSITE motif appears
            across the scanned sequence set.
        </p>
        <img src="<?php echo htmlspecialchars($motif_stats_plot_relative); ?>" alt="Motif hits frequency plot">
    <?php else: ?>
        <p class="warn">Motif statistics plot is not available.</p>
        <?php if ($motif_stats_plot_error !== ''): ?>
            <pre><?php echo htmlspecialchars($motif_stats_plot_error); ?></pre>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($motif_stats_relative !== ''): ?>
        <p>
            <a href="<?php echo htmlspecialchars($motif_stats_relative); ?>">
                Download motif counts (JSON)
            </a>
        </p>
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

<div class="box">
    <h2>Related analyses</h2>
    <p>
        <a href="analyze.php?file=<?php echo urlencode($raw_relative); ?>">
            View conservation analysis for this sequence set
        </a>
    </p>

    <?php if ($aligned_exists): ?>
        <p>
            <a href="tree.php?file=<?php echo urlencode($aligned_relative); ?>">
                Run phylogenetic tree analysis for this aligned sequence set
            </a>
        </p>
    <?php else: ?>
        <p class="warn">
            Phylogenetic tree analysis requires an aligned FASTA file, which is generated
            during conservation analysis.
            <a href="analysis_status.php?file=<?php echo urlencode($raw_relative); ?>">
                Run conservation analysis first
            </a>.
        </p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>