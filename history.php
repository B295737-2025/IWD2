<?php
$page_title = 'Query History';
$active_page = 'history';

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

    if (strpos($raw_relative, 'results/examples/') === 0) {
        return 'results/examples/aligned_' . $base . '.fasta';
    }

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

$example_available_rows = [];
$normal_available_rows = [];

foreach ($available_rows as $row) {
    if (!empty($row['is_example'])) {
        $example_available_rows[] = $row;
    } else {
        $normal_available_rows[] = $row;
    }
}

$available_rows = array_merge($example_available_rows, $normal_available_rows);

$example_unavailable_rows = [];
$normal_unavailable_rows = [];

foreach ($unavailable_rows as $row) {
    if (!empty($row['is_example'])) {
        $example_unavailable_rows[] = $row;
    } else {
        $normal_unavailable_rows[] = $row;
    }
}

$unavailable_rows = array_merge($example_unavailable_rows, $normal_unavailable_rows);

require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Query History</h1>
    <p>
        Browse previously saved sequence retrievals, filter them by user name,
        protein family, or taxonomic group, and reopen any results that are
        still available on the server.
    </p>
</div>

<div class="box">
    <p class="small">
        Result files are retained on the server for 60 days. Older files may no
        longer be available even if the database record still exists.
    </p>
</div>

<div class="box">
    <h2>Filter history</h2>

    <form class="form-grid" method="get" action="history.php">
        <label for="user_name">User name:</label>
        <input id="user_name" name="user_name" type="text" value="<?php echo htmlspecialchars($filter_user); ?>">

        <label for="family">Protein family:</label>
        <input id="family" name="family" type="text" value="<?php echo htmlspecialchars($filter_family); ?>">

        <label for="taxon">Taxonomic group:</label>
        <input id="taxon" name="taxon" type="text" value="<?php echo htmlspecialchars($filter_taxon); ?>">

        <div></div>
        <div class="form-actions">
            <input type="submit" value="Filter">
            <a href="history.php">Clear filters</a>
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
                                    <div><a href="example.php">Example dataset page</a></div>
                                    <div><a href="<?php echo htmlspecialchars($row['raw_relative']); ?>">Raw FASTA</a></div>
                                    <?php if ($row['aligned_exists']): ?>
                                        <div><a href="<?php echo htmlspecialchars($row['aligned_relative']); ?>">Aligned FASTA</a></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div><a href="<?php echo htmlspecialchars($row['raw_relative']); ?>">Raw FASTA</a></div>
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
                    These records remain in the database as query history, but their
                    raw FASTA files are no longer present on the server.
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

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>