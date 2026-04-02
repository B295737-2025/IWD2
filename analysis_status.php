<?php
$page_title = 'Conservation Analysis Running';
$active_page = '';

$input_file = $_GET['file'] ?? '';
$input_file = trim($input_file);

if ($input_file === '') {
    die('No input file was provided.');
}

require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Conservation analysis is running</h1>
    <p>
        The selected sequence set is being aligned and processed for conservation
        analysis. This page will automatically continue when the result files are ready.
    </p>
</div>

<div class="box">
    <p><strong>Input FASTA:</strong> <?php echo htmlspecialchars($input_file); ?></p>

    <div class="spinner"></div>

    <p class="status" id="statusText">Starting analysis...</p>

    <p class="small">
        This may take several minutes depending on the size of the sequence set.
    </p>
    <p class="small">
        Please keep this page open while the analysis is running.
    </p>
</div>

<div class="box">
    <h2>Next step</h2>
    <p class="small">
        Once the conservation analysis is complete, this page will automatically
        redirect to the conservation results page.
    </p>
</div>

<script>
    const fileParam = <?php echo json_encode($input_file); ?>;
    const runUrl = 'run_analysis.php?file=' + encodeURIComponent(fileParam);
    const checkUrl = 'analysis_check.php?file=' + encodeURIComponent(fileParam);
    const resultUrl = 'analyze.php?file=' + encodeURIComponent(fileParam);
    const statusText = document.getElementById('statusText');

    let started = false;

    function startAnalysis() {
        if (started) return;
        started = true;

        statusText.textContent = 'Analysis started. Waiting for server response...';

        fetch(runUrl)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'running') {
                    statusText.textContent = 'Analysis is already running...';
                } else if (data.status === 'complete') {
                    statusText.textContent = 'Analysis finished. Redirecting...';
                    window.location.href = resultUrl;
                } else if (data.status === 'error') {
                    statusText.textContent = 'Analysis failed: ' + data.message;
                } else {
                    statusText.textContent = 'Analysis is running...';
                }
            })
            .catch(error => {
                statusText.textContent = 'Unable to start analysis request cleanly. Still checking results...';
            });
    }

    function checkStatus() {
        fetch(checkUrl)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'complete') {
                    statusText.textContent = 'Analysis finished. Redirecting...';
                    window.location.href = resultUrl;
                } else if (data.status === 'running') {
                    statusText.textContent = 'Analysis is still running...';
                } else if (data.status === 'pending') {
                    statusText.textContent = 'Analysis has not finished yet...';
                } else if (data.status === 'error') {
                    statusText.textContent = 'Status check error: ' + data.message;
                }
            })
            .catch(error => {
                statusText.textContent = 'Status check failed temporarily. Retrying...';
            });
    }

    startAnalysis();
    setInterval(checkStatus, 10000);
</script>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>