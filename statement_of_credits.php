<?php
$page_title = 'Statement of Credits';
$active_page = 'credits';
require_once __DIR__ . '/lib/site_header.php';
?>

<div class="hero">
    <h1>Statement of Credits</h1>
    <p>
        This page documents the external sources, documentation, software tools,
        and AI assistance used during the design, implementation, debugging,
        and refinement of this website.
    </p>
</div>

<div class="box">
    <p class="small">
        All final code and wording included in the website were reviewed, edited,
        tested, and integrated before submission.
    </p>
</div>

<div class="box">
    <h2>1. External documentation and software references</h2>
    <ul>
        <li>
            <strong>Biopython Bio.Entrez documentation</strong><br>
            <a href="https://biopython.org/docs/1.80/api/Bio.Entrez.html" target="_blank" rel="noopener noreferrer">
                https://biopython.org/docs/1.80/api/Bio.Entrez.html
            </a><br>
            Used as the main reference for implementing protein sequence retrieval
            from NCBI in <code>lib/fetch_sequences.py</code>, especially Entrez
            search/fetch usage, return handling, and retry logic.
        </li>

        <li>
            <strong>NCBI Entrez Programming Utilities documentation</strong><br>
            <a href="https://www.ncbi.nlm.nih.gov/books/NBK25499/" target="_blank" rel="noopener noreferrer">
                https://www.ncbi.nlm.nih.gov/books/NBK25499/
            </a><br>
            Used as background reference for how NCBI E-utilities work and how
            query terms and sequence retrieval should be structured.
        </li>

        <li>
            <strong>Biopython SeqIO documentation</strong><br>
            <a href="https://biopython.org/docs/1.82/api/Bio.SeqIO.html" target="_blank" rel="noopener noreferrer">
                https://biopython.org/docs/1.82/api/Bio.SeqIO.html
            </a><br>
            Used as a reference for parsing FASTA records, extracting sequence
            identifiers, generating preview information, and reading sequence files.
        </li>

        <li>
            <strong>Clustal Omega</strong><br>
            Used as the alignment tool for conservation analysis through the server-side
            <code>clustalo</code> executable, called from the PHP/Python workflow.
        </li>

        <li>
            <strong>EMBOSS patmatmotifs manual</strong><br>
            <a href="https://emboss.bioinformatics.nl/cgi-bin/emboss/help/patmatmotifs" target="_blank" rel="noopener noreferrer">
                https://emboss.bioinformatics.nl/cgi-bin/emboss/help/patmatmotifs
            </a><br>
            Used as the main reference for PROSITE motif scanning, command-line options,
            and interpretation of the motif report output used in
            <code>lib/motif_scan.py</code>.
        </li>

        <li>
            <strong>Biopython TreeConstruction documentation</strong><br>
            <a href="https://biopython.org/docs/1.81/api/Bio.Phylo.TreeConstruction.html" target="_blank" rel="noopener noreferrer">
                https://biopython.org/docs/1.81/api/Bio.Phylo.TreeConstruction.html
            </a><br>
            Used as the reference for distance calculation from aligned FASTA files
            and neighbour-joining tree construction in <code>lib/tree_analysis.py</code>.
        </li>

        <li>
            <strong>Biopython Phylo documentation</strong><br>
            <a href="https://biopython.org/docs/1.79/api/Bio.Phylo.html" target="_blank" rel="noopener noreferrer">
                https://biopython.org/docs/1.79/api/Bio.Phylo.html
            </a><br>
            Used for writing Newick tree files and rendering tree graphics for
            the phylogenetic analysis page.
        </li>

        <li>
            <strong>PHP PDO documentation</strong><br>
            <a href="https://www.php.net/manual/en/pdo.connections.php" target="_blank" rel="noopener noreferrer">
                https://www.php.net/manual/en/pdo.connections.php
            </a><br>
            Used as the main reference for database connection handling and SQL
            interaction through PDO in <code>lib/db_connect.php</code>,
            <code>fetch_run.php</code>, and <code>history.php</code>.
        </li>

        <li>
            <strong>Matplotlib documentation</strong><br>
            <a href="https://matplotlib.org/stable/" target="_blank" rel="noopener noreferrer">
                https://matplotlib.org/stable/
            </a><br>
            Used as the plotting reference for the conservation profile image,
            motif frequency chart, and phylogenetic tree PNG output.
        </li>

        <li>
            <strong>Git and GitHub</strong><br>
            Used for version control and source code backup during development and
            final refinement of the project.
        </li>
    </ul>
</div>

<div class="box">
    <h2>2. AI tools used</h2>
    <ul>
        <li>
            <strong>ChatGPT (OpenAI)</strong><br>
            <a href="https://openai.com/chatgpt/" target="_blank" rel="noopener noreferrer">
                https://openai.com/chatgpt/
            </a><br>
            ChatGPT was used as a development support tool throughout the project.
            Its use included:
            <ul>
                <li>breaking the project into smaller implementation steps and helping plan the workflow;</li>
                <li>helping debug practical problems such as path validation, cached result logic, JSON parsing failures, page linking issues, and example-dataset path handling;</li>
                <li>helping refine user-facing wording on the homepage, help page, result pages, status pages, and credits page;</li>
                <li>helping design and integrate later improvements such as the conservation profile plot, motif frequency plot, About page, GitHub link integration, and final QA checklist;</li>
                <li>helping review project structure and identify inconsistencies between displayed behaviour and backend logic.</li>
            </ul>
            AI-generated suggestions were not copied blindly. They were checked,
            edited, tested on the server, and revised before being kept in the final site.
        </li>
    </ul>
</div>

<div class="box">
    <h2>3. Project repository</h2>
    <p>
        The source code repository for this project is available at:
    </p>
    <p>
        <a href="https://github.com/B295737-2025/IWD2" target="_blank" rel="noopener noreferrer">
            https://github.com/B295737-2025/IWD2
        </a>
    </p>
</div>

<div class="box">
    <h2>4. Notes on authorship</h2>
    <p>
        The overall website was assembled, adapted, tested, and deployed by the site author.
        External documentation and AI assistance were used as development support,
        but the final selection of features, implementation decisions, debugging,
        file organisation, and deployment choices were made during the completion
        of this project.
    </p>
</div>

<?php require_once __DIR__ . '/lib/site_footer.php'; ?>