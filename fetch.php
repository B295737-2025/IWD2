<?php
require_once __DIR__ . '/lib/db_connect.php';
require_once __DIR__ . '/lib/cleanup_results.php';

cleanup_old_results(__DIR__ . '/results', 60);

$user_name = $_POST['user_name'] ?? 'guest';
$family = $_POST['family'] ?? '';
$taxon  = $_POST['taxon'] ?? '';

$user_name = trim($user_name);
$family = trim($family);
$taxon  = trim($taxon);

if ($user_name === '') {
    $user_name = 'guest';
}

$result = null;
$raw_output = '';
$history_saved = false;
$history_error = '';

if ($family !== '' && $taxon !== '') {
    $python = '/usr/bin/python3';
    $script = __DIR__ . '/lib/fetch_sequences.py';

    $command = $python . ' '
             . escapeshellarg($script) . ' '
             . escapeshellarg($family) . ' '
             . escapeshellarg($taxon) . ' 2>&1';

    $raw_output = shell_exec($command);
    $result = json_decode($raw_output, true);

    if (is_array($result) && isset($result['status']) && $result['status'] === 'ok') {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO history (user, family, taxon, result_link) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $user_name,
                $family,
                $taxon,
                $result['result_file']
            ]);
            $history_saved = true;
        } catch (PDOException $e) {
            $history_error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fetch Sequences</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
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
        .ok {
            color: #0a6b2d;
        }
        .warn {
            color: #a15c00;
        }
        .err {
            color: #a10000;
        }
        code, pre {
            background: #f3f3f3;
            padding: 2px 4px;
        }
        a {
            color: #1f4e79;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Fetch Sequences</h1>

    <div class="box">
    <?php if ($family === '' || $taxon === ''): ?>
        <p class="err"><strong>Error:</strong> missing protein family or taxonomic group.</p>

    <?php elseif (!is_array($result)): ?>
        <p class="err"><strong>Error:</strong> Python script did not return valid JSON.</p>
        <pre><?php echo htmlspecialchars($raw_output); ?></pre>

    <?php elseif ($result['status'] === 'empty'): ?>
        <p><strong>User Name:</strong> <?php echo htmlspecialchars($user_name); ?></p>
        <p><strong>NCBI query:</strong> <?php echo htmlspecialchars($result['term']); ?></p>
        <p class="warn"><?php echo htmlspecialchars($result['message']); ?></p>

    <?php elseif ($result['status'] === 'error'): ?>
        <p class="err"><strong>Error:</strong> <?php echo htmlspecialchars($result['message']); ?></p>
        <pre><?php echo htmlspecialchars($raw_output); ?></pre>

    <?php else: ?>
        <p><strong>User Name:</strong> <?php echo htmlspecialchars($user_name); ?></p>
        <p><strong>Protein family:</strong> <?php echo htmlspecialchars($family); ?></p>
        <p><strong>Taxonomic group:</strong> <?php echo htmlspecialchars($taxon); ?></p>
        <p><strong>NCBI query:</strong> <?php echo htmlspecialchars($result['term']); ?></p>
        <p><strong>Sequences retrieved:</strong> <?php echo htmlspecialchars((string)$result['sequence_count']); ?></p>
        <p><strong>Saved FASTA:</strong>
            <a href="<?php echo htmlspecialchars($result['result_file']); ?>">
                <?php echo htmlspecialchars($result['result_file']); ?>
            </a>
        </p>

        <p class="warn">
            Result files are retained on the server for 60 days.
            You can revisit recent analyses from the history page during this period.
        </p>

        <p>
            <a href="analysis_status.php?file=<?php echo urlencode($result['result_file']); ?>">
                Run conservation analysis
            </a>
        </p>
        <p>
            <a href="motif.php?file=<?php echo urlencode($result['result_file']); ?>">
                Run motif scan
            </a>
        </p>

        <?php if ($history_saved): ?>
            <p class="ok"><strong>History:</strong> this query has been saved to the database.</p>
        <?php elseif ($history_error !== ''): ?>
            <p class="warn"><strong>History not saved:</strong> <?php echo htmlspecialchars($history_error); ?></p>
        <?php endif; ?>

        <h2>Preview (first 5 sequences)</h2>
        <ul>
            <?php foreach ($result['preview'] as $row): ?>
                <li>
                    <strong><?php echo htmlspecialchars($row['id']); ?></strong>
                    (length: <?php echo htmlspecialchars((string)$row['length']); ?> aa)<br>
                    <?php echo htmlspecialchars($row['description']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    </div>

    <p><a href="index.php">Back to home</a></p>
    <p><a href="history.php">View history</a></p>
</body>
</html>