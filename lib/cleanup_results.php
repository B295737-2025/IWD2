<?php
// IMPORTANT:
// This cleanup function is intentionally NON-RECURSIVE.
//
// It is designed to delete only ordinary time-limited files directly inside
// the main /results directory.
//
// Do NOT change this function to recursive deletion unless you also add an
// explicit exclusion for /results/examples/.
//
// The /results/examples/ directory stores the fixed coursework example dataset
// (Aves glucose-6-phosphatase) and should be kept as a permanent, pre-processed
// example for users. It must not be removed by the normal 60-day cleanup logic.
function cleanup_old_results($results_dir, $days = 60) {
    $deleted = 0;
    $errors = 0;

    if (!is_dir($results_dir)) {
        return ['deleted' => 0, 'errors' => 1];
    }

    $cutoff = time() - ($days * 24 * 60 * 60);

    $files = glob($results_dir . '/*');
    if ($files === false) {
        return ['deleted' => 0, 'errors' => 1];
    }

    foreach ($files as $file) {
        if (!is_file($file)) {
            continue;
        }

        $mtime = filemtime($file);
        if ($mtime === false) {
            $errors++;
            continue;
        }

        if ($mtime < $cutoff) {
            if (@unlink($file)) {
                $deleted++;
            } else {
                $errors++;
            }
        }
    }

    return ['deleted' => $deleted, 'errors' => $errors];
}
?>