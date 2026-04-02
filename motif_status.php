<?php
$page_title = 'Motif Scan Running';
$active_page = '';

$input_file = $_GET['file'] ?? '';
$input_file = trim($input_file);

if ($input_file === '') {
    die('No input FASTA file was provided.');
}

require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Motif scan is running</h1>
    <p>
        The selected sequence set is being scanned against PROSITE motifs.
        This page will automatically continue when motif analysis is finished.
    </p>
</div>

<div class="box">
    <p><strong>Input FASTA:</strong> <?php echo htmlspecialchars($input_file); ?></p>

    <div class="spinner"></div>

    <p class="status" id="statusText">Preparing motif scan...</p>
    <p class="small">
        This may take longer for larger sequence sets because each sequence is
        scanned individually.
    </p>
</div>

<div class="box">
    <h2>Next step</h2>
    <p class="small">
        Once motif analysis is complete, this page will redirect to the motif
        results page for the same sequence set.
    </p>
</div>

<script>
    const fileParam = <?php echo json_encode($input_file); ?>;
    const statusText = document.getElementById('statusText');

    statusText.textContent = 'Running PROSITE motif scan...';

    fetch('motif_run.php?file=' + encodeURIComponent(fileParam))
        .then(response => response.json())
        .then(data => {
            if (data.status === 'complete' && data.redirect) {
                statusText.textContent = 'Motif scan finished. Redirecting...';
                window.location.href = data.redirect;
            } else {
                statusText.textContent = 'Unexpected motif response.';
            }
        })
        .catch(error => {
            statusText.textContent = 'The motif request failed. Please try again.';
        });
</script>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>