<?php
$page_title = 'Phylogenetic Tree Running';
$active_page = '';

$input_file = $_GET['file'] ?? '';
$input_file = trim($input_file);

if ($input_file === '') {
    die('No aligned FASTA file was provided.');
}

require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Phylogenetic tree analysis is running</h1>
    <p>
        The aligned sequence set is being processed to generate a neighbour-joining
        tree. This page will automatically continue when tree analysis is finished.
    </p>
</div>

<div class="box">
    <p><strong>Aligned FASTA:</strong> <?php echo htmlspecialchars($input_file); ?></p>

    <div class="spinner"></div>

    <p class="status" id="statusText">Preparing tree analysis...</p>
    <p class="small">
        Larger alignments may take longer to process and render.
    </p>
</div>

<div class="box">
    <h2>Next step</h2>
    <p class="small">
        Once tree analysis is complete, this page will redirect to the
        phylogenetic tree results page for the same alignment.
    </p>
</div>

<script>
    const fileParam = <?php echo json_encode($input_file); ?>;
    const statusText = document.getElementById('statusText');

    statusText.textContent = 'Building phylogenetic tree...';

    fetch('tree_run.php?file=' + encodeURIComponent(fileParam))
        .then(response => response.json())
        .then(data => {
            if (data.status === 'complete' && data.redirect) {
                statusText.textContent = 'Tree analysis finished. Redirecting...';
                window.location.href = data.redirect;
            } else {
                statusText.textContent = 'Unexpected tree response.';
            }
        })
        .catch(error => {
            statusText.textContent = 'The tree request failed. Please try again.';
        });
</script>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>