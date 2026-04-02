<?php
$page_title = 'Example Dataset';
$active_page = 'example';

$example_files = [
    'raw_fasta' => 'results/examples/glucose-6-phosphatase_Aves_example.fasta',
    'aligned_fasta' => 'results/examples/aligned_glucose-6-phosphatase_Aves_example.fasta',
    'identity_matrix' => 'results/examples/aligned_glucose-6-phosphatase_Aves_example_identity_matrix.tsv',
    'conservation_summary' => 'results/examples/aligned_glucose-6-phosphatase_Aves_example_conservation_summary.json',
    'motif_summary' => 'results/examples/glucose-6-phosphatase_Aves_example_motif_summary.json',
    'motif_raw' => 'results/examples/glucose-6-phosphatase_Aves_example_motif_raw.txt',
    'tree_newick' => 'results/examples/aligned_glucose-6-phosphatase_Aves_example_nj_tree.nwk',
    'tree_png' => 'results/examples/aligned_glucose-6-phosphatase_Aves_example_nj_tree.png',
    'tree_summary' => 'results/examples/aligned_glucose-6-phosphatase_Aves_example_tree_summary.json'
];

function error_page($message, $detail = '') {
    global $page_title, $active_page;
    require_once __DIR__ . '/lib/site_header.php';
    ?>
    <div class="hero">
        <h1>Example Dataset</h1>
        <p>An error occurred while loading the pre-computed example dataset.</p>
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

function load_json_file($relative_path) {
    $absolute_path = __DIR__ . '/' . $relative_path;
    if (!file_exists($absolute_path)) {
        return [false, "Missing file: $relative_path"];
    }

    $text = file_get_contents($absolute_path);
    if ($text === false) {
        return [false, "Could not read file: $relative_path"];
    }

    $data = json_decode($text, true);
    if (!is_array($data)) {
        return [false, "Invalid JSON in file: $relative_path"];
    }

    return [$data, ''];
}

$missing = [];
foreach ($example_files as $label => $relative_path) {
    if (!file_exists(__DIR__ . '/' . $relative_path)) {
        $missing[] = $relative_path;
    }
}

if (!empty($missing)) {
    error_page(
        'One or more example dataset files are missing.',
        implode("\n", $missing)
    );
}

list($conservation_data, $cons_error) = load_json_file($example_files['conservation_summary']);
if ($conservation_data === false) {
    error_page('Could not load conservation summary JSON.', $cons_error);
}

list($motif_data, $motif_error) = load_json_file($example_files['motif_summary']);
if ($motif_data === false) {
    error_page('Could not load motif summary JSON.', $motif_error);
}

list($tree_data, $tree_error) = load_json_file($example_files['tree_summary']);
if ($tree_data === false) {
    error_page('Could not load tree summary JSON.', $tree_error);
}

$motif_hits = [];
foreach (($motif_data['results'] ?? []) as $seq_result) {
    $seq_id = $seq_result['sequence_id'] ?? '';
    foreach (($seq_result['hits'] ?? []) as $hit) {
        $motif_hits[] = [
            'sequence_id' => $seq_id,
            'motif' => $hit['motif'] ?? '',
            'start' => $hit['start'] ?? '',
            'end' => $hit['end'] ?? '',
            'length' => $hit['length'] ?? ''
        ];
    }
}

$tree_png_exists = file_exists(__DIR__ . '/' . $example_files['tree_png']);
$motif_raw_text = file_get_contents(__DIR__ . '/' . $example_files['motif_raw']);
if ($motif_raw_text === false) {
    $motif_raw_text = '';
}

require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Example Dataset</h1>
    <p>
        Explore the fixed reference dataset based on
        <strong>glucose-6-phosphatase proteins from Aves</strong>.
        This page uses pre-computed files and does not run any new retrieval or analysis.
    </p>
</div>

<div class="box">
    <h2>Example files</h2>
    <div class="link-row">
        <a href="<?php echo htmlspecialchars($example_files['raw_fasta']); ?>">Raw FASTA</a>
        <a href="<?php echo htmlspecialchars($example_files['aligned_fasta']); ?>">Aligned FASTA</a>
        <a href="<?php echo htmlspecialchars($example_files['identity_matrix']); ?>">Identity matrix</a>
        <a href="<?php echo htmlspecialchars($example_files['conservation_summary']); ?>">Conservation summary JSON</a>
        <a href="<?php echo htmlspecialchars($example_files['motif_summary']); ?>">Motif summary JSON</a>
        <a href="<?php echo htmlspecialchars($example_files['motif_raw']); ?>">Raw motif report</a>
        <a href="<?php echo htmlspecialchars($example_files['tree_newick']); ?>">Newick tree</a>
        <a href="<?php echo htmlspecialchars($example_files['tree_summary']); ?>">Tree summary JSON</a>
        <?php if ($tree_png_exists): ?>
            <a href="<?php echo htmlspecialchars($example_files['tree_png']); ?>">Tree image PNG</a>
        <?php endif; ?>
    </div>
</div>

<div class="box">
    <h2>Conservation summary</h2>
    <p><strong>Sequence count:</strong> <?php echo htmlspecialchars((string)($conservation_data['sequence_count'] ?? '')); ?></p>
    <p><strong>Alignment length:</strong> <?php echo htmlspecialchars((string)($conservation_data['alignment_length'] ?? '')); ?> aa</p>
    <p><strong>Average pairwise identity:</strong> <?php echo htmlspecialchars((string)($conservation_data['average_pairwise_identity'] ?? '')); ?>%</p>
    <p><strong>Minimum pairwise identity:</strong> <?php echo htmlspecialchars((string)($conservation_data['minimum_pairwise_identity'] ?? '')); ?>%</p>
    <p><strong>Maximum pairwise identity:</strong> <?php echo htmlspecialchars((string)($conservation_data['maximum_pairwise_identity'] ?? '')); ?>%</p>
    <p><strong>Average compared non-gap sites:</strong> <?php echo htmlspecialchars((string)($conservation_data['average_compared_sites'] ?? '')); ?></p>
</div>

<div class="box">
    <h2>Motif summary</h2>
    <p><strong>Total sequences scanned:</strong> <?php echo htmlspecialchars((string)($motif_data['sequence_count'] ?? '')); ?></p>
    <p><strong>Sequences with motif hits:</strong> <?php echo htmlspecialchars((string)($motif_data['sequences_with_hits'] ?? '')); ?></p>
    <p><strong>Total motif hits:</strong> <?php echo htmlspecialchars((string)($motif_data['total_hits'] ?? '')); ?></p>

    <?php if (empty($motif_hits)): ?>
        <p>No motif hit positions were found in this example dataset.</p>
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
                <?php foreach ($motif_hits as $row): ?>
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

    <?php if ($motif_raw_text !== ''): ?>
        <details>
            <summary>Show raw motif report</summary>
            <pre><?php echo htmlspecialchars($motif_raw_text); ?></pre>
        </details>
    <?php endif; ?>
</div>

<div class="box">
    <h2>Phylogenetic tree summary</h2>
    <p><strong>Total sequences in tree:</strong> <?php echo htmlspecialchars((string)($tree_data['sequence_count'] ?? '')); ?></p>
    <p><strong>Minimum pairwise distance:</strong> <?php echo htmlspecialchars((string)($tree_data['min_distance'] ?? '')); ?></p>
    <p><strong>Maximum pairwise distance:</strong> <?php echo htmlspecialchars((string)($tree_data['max_distance'] ?? '')); ?></p>
    <p><strong>Average pairwise distance:</strong> <?php echo htmlspecialchars((string)($tree_data['average_distance'] ?? '')); ?></p>

    <?php if ($tree_png_exists): ?>
        <img src="<?php echo htmlspecialchars($example_files['tree_png']); ?>" alt="Example dataset phylogenetic tree">
    <?php else: ?>
        <p>Tree PNG image is not available.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>