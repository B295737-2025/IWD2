<?php
$input_file = $_GET['file'] ?? '';
$input_file = trim($input_file);

$results_dir = realpath(__DIR__ . '/results');

function error_page($message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Conservation Analysis</title>
    </head>
    <body>
        <h1>Conservation Analysis</h1>
        <p><strong>Error:</strong> <?php echo htmlspecialchars($message); ?></p>
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
$aligned_basename = 'aligned_' . $input_basename . '.fasta';
$aligned_absolute = $results_dir . '/' . $aligned_basename;
$aligned_relative = 'results/' . $aligned_basename;

$conservation_script = __DIR__ . '/lib/conservation_analysis.py';
$clustalo = '/usr/bin/clustalo';
$python = '/usr/bin/python3';

$align_command = $clustalo
    . ' --threads=4'
    . ' -i ' . escapeshellarg($absolute_input)
    . ' -o ' . escapeshellarg($aligned_absolute)
    . ' --force --outfmt=fasta 2>&1';

$align_output = shell_exec($align_command);

if (!file_exists($aligned_absolute)) {
    error_page("Alignment failed.<br><pre>" . htmlspecialchars($align_output) . "</pre>");
}

$cons_command = $python . ' ' . escapeshellarg($conservation_script) . ' '
              . escapeshellarg($aligned_absolute) . ' 2>&1';

$cons_output = shell_exec($cons_command);
$cons_result = json_decode($cons_output, true);

if (!is_array($cons_result)) {
    error_page("Conservation script did not return valid JSON.<br><pre>" . htmlspecialchars($cons_output) . "</pre>");
}

if (($cons_result['status'] ?? '') !== 'ok') {
    $msg = $cons_result['message'] ?? 'Unknown error';
    error_page("Conservation analysis failed: " . htmlspecialchars($msg) . "<br><pre>" . htmlspecialchars($cons_output) . "</pre>");
}

$matrix_relative = 'results/' . basename($cons_result['identity_matrix_file']);
$summary_relative = 'results/' . basename($cons_result['summary_file']);

$seq_count = $cons_result['sequence_count'];
$align_length = $cons_result['alignment_length'];
$avg_identity = $cons_result['average_pairwise_identity'];
$min_identity = $cons_result['minimum_pairwise_identity'];
$max_identity = $cons_result['maximum_pairwise_identity'];
$avg_compared = $cons_result['average_compared_sites'];

$interpretation = '';
if ($avg_identity >= 80) {
    $interpretation = 'These sequences appear highly conserved overall.';
} elseif ($avg_identity >= 50) {
    $interpretation = 'These sequences show moderate conservation overall, with both conserved and variable regions.';
} else {
    $interpretation = 'These sequences appear relatively diverse overall, suggesting substantial sequence variation.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conservation Analysis</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 950px;
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
        ul {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <h1>Conservation Analysis</h1>

    <div class="box">
        <h2>Input and output files</h2>
        <ul>
            <li><strong>Input FASTA:</strong> <a href="<?php echo htmlspecialchars($relative_input); ?>"><?php echo htmlspecialchars($relative_input); ?></a></li>
            <li><strong>Aligned FASTA:</strong> <a href="<?php echo htmlspecialchars($aligned_relative); ?>"><?php echo htmlspecialchars($aligned_relative); ?></a></li>
            <li><strong>Identity matrix:</strong> <a href="<?php echo htmlspecialchars($matrix_relative); ?>"><?php echo htmlspecialchars($matrix_relative); ?></a></li>
            <li><strong>Summary JSON:</strong> <a href="<?php echo htmlspecialchars($summary_relative); ?>"><?php echo htmlspecialchars($summary_relative); ?></a></li>
        </ul>
        <p>
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
        <h2>Initial interpretation</h2>
        <p><?php echo htmlspecialchars($interpretation); ?></p>
        <p>
            A high maximum identity together with a much lower minimum identity suggests that
            this dataset likely contains both closely related sequences and more divergent isoforms
            or homologous proteins within the chosen taxonomic group.
        </p>
    </div>

    <p><a href="index.php">Back to home</a></p>
    <p><a href="history.php">View history</a></p>
</body>
</html>