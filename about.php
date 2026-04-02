<?php
$page_title = 'About';
$active_page = 'about';
require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>About This Project</h1>
    <p>
        This page gives a developer-oriented overview of how the website is structured,
        which technologies are used, and how the main analysis workflow is implemented.
    </p>
</div>

<div class="box">
    <h2>Project overview</h2>
    <p>
        This website was developed for the IWD2 bioinformatics web design assignment.
        It allows a user to retrieve protein sequences from NCBI for a chosen protein family
        and taxonomic group, and then explore conservation, PROSITE motif hits, and
        phylogenetic relationships through a multi-step web workflow.
    </p>
</div>

<div class="box">
    <h2>Implementation approach</h2>
    <ul>
        <li><strong>Frontend:</strong> PHP page templates with a shared global stylesheet.</li>
        <li><strong>Backend workflow:</strong> PHP coordinates requests and launches Python scripts for sequence retrieval and downstream analyses.</li>
        <li><strong>Sequence retrieval:</strong> Biopython <code>Bio.Entrez</code> is used to query and download protein FASTA records from NCBI.</li>
        <li><strong>Conservation analysis:</strong> Clustal Omega is used for multiple sequence alignment, followed by pairwise identity summary and a conservation profile plot.</li>
        <li><strong>Motif analysis:</strong> EMBOSS <code>patmatmotifs</code> is run sequence-by-sequence, and the outputs are summarised into JSON, tables, and a motif frequency plot.</li>
        <li><strong>Extra analysis:</strong> a neighbour-joining phylogenetic tree is built from the aligned FASTA using Biopython.</li>
        <li><strong>Database:</strong> MySQL stores query history, and all SQL interactions are handled in PHP through PDO.</li>
    </ul>
</div>

<div class="box">
    <h2>Workflow structure</h2>
    <ol>
        <li>The user submits a protein family, taxonomic group, optional user label, and maximum sequence count.</li>
        <li>The website retrieves protein sequences from NCBI and saves a raw FASTA file in the server-side results directory.</li>
        <li>The user can run conservation analysis, motif scanning, and phylogenetic tree analysis as separate downstream steps.</li>
        <li>Each analysis writes result files to the <code>results/</code> directory and exposes them through dedicated result pages.</li>
        <li>Completed queries are recorded in the history database so they can be revisited later while the files still exist on the server.</li>
    </ol>
</div>

<div class="box">
    <h2>Example dataset and result storage</h2>
    <p>
        The website includes a fixed example dataset based on
        <strong>glucose-6-phosphatase proteins from Aves</strong>.
        Unlike normal user-generated results, this example dataset is stored in
        <code>results/examples/</code> and is handled separately so that it is not removed by the
        normal 60-day cleanup process.
    </p>
    <p>
        Normal user-generated outputs are stored in <code>results/</code>, including raw FASTA files,
        aligned FASTA files, summary JSON files, plots, and temporary job JSON files used by the
        waiting-page workflow.
    </p>
</div>

<div class="box">
    <h2>Configuration and security</h2>
    <p>
        Sensitive information such as the database credentials and Entrez configuration is not stored
        directly inside the public website directory. Instead, the public PHP scripts load these values
        from a private configuration file stored outside <code>public_html</code>.
    </p>
    <p>
        Input file paths are validated before use, and database access is handled with PDO prepared
        statements for safer query execution.
    </p>
</div>

<div class="box">
    <h2>GitHub repository</h2>
    <p>
        If a public repository is available, the project source code can be accessed here:
    </p>
    <p>
        <a href="https://github.com/B295737-2025/IWD2" target="_blank" rel="noopener noreferrer">https://github.com/B295737-2025/IWD2</a>
    </p>
    <p class="small">
        Replace the placeholder above with your real GitHub repository URL before final submission.
    </p>
</div>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>
