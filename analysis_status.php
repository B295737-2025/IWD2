<?php
$input_file = $_GET['file'] ?? '';
$input_file = trim($input_file);

if ($input_file === '') {
    die('No input file was provided.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analysis Running</title>
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
            padding: 24px;
            border-radius: 8px;
            background: #f9f9f9;
            margin-bottom: 20px;
        }
        h1 {
            color: #1f4e79;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 5px solid #ddd;
            border-top: 5px solid #1f4e79;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px 0;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .status {
            font-weight: bold;
            color: #1f4e79;
        }
        a {
            color: #1f4e79;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .small {
            color: #555;
            font-size: 0.95em;
        }
    </style>
</head>
<body>
    <h1>Conservation analysis is running</h1>

    <div class="box">
        <p><strong>Input FASTA:</strong> <?php echo htmlspecialchars($input_file); ?></p>
        <div class="spinner"></div>
        <p class="status" id="statusText">Starting analysis...</p>
        <p class="small">
            This may take several minutes.
        </p>
        <p class="small">
            Please keep this page open while the analysis is running.
        </p>
        <p class="small">
            This page will automatically check for results and redirect when the analysis is finished.
        </p>
    </div>

    <p><a href="index.php">Back to home</a></p>
    <p><a href="history.php">View history</a></p>

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
</body>
</html>