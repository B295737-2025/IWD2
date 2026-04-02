<?php
$page_title = 'Help';
$active_page = 'help';
require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Help</h1>
    <p>
        This page explains what the website does, how to use it, and how to
        interpret the main analysis outputs from a biological point of view.
    </p>
</div>

<div class="box">
    <h2>What this website does</h2>
    <p>
        This website retrieves protein sequences from NCBI for a selected
        protein family and taxonomic group, and then supports three main
        downstream analyses:
    </p>
    <ul>
        <li><strong>Conservation analysis</strong> - compares the retrieved sequences after alignment.</li>
        <li><strong>Motif scan</strong> - identifies PROSITE motif matches in the sequence set.</li>
        <li><strong>Phylogenetic tree analysis</strong> - shows how closely related the aligned proteins are.</li>
    </ul>
</div>

<div class="box">
    <h2>How to start a query</h2>
    <p>
        Go to the <strong>Home</strong> page and enter:
    </p>
    <ul>
        <li><strong>Protein family</strong> - for example, <code>glucose-6-phosphatase</code></li>
        <li><strong>Taxonomic group</strong> - for example, <code>Aves</code></li>
        <li><strong>Maximum sequences</strong> - the number of NCBI protein records to retrieve</li>
        <li><strong>User name (optional)</strong> - a label used only for history filtering</li>
    </ul>
    <p class="small">
        The user name is not an account or login. It is only a label attached to saved history entries.
    </p>
</div>

<div class="box">
    <h2>What each analysis means</h2>

    <h3>1. Conservation analysis</h3>
    <p>
        This step aligns the protein sequences and calculates pairwise identity
        values. It is useful for asking whether the selected proteins are highly
        similar, moderately conserved, or more divergent.
    </p>
    <p>
        The main outputs include sequence count, alignment length, and average /
        minimum / maximum pairwise identity.
    </p>

    <h3>2. Motif scan</h3>
    <p>
        This step scans the protein sequences against PROSITE motifs using
        EMBOSS <code>patmatmotifs</code>. It helps identify known sequence
        patterns that may be associated with conserved functional regions.
    </p>
    <p>
        The output lists which sequences contain motif hits, the motif name, and
        the start / end positions of each hit.
    </p>

    <h3>3. Phylogenetic tree analysis</h3>
    <p>
        This step uses the aligned FASTA file to build a neighbour-joining tree.
        It provides a simple visual summary of how closely related the retrieved
        proteins are within the chosen dataset.
    </p>
</div>

<div class="box">
    <h2>Suggested workflow</h2>
    <ol>
        <li>Retrieve sequences from NCBI</li>
        <li>Run conservation analysis to generate the alignment</li>
        <li>Run motif scan to inspect PROSITE matches</li>
        <li>Run phylogenetic tree analysis on the aligned FASTA file</li>
    </ol>
    <p class="small">
        Tree analysis depends on the aligned FASTA file generated during conservation analysis.
    </p>
</div>

<div class="box">
    <h2>Example dataset</h2>
    <p>
        The website includes a fixed example dataset based on
        <strong>glucose-6-phosphatase proteins from Aves</strong>.
        This is useful if you want to explore the website workflow before
        submitting your own query.
    </p>
    <p>
        Open the <strong>Example Dataset</strong> page from the navigation bar
        to inspect the pre-computed files and example outputs.
    </p>
</div>

<div class="box">
    <h2>History and result retention</h2>
    <p>
        Recent queries are saved in the <strong>History</strong> page so that
        you can revisit previously generated data.
    </p>
    <p>
        Normal result files are retained on the server for 60 days. Older files
        may disappear from the results directory even if the history record
        still remains in the database.
    </p>
</div>

<div class="box">
    <h2>Common questions</h2>
    <p><strong>Why did no sequences appear?</strong><br>
    The query may be too specific, may use a name not present in NCBI protein
    records, or may not match the selected taxonomic group.</p>

    <p><strong>Why is tree analysis unavailable?</strong><br>
    Tree analysis requires an aligned FASTA file, which is generated during
    conservation analysis. Run conservation analysis first.</p>

    <p><strong>Why can large jobs be slower?</strong><br>
    Larger sequence sets take longer to align, scan for motifs, and use for tree construction.</p>
</div>

<div class="box">
    <h2>Related pages</h2>
    <div class="link-row">
        <a href="index.php">Home</a>
        <a href="example.php">Example Dataset</a>
        <a href="history.php">History</a>
        <a href="statement_of_credits.php">Statement of Credits</a>
    </div>
</div>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>