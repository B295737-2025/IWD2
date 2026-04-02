<?php
$page_title = 'Conservation Analysis';
$active_page = '';

$input_file = $_GET['file'] ?? '';
$input_file = trim($input_file);

$results_dir = realpath(__DIR__ . '/results');

function error_page($message) {
    global $page_title, $active_page;
    require_once __DIR__ . '/lib/site_header.php';
    ?>
    <div class="hero">
        <h1>Conservation Analysis</h1>
        <p>An error occurred while loading the conservation analysis result.</p>
    </div>

    <div class="box">
        <p class="err"><strong>Error:</strong> <?php echo htmlspecialchars($message); ?></p>
    </div>
    <?php
    require_once __DIR__ . '/lib/site_footer.php';
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

$aligned_basename = 'aligned_' . $input_basename . '.fasta';
$matrix_basename  = 'aligned_' . $input_basename . '_identity_matrix.tsv';
$summary_basename = 'aligned_' . $input_basename . '_conservation_summary.json';

$aligned_absolute = $results_dir . '/' . $aligned_basename;
$matrix_absolute  = $results_dir . '/' . $matrix_basename;
$summary_absolute = $results_dir . '/' . $summary_basename;

$aligned_relative = 'results/' . $aligned_basename;
$matrix_relative  = 'results/' . $matrix_basename;
$summary_relative = 'results/' . $summary_basename;

$profile_basename = 'aligned_' . $input_basename . '_conservation_profile.tsv';
$plot_basename    = 'aligned_' . $input_basename . '_conservation_plot.png';

$profile_absolute = $results_dir . '/' . $profile_basename;
$plot_absolute    = $results_dir . '/' . $plot_basename;

$profile_relative = 'results/' . $profile_basename;
$plot_relative    = 'results/' . $plot_basename;

/*
 * This page only displays existing conservation analysis results.
 * If the required output files are not present yet, redirect to the status page.
 */
if (
    !file_exists($aligned_absolute) ||
    !file_exists($matrix_absolute) ||
    !file_exists($summary_absolute)
) {
    header('Location: analysis_status.php?file=' . urlencode($relative_input));
    exit;
}

$json_text = file_get_contents($summary_absolute);
if ($json_text === false) {
    error_page('Could not read conservation summary JSON file.');
}

$cons_result = json_decode($json_text, true);
if (!is_array($cons_result)) {
    error_page('Saved conservation summary JSON is invalid.');
}

if (($cons_result['status'] ?? '') !== 'ok') {
    $msg = $cons_result['message'] ?? 'Unknown error';
    error_page('Saved conservation summary does not contain a successful result: ' . $msg);
}

$seq_count     = $cons_result['sequence_count'] ?? '';
$align_length  = $cons_result['alignment_length'] ?? '';
$avg_identity  = $cons_result['average_pairwise_identity'] ?? '';
$min_identity  = $cons_result['minimum_pairwise_identity'] ?? '';
$max_identity  = $cons_result['maximum_pairwise_identity'] ?? '';
$avg_compared  = $cons_result['average_compared_sites'] ?? '';

$interpretation = '';
if (is_numeric($avg_identity)) {
    if ($avg_identity >= 80) {
        $interpretation = 'These sequences appear highly conserved overall.';
    } elseif ($avg_identity >= 50) {
        $interpretation = 'These sequences show moderate conservation overall, with both conserved and variable regions.';
    } else {
        $interpretation = 'These sequences appear relatively diverse overall, suggesting substantial sequence variation.';
    }
}

require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Conservation Analysis</h1>
    <p>
        Review the alignment-based conservation summary for the selected sequence set,
        including pairwise identity statistics and links to downstream analyses.
    </p>
</div>

<div class="box">
    <h2>Input and output files</h2>
    <ul>
        <li><strong>Input FASTA:</strong> <a href="<?php echo htmlspecialchars($relative_input); ?>"><?php echo htmlspecialchars($relative_input); ?></a></li>
        <li><strong>Aligned FASTA:</strong> <a href="<?php echo htmlspecialchars($aligned_relative); ?>"><?php echo htmlspecialchars($aligned_relative); ?></a></li>
        <li><strong>Identity matrix:</strong> <a href="<?php echo htmlspecialchars($matrix_relative); ?>"><?php echo htmlspecialchars($matrix_relative); ?></a></li>
        <li><strong>Summary JSON:</strong> <a href="<?php echo htmlspecialchars($summary_relative); ?>"><?php echo htmlspecialchars($summary_relative); ?></a></li>
        <?php if (file_exists($profile_absolute)): ?>
            <li><strong>Conservation profile TSV:</strong> <a href="<?php echo htmlspecialchars($profile_relative); ?>"><?php echo htmlspecialchars($profile_relative); ?></a></li>
        <?php endif; ?>
        <?php if (file_exists($plot_absolute)): ?>
            <li><strong>Conservation plot PNG:</strong> <a href="<?php echo htmlspecialchars($plot_relative); ?>"><?php echo htmlspecialchars($plot_relative); ?></a></li>
        <?php endif; ?>
    </ul>
    <p class="small">
        Result files are retained on the server for 60 days.
        You can revisit recent analyses from the history page during this period.
    </p>
</div>

<div class="box">
    <h2>Next analyses</h2>
    <p>
        <a href="motif.php?file=<?php echo urlencode($relative_input); ?>">
            Run motif scan for this sequence set
        </a>
    </p>
    <p>
        <a href="tree.php?file=<?php echo urlencode($aligned_relative); ?>">
            Run phylogenetic tree analysis for this aligned sequence set
        </a>
    </p>
</div>

<div class="box">
    <h2>Alignment summary</h2>
    <p><strong>Sequence count:</strong> <?php echo htmlspecialchars((string)$seq_count); ?></p>
    <p><strong>Alignment length:</strong> <?php echo htmlspecialchars((string)$align_length); ?> aa</p>
    <p><strong>Average pairwise identity:</strong> <?php echo htmlspecialchars((string)$avg_identity); ?>%</p>
    <p><strong>Minimum pairwise identity:</strong> <?php echo htmlspecialchars((string)$min_identity); ?>%</p>
    <p><strong>Maximum pairwise identity:</strong> <?php echo htmlspecialchars((string)$max_identity); ?>%</p>
    <p><strong>Average compared non-gap sites:</strong> <?php echo htmlspecialchars((string)$avg_compared); ?></p>
</div>

<div class="box">
    <h2>Conservation profile</h2>

    <?php if (file_exists($plot_absolute)): ?>
        <p>
            This plot shows how strongly conserved each alignment column is across
            the selected sequence set.
        </p>
        <img src="<?php echo htmlspecialchars($plot_relative); ?>" alt="Conservation profile plot">
    <?php else: ?>
        <p class="warn">Conservation plot PNG is not available.</p>
    <?php endif; ?>

    <?php if (file_exists($profile_absolute)): ?>
        <p>
            <a href="<?php echo htmlspecialchars($profile_relative); ?>">
                Download conservation profile (TSV)
            </a>
        </p>
    <?php endif; ?>
</div>

<div class="box">
    <h2>Initial interpretation</h2>
    <p><?php echo htmlspecialchars($interpretation); ?></p>
    <p>
        A high maximum identity together with a much lower minimum identity suggests that
        this dataset likely contains both closely related sequences and more divergent isoforms
        or homologous proteins within the chosen taxonomic group.
    </p>
</div>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>