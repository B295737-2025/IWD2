<?php
$page_title = 'Fetch Sequences';
$active_page = '';

$job_file = $_GET['job'] ?? '';
$job_file = trim($job_file);

$results_dir = realpath(__DIR__ . '/results');

function error_page($message, $detail = '') {
    global $page_title, $active_page;
    require_once __DIR__ . '/lib/site_header.php';
    ?>
    <div class="hero">
        <h1>Fetch Sequences</h1>
        <p>An error occurred while loading the sequence retrieval result.</p>
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

if ($job_file === '') {
    error_page('No fetch result file was provided.');
}

$relative_job = ltrim($job_file, '/');
$absolute_job = realpath(__DIR__ . '/' . $relative_job);

if ($absolute_job === false || strpos($absolute_job, $results_dir) !== 0) {
    error_page('Invalid fetch result file path.');
}

if (!file_exists($absolute_job)) {
    error_page('Fetch result file does not exist.');
}

$json_text = file_get_contents($absolute_job);
if ($json_text === false) {
    error_page('Could not read fetch result file.');
}

$data = json_decode($json_text, true);
if (!is_array($data)) {
    error_page('Fetch result JSON is invalid.');
}

$user_name = $data['user_name'] ?? 'guest';
$family = $data['family'] ?? '';
$taxon = $data['taxon'] ?? '';
$max_sequences_requested = $data['max_sequences_requested'] ?? '';
$raw_output = $data['raw_output'] ?? '';
$history_saved = $data['history_saved'] ?? false;
$history_error = $data['history_error'] ?? '';

require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Fetch Sequences</h1>
    <p>
        Sequence retrieval has completed. Review the retrieved FASTA file,
        preview the first records, and continue to conservation or motif analysis.
    </p>
</div>

<div class="box">
<?php if (($data['status'] ?? '') === 'empty'): ?>
    <p><strong>User Name:</strong> <?php echo htmlspecialchars($user_name); ?></p>
    <p><strong>Protein family:</strong> <?php echo htmlspecialchars($family); ?></p>
    <p><strong>Taxonomic group:</strong> <?php echo htmlspecialchars($taxon); ?></p>
    <p><strong>Maximum sequences requested:</strong> <?php echo htmlspecialchars((string)$max_sequences_requested); ?></p>
    <p><strong>NCBI query:</strong> <?php echo htmlspecialchars($data['term'] ?? ''); ?></p>
    <p class="warn"><?php echo htmlspecialchars($data['message'] ?? 'No matching protein sequences found.'); ?></p>

<?php elseif (($data['status'] ?? '') === 'error'): ?>
    <p><strong>User Name:</strong> <?php echo htmlspecialchars($user_name); ?></p>
    <p><strong>Protein family:</strong> <?php echo htmlspecialchars($family); ?></p>
    <p><strong>Taxonomic group:</strong> <?php echo htmlspecialchars($taxon); ?></p>
    <p><strong>Maximum sequences requested:</strong> <?php echo htmlspecialchars((string)$max_sequences_requested); ?></p>
    <p class="err"><strong>Error:</strong> <?php echo htmlspecialchars($data['message'] ?? 'Unknown error'); ?></p>
    <?php if ($raw_output !== ''): ?>
        <pre><?php echo htmlspecialchars($raw_output); ?></pre>
    <?php endif; ?>

<?php else: ?>
    <p><strong>User Name:</strong> <?php echo htmlspecialchars($user_name); ?></p>
    <p><strong>Protein family:</strong> <?php echo htmlspecialchars($family); ?></p>
    <p><strong>Taxonomic group:</strong> <?php echo htmlspecialchars($taxon); ?></p>
    <p><strong>Maximum sequences requested:</strong> <?php echo htmlspecialchars((string)$max_sequences_requested); ?></p>
    <p><strong>NCBI query:</strong> <?php echo htmlspecialchars($data['term'] ?? ''); ?></p>
    <p><strong>Sequences retrieved:</strong> <?php echo htmlspecialchars((string)($data['sequence_count'] ?? 0)); ?></p>
    <p><strong>Saved FASTA:</strong>
        <a href="<?php echo htmlspecialchars($data['result_file'] ?? ''); ?>">
            <?php echo htmlspecialchars($data['result_file'] ?? ''); ?>
        </a>
    </p>

    <p class="small">
        Result files are retained on the server for 60 days. You can revisit
        recent analyses from the history page during this period.
    </p>

    <?php if ($history_saved): ?>
        <p class="ok"><strong>History:</strong> this query has been saved to the database.</p>
    <?php elseif ($history_error !== ''): ?>
        <p class="warn"><strong>History not saved:</strong> <?php echo htmlspecialchars($history_error); ?></p>
    <?php endif; ?>
<?php endif; ?>
</div>

<?php if (($data['status'] ?? '') === 'ok'): ?>
    <div class="box">
        <h2>Next analyses</h2>
        <p>
            <a href="analysis_status.php?file=<?php echo urlencode($data['result_file'] ?? ''); ?>">
                Run conservation analysis
            </a>
        </p>
        <p>
            <a href="motif.php?file=<?php echo urlencode($data['result_file'] ?? ''); ?>">
                Run motif scan
            </a>
        </p>
    </div>

    <div class="box">
        <h2>Preview (first 5 sequences)</h2>
        <?php if (empty($data['preview'])): ?>
            <p>No preview records are available.</p>
        <?php else: ?>
            <ul>
                <?php foreach (($data['preview'] ?? []) as $row): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($row['id'] ?? ''); ?></strong>
                        (length: <?php echo htmlspecialchars((string)($row['length'] ?? '')); ?> aa)<br>
                        <?php echo htmlspecialchars($row['description'] ?? ''); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>