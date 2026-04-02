<?php
if (!isset($page_title)) {
    $page_title = 'Protein Sequence Analysis';
}
if (!isset($active_page)) {
    $active_page = '';
}

function nav_class($key, $active_page) {
    return $key === $active_page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="site-header">
    <div class="site-header-inner">
        <a class="site-brand" href="index.php">Protein Sequence Analysis</a>
        <nav class="site-nav">
            <a class="<?php echo nav_class('home', $active_page); ?>" href="index.php">Home</a>
            <a class="<?php echo nav_class('history', $active_page); ?>" href="history.php">History</a>
            <a class="<?php echo nav_class('example', $active_page); ?>" href="example.php">Example Dataset</a>
            <a class="<?php echo nav_class('help', $active_page); ?>" href="help.php">Help</a>
            <a class="<?php echo nav_class('about', $active_page); ?>" href="about.php">About</a>
            <a class="<?php echo nav_class('credits', $active_page); ?>" href="statement_of_credits.php">Credits</a>
        </nav>
    </div>
</header>
<main class="container">