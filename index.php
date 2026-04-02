<?php
$page_title = 'Protein Sequence Analysis';
$active_page = 'home';
require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Protein Sequence Analysis</h1>
    <p>
        Retrieve protein sequences from a selected taxonomic group and explore
        conservation, PROSITE motif hits, and phylogenetic relationships in a
        single workflow.
    </p>
</div>

<div class="box">
    <h2>Start a new query</h2>
    <p>Enter a protein family and a taxonomic group to retrieve protein sequences.</p>

<form action="fetch_status.php" method="post" class="form-grid">
    <label for="family">Protein family:</label>
    <input id="family" name="family" type="text" required>

    <label for="taxon">Taxonomic group:</label>
    <input id="taxon" name="taxon" type="text" required>
    
    <label for="max_sequences">Maximum sequences:</label>
    <input
        id="max_sequences"
        name="max_sequences"
        type="number"
        placeholder="50"
        min="1"
        max="1000"
        step="1"
    >

    <label for="user_name">User name (optional):</label>
    <input
        id="user_name"
        name="user_name"
        type="text"
        placeholder="guest"
        maxlength="50"
    >

    <div></div>
    <div class="form-actions">
        <input type="submit" value="Fetch sequences">
        <p class="small form-hint">
            You can request up to <strong>1000</strong> protein sequences from NCBI.
            For faster downstream analyses, values above <strong>100</strong> are not recommended.
        </p>
    </div>
</form>

    <p class="note">
        The user name is only used as a history label to help filter previous queries.
        It is not a login or authentication system.
    </p>
</div>

<div class="box">
    <h2>Example dataset</h2>
    <p>
        Explore a pre-computed example dataset based on
        <strong>glucose-6-phosphatase proteins from Aves</strong>.
    </p>
    <div class="link-row">
        <a href="example.php">View example dataset</a>
        <a href="history.php">Browse query history</a>
        <a href="statement_of_credits.php">Statement of Credits</a>
    </div>
</div>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>