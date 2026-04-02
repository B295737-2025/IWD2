<?php
$page_title = 'Phylogenetic Tree Analysis';
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
        <h1>Phylogenetic Tree Analysis</h1>
        <p>An error occurred while loading the phylogenetic tree result.</p>
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
    error_page('No aligned FASTA or tree job file was provided.');
}

$data = null;

/*
 * Mode 1: display from a completed job file
 */
if ($job_file !== '') {
    $relative_job = ltrim($job_file, '/');
    $absolute_job = realpath(__DIR__ . '/' . $relative_job);

    if ($absolute_job === false || strpos($absolute_job, $results_dir) !== 0) {
        error_page('Invalid tree result file path.');
    }

    if (!file_exists($absolute_job)) {
        error_page('Tree result file does not exist.');
    }

    $json_text = file_get_contents($absolute_job);
    if ($json_text === false) {
        error_page('Could not read tree result file.');
    }

    $data = json_decode($json_text, true);
    if (!is_array($data)) {
        error_page('Tree result JSON is invalid.');
    }

    if (($data['status'] ?? '') !== 'ok') {
        error_page($data['message'] ?? 'Unknown error.', $data['detail'] ?? '');
    }
}

/*
 * Mode 2: direct access via ?file=...
 * If cached tree outputs already exist, display them directly.
 * Only redirect to the waiting page if outputs are not present yet.
 */
if ($data === null && $input_file !== '') {
    $relative_input = ltrim($input_file, '/');
    $absolute_input = realpath(__DIR__ . '/' . $relative_input);

    if ($absolute_input === false || strpos($absolute_input, $results_dir) !== 0) {
        error_page('Aligned FASTA file is not available. Please run conservation analysis first.');
    }

    if (!file_exists($absolute_input)) {
        error_page('Aligned FASTA file does not exist.');
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

    if (!file_exists($summary_absolute)) {
        header('Location: tree_status.php?file=' . urlencode($relative_input));
        exit;
    }

    $json_text = file_get_contents($summary_absolute);
    if ($json_text === false) {
        error_page('Could not read tree summary JSON file.');
    }

    $tree_data = json_decode($json_text, true);
    if (!is_array($tree_data)) {
        error_page('Saved tree summary JSON is invalid.');
    }

    if (($tree_data['status'] ?? '') !== 'ok') {
        $msg = $tree_data['message'] ?? 'Unknown error';
        error_page('Saved tree summary JSON does not contain a successful result: ' . $msg);
    }

    $data = [
        'status' => 'ok',
        'message' => '',
        'detail' => '',
        'input_file' => $relative_input,
        'summary_file' => $summary_relative,
        'tree_file' => file_exists($tree_absolute) ? $tree_relative : '',
        'png_file' => file_exists($png_absolute) ? $png_relative : '',
        'sequence_count' => $tree_data['sequence_count'] ?? 0,
        'min_distance' => $tree_data['min_distance'] ?? '',
        'max_distance' => $tree_data['max_distance'] ?? '',
        'average_distance' => $tree_data['average_distance'] ?? '',
        'png_created' => $tree_data['png_created'] ?? false,
        'png_error' => $tree_data['png_error'] ?? ''
    ];
}

if ($data === null) {
    error_page('Unable to load tree result data.');
}

$aligned_relative = $data['input_file'] ?? '';
if ($aligned_relative === '') {
    error_page('Tree result data does not contain an aligned FASTA path.');
}

$aligned_basename = pathinfo($aligned_relative, PATHINFO_FILENAME);
$raw_basename = $aligned_basename;

if (strpos($raw_basename, 'aligned_') === 0) {
    $raw_basename = substr($raw_basename, strlen('aligned_'));
}

if (strpos($aligned_relative, 'results/examples/') === 0) {
    $raw_relative = 'results/examples/' . $raw_basename . '.fasta';
} else {
    $raw_relative = 'results/' . $raw_basename . '.fasta';
}

$summary_relative = $data['summary_file'] ?? '';
$tree_relative = $data['tree_file'] ?? '';
$png_relative = $data['png_file'] ?? '';

$sequence_count = $data['sequence_count'] ?? 0;
$min_distance = $data['min_distance'] ?? '';
$max_distance = $data['max_distance'] ?? '';
$average_distance = $data['average_distance'] ?? '';
$png_created = $data['png_created'] ?? false;
$png_error = $data['png_error'] ?? '';

$interpretation = '';
if (is_numeric($average_distance)) {
    if ($average_distance < 0.15) {
        $interpretation = 'The aligned sequences are very similar overall, so the tree is expected to contain short branch lengths and closely clustered sequences.';
    } elseif ($average_distance < 0.35) {
        $interpretation = 'The aligned sequences show moderate divergence overall, suggesting a mixture of closely related and more distinct proteins.';
    } else {
        $interpretation = 'The aligned sequences show substantial divergence overall, so the tree is likely to contain several clearly separated branches.';
    }
}

require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Phylogenetic Tree Analysis</h1>
    <p>
        Review the neighbour-joining tree generated from the aligned sequence set,
        together with pairwise distance summary statistics and related analysis links.
    </p>
</div>

<div class="box">
    <h2>Input and output files</h2>
    <p><strong>Aligned FASTA:</strong>
        <a href="<?php echo htmlspecialchars($aligned_relative); ?>">
            <?php echo htmlspecialchars($aligned_relative); ?>
        </a>
    </p>
    <p><strong>Tree summary JSON:</strong>
        <a href="<?php echo htmlspecialchars($summary_relative); ?>">
            <?php echo htmlspecialchars($summary_relative); ?>
        </a>
    </p>
    <?php if ($tree_relative !== ''): ?>
        <p><strong>Newick tree file:</strong>
            <a href="<?php echo htmlspecialchars($tree_relative); ?>">
                <?php echo htmlspecialchars($tree_relative); ?>
            </a>
        </p>
    <?php endif; ?>
    <?php if ($png_relative !== '' && $png_created): ?>
        <p><strong>Tree image:</strong>
            <a href="<?php echo htmlspecialchars($png_relative); ?>">
                <?php echo htmlspecialchars($png_relative); ?>
            </a>
        </p>
    <?php endif; ?>
    <p class="small">
        Result files are retained on the server for 60 days.
        You can revisit recent analyses from the history page during this period.
    </p>
</div>

<div class="box">
    <h2>Tree summary</h2>
    <p><strong>Total sequences in tree:</strong> <?php echo htmlspecialchars((string)$sequence_count); ?></p>
    <p><strong>Minimum pairwise distance:</strong> <?php echo htmlspecialchars((string)$min_distance); ?></p>
    <p><strong>Maximum pairwise distance:</strong> <?php echo htmlspecialchars((string)$max_distance); ?></p>
    <p><strong>Average pairwise distance:</strong> <?php echo htmlspecialchars((string)$average_distance); ?></p>
</div>

<div class="box">
    <h2>Initial interpretation</h2>
    <p><?php echo htmlspecialchars($interpretation); ?></p>
    <p>
        This neighbour-joining tree provides a simple overview of how closely related the
        retrieved proteins are within the selected taxonomic group.
    </p>
</div>

<div class="box">
    <h2>Tree image</h2>
    <?php if ($png_relative !== '' && $png_created && file_exists(__DIR__ . '/' . $png_relative)): ?>
        <img src="<?php echo htmlspecialchars($png_relative); ?>" alt="Neighbour-joining tree">
    <?php else: ?>
        <p>PNG tree image is not available.</p>
        <?php if ($png_error !== ''): ?>
            <pre><?php echo htmlspecialchars($png_error); ?></pre>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="box">
    <h2>Related analyses</h2>
    <p>
        <a href="analyze.php?file=<?php echo urlencode($raw_relative); ?>">
            View conservation analysis for this sequence set
        </a>
    </p>
    <p>
        <a href="motif.php?file=<?php echo urlencode($raw_relative); ?>">
            View motif scan for this sequence set
        </a>
    </p>
</div>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>