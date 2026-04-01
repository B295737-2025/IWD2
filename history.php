<?php
require_once __DIR__ . '/lib/db_connect.php';

$filter_user = trim($_GET['user_name'] ?? '');
$filter_family = trim($_GET['family'] ?? '');
$filter_taxon = trim($_GET['taxon'] ?? '');

$rows = [];
$db_error = '';

try {
    $sql = "
        SELECT id, user, family, taxon, result_link, created_at
        FROM history
    ";

    $conditions = [];
    $params = [];

    if ($filter_user !== '') {
        $conditions[] = "user = ?";
        $params[] = $filter_user;
    }

    if ($filter_family !== '') {
        $conditions[] = "family LIKE ?";
        $params[] = '%' . $filter_family . '%';
    }

    if ($filter_taxon !== '') {
        $conditions[] = "taxon LIKE ?";
        $params[] = '%' . $filter_taxon . '%';
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY created_at DESC, id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

$results_dir = realpath(__DIR__ . '/results');

function file_exists_in_results($relative_path, $results_dir) {
    $relative_path = ltrim($relative_path, '/');
    $absolute_path = realpath(__DIR__ . '/' . $relative_path);

    if ($absolute_path === false) {
        return false;
    }

    return strpos($absolute_path, $results_dir) === 0 && file_exists($absolute_path);
}

function aligned_relative_from_raw($raw_relative) {
    $raw_relative = ltrim($raw_relative, '/');
    $base = pathinfo($raw_relative, PATHINFO_FILENAME);
    return 'results/aligned_' . $base . '.fasta';
}

$available_rows = [];
$unavailable_rows = [];

foreach ($rows as $row) {
    $raw_relative = ltrim($row['result_link'], '/');
    $raw_exists = file_exists_in_results($raw_relative, $results_dir);

    $aligned_relative = aligned_relative_from_raw($raw_relative);
    $aligned_exists = file_exists_in_results($aligned_relative, $results_dir);

    $record = [
        'id' => $row['id'],
        'user' => $row['user'] ?? '',
        'family' => $row['family'] ?? '',
        'taxon' => $row['taxon'] ?? '',
        'created_at' => $row['created_at'] ?? '',
        'raw_relative' => $raw_relative,
        'raw_exists' => $raw_exists,
        'aligned_relative' => $aligned_relative,
        'aligned_exists' => $aligned_exists,
        'is_example' => (
            ($row['user'] ?? '') === 'example_dataset' ||
            strpos($raw_relative, 'results/examples/') === 0
        )
    ];

    if ($raw_exists) {
        $available_rows[] = $record;
    } else {
        $unavailable_rows[] = $record;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query History</title>
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
        form.filter-form {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 10px 12px;
            align-items: center;
            max-width: 760px;
        }
        input[type="text"] {
            padding: 8px;
            width: 100%;
            max-width: 420px;
        }
        .button-row {
            grid-column: 2;
        }
        input[type="submit"], .clear-link {
            padding: 9px 14px;
            display: inline-block;
            margin-right: 8px;
        }
        .clear-link {
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #f3f3f3;
            color: #333;
            text-decoration: none;
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
        .warn {
            color: #a15c00;
        }
        .err {
            color: #a10000;
        }
        .small {
            color: #555;
            font-size: 0.95em;
        }
        details {
            margin-top: 10px;
        }
        summary {
            cursor: pointer;
            font-weight: bold;
            color: #1f4e79;
        }
    </style>
</head>
<body>
    <h1>Query History</h1>

    <div class="box">
        <p>
            This page lists previous sequence retrievals saved in the database.
            You can filter records by user name, protein family, and taxonomic group.
        </p>
        <p class="small">
            Result files are retained on the server for 60 days.
            Older files may no longer be available even if the database entry still exists.
        </p>
    </div>

    <div class="box">
        <h2>Filter history</h2>
        <form class="filter-form" method="get" action="history.php">
            <label for="user_name">User Name:</label>
            <input id="user_name" name="user_name" type="text" value="<?php echo htmlspecialchars($filter_user); ?>">

            <label for="family">Protein Family:</label>
            <input id="family" name="family" type="text" value="<?php echo htmlspecialchars($filter_family); ?>">

            <label for="taxon">Taxonomic Group:</label>
            <input id="taxon" name="taxon" type="text" value="<?php echo htmlspecialchars($filter_taxon); ?>">

            <div></div>
            <div class="button-row">
                <input type="submit" value="Filter">
                <a class="clear-link" href="history.php">Clear</a>
            </div>
        </form>
    </div>

    <?php if ($db_error !== ''): ?>
        <div class="box">
            <p class="err"><strong>Database error:</strong> <?php echo htmlspecialchars($db_error); ?></p>
        </div>

    <?php else: ?>

        <div class="box">
            <h2>Available records</h2>

            <?php if (empty($available_rows)): ?>
                <p>No currently available records were found for this filter.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Protein family</th>
                            <th>Taxonomic group</th>
                            <th>Saved at</th>
                            <th>Available links</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($available_rows as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string)$row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['user']); ?></td>
                                <td><?php echo htmlspecialchars($row['family']); ?></td>
                                <td><?php echo htmlspecialchars($row['taxon']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td>
                                    <?php if ($row['is_example']): ?>
                                        <div>
                                            <a href="example.php">Example dataset page</a>
                                        </div>
                                        <div>
                                            <a href="<?php echo htmlspecialchars($row['raw_relative']); ?>">Raw FASTA</a>
                                        </div>
                                        <?php if ($row['aligned_exists']): ?>
                                            <div>
                                                <a href="<?php echo htmlspecialchars($row['aligned_relative']); ?>">Aligned FASTA</a>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div>
                                            <a href="<?php echo htmlspecialchars($row['raw_relative']); ?>">Raw FASTA</a>
                                        </div>
                                        <div>
                                            <a href="analyze.php?file=<?php echo urlencode($row['raw_relative']); ?>">
                                                Conservation analysis
                                            </a>
                                        </div>
                                        <div>
                                            <a href="motif.php?file=<?php echo urlencode($row['raw_relative']); ?>">
                                                Motif scan
                                            </a>
                                        </div>

                                        <?php if ($row['aligned_exists']): ?>
                                            <div>
                                                <a href="tree.php?file=<?php echo urlencode($row['aligned_relative']); ?>">
                                                    Phylogenetic tree
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="small">Tree link will appear after conservation output exists.</div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="box">
            <details>
                <summary>Unavailable / expired records (<?php echo count($unavailable_rows); ?>)</summary>

                <?php if (empty($unavailable_rows)): ?>
                    <p>No unavailable records were found for this filter.</p>
                <?php else: ?>
                    <p class="small">
                        These records remain in the database as query history, but their raw FASTA files
                        are no longer present on the server.
                    </p>

                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Protein family</th>
                                <th>Taxonomic group</th>
                                <th>Saved at</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unavailable_rows as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user']); ?></td>
                                    <td><?php echo htmlspecialchars($row['family']); ?></td>
                                    <td><?php echo htmlspecialchars($row['taxon']); ?></td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td class="warn">Raw FASTA file is no longer available.</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </details>
        </div>

    <?php endif; ?>

    <p><a href="index.php">Back to home</a></p>
</body>
</html>