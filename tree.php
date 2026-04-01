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
        <title>Phylogenetic Tree Analysis</title>
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
        <h1>Phylogenetic Tree Analysis</h1>
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
    error_page('No aligned FASTA file was provided.');
}

$relative_input = ltrim($input_file, '/');
$absolute_input = realpath(__DIR__ . '/' . $relative_input);

if ($absolute_input === false || strpos($absolute_input, $results_dir) !== 0) {
    error_page('Invalid input file path.');
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
        error_page('Tree analysis did not return valid JSON.', $run_output);
    }

    if (($run_result['status'] ?? '') !== 'ok') {
        $msg = $run_result['message'] ?? 'Unknown error';
        error_page('Tree analysis failed: ' . $msg, $run_output);
    }

    file_put_contents(
        $summary_absolute,
        json_encode($run_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    $data = $run_result;
} else {
    $json_text = file_get_contents($summary_absolute);
    if ($json_text === false) {
        error_page('Could not read tree summary JSON file.');
    }

    $data = json_decode($json_text, true);
    if (!is_array($data)) {
        error_page('Saved tree summary JSON is invalid.');
    }
}

if (($data['status'] ?? '') !== 'ok') {
    error_page('Saved tree summary JSON does not contain a successful result.');
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phylogenetic Tree Analysis</title>
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
        img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ccc;
            background: white;
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
    <h1>Phylogenetic Tree Analysis</h1>

    <div class="box">
        <h2>Input and output files</h2>
        <p><strong>Aligned FASTA:</strong>
            <a href="<?php echo htmlspecialchars($relative_input); ?>">
                <?php echo htmlspecialchars($relative_input); ?>
            </a>
        </p>
        <p><strong>Tree summary JSON:</strong>
            <a href="<?php echo htmlspecialchars($summary_relative); ?>">
                <?php echo htmlspecialchars($summary_relative); ?>
            </a>
        </p>
        <p><strong>Newick tree file:</strong>
            <a href="<?php echo htmlspecialchars($tree_relative); ?>">
                <?php echo htmlspecialchars($tree_relative); ?>
            </a>
        </p>
        <?php if ($png_created && file_exists($png_absolute)): ?>
            <p><strong>Tree image:</strong>
                <a href="<?php echo htmlspecialchars($png_relative); ?>">
                    <?php echo htmlspecialchars($png_relative); ?>
                </a>
            </p>
        <?php endif; ?>
        <p class="note">
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
        <?php if ($png_created && file_exists($png_absolute)): ?>
            <img src="<?php echo htmlspecialchars($png_relative); ?>" alt="Neighbour-joining tree">
        <?php else: ?>
            <p>PNG tree image is not available.</p>
            <?php if ($png_error !== ''): ?>
                <pre><?php echo htmlspecialchars($png_error); ?></pre>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <p><a href="index.php">Back to home</a></p>
    <p><a href="history.php">View history</a></p>
</body>
</html>
