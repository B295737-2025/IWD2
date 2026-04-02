<?php
$page_title = 'Sequence Retrieval Running';
$active_page = '';

$user_name = trim($_POST['user_name'] ?? 'guest');
$family = trim($_POST['family'] ?? '');
$taxon  = trim($_POST['taxon'] ?? '');
$max_sequences = trim($_POST['max_sequences'] ?? '50');

if ($user_name === '') {
    $user_name = 'guest';
}
if ($max_sequences === '' || !ctype_digit($max_sequences)) {
    $max_sequences = '50';
}

require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Sequence retrieval is running</h1>
    <p>
        The query has been submitted to NCBI protein retrieval. This page will
        automatically continue when the sequence set is ready.
    </p>
</div>

<div class="box">
    <p><strong>User Name:</strong> <?php echo htmlspecialchars($user_name); ?></p>
    <p><strong>Protein family:</strong> <?php echo htmlspecialchars($family); ?></p>
    <p><strong>Taxonomic group:</strong> <?php echo htmlspecialchars($taxon); ?></p>
    <p><strong>Maximum sequences requested:</strong> <?php echo htmlspecialchars($max_sequences); ?></p>

    <div class="spinner"></div>
    <p class="status" id="statusText">Preparing NCBI retrieval...</p>
    <p class="small">
        This page will automatically continue when sequence retrieval is finished.
    </p>
</div>

<div class="box">
    <h2>Next step</h2>
    <p class="small">
        Once the sequence retrieval is complete, you will be redirected to the
        fetch results page, where you can launch conservation and motif analyses.
    </p>
</div>

<form id="fetchForm" style="display:none;">
    <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($user_name); ?>">
    <input type="hidden" name="family" value="<?php echo htmlspecialchars($family); ?>">
    <input type="hidden" name="taxon" value="<?php echo htmlspecialchars($taxon); ?>">
    <input type="hidden" name="max_sequences" value="<?php echo htmlspecialchars($max_sequences); ?>">
</form>

<script>
    const form = document.getElementById('fetchForm');
    const statusText = document.getElementById('statusText');

    statusText.textContent = 'Retrieving sequences from NCBI...';

    fetch('fetch_run.php', {
        method: 'POST',
        body: new FormData(form)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'complete' && data.redirect) {
            statusText.textContent = 'Sequence retrieval finished. Redirecting...';
            window.location.href = data.redirect;
        } else {
            statusText.textContent = 'Unexpected fetch response.';
        }
    })
    .catch(error => {
        statusText.textContent = 'The fetch request failed. Please try again.';
    });
</script>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>