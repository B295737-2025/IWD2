<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement of Credits</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            line-height: 1.7;
            padding: 0 20px;
        }
        .box {
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 8px;
            background: #f9f9f9;
            margin-bottom: 20px;
        }
        h1, h2 {
            color: #1f4e79;
        }
        a {
            color: #1f4e79;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        ul {
            margin-top: 8px;
        }
        li {
            margin-bottom: 10px;
        }
        code {
            background: #f3f3f3;
            padding: 2px 4px;
        }
        .small {
            color: #555;
            font-size: 0.95em;
        }
    </style>
</head>
<body>
    <h1>Statement of Credits</h1>

    <div class="box">
        <p>
            This page documents the external code sources, documentation sources, and AI assistance
            used during the development of this website.
        </p>
        <p class="small">
            All final code was reviewed, edited, and tested before being incorporated into the project.
        </p>
    </div>

    <div class="box">
        <h2>1. External code and documentation sources</h2>
        <ul>
            <li>
                <strong>Biopython Bio.Entrez documentation</strong><br>
                <a href="https://biopython.org/docs/1.80/api/Bio.Entrez.html">
                    https://biopython.org/docs/1.80/api/Bio.Entrez.html
                </a><br>
                Used as the main reference for implementing sequence retrieval from NCBI in
                <code>lib/fetch_sequences.py</code>, especially the use of Entrez search/fetch logic,
                handle reading, and general error-handling behaviour.
            </li>

            <li>
                <strong>NCBI Entrez Programming Utilities documentation</strong><br>
                <a href="https://www.ncbi.nlm.nih.gov/books/NBK25499/">
                    https://www.ncbi.nlm.nih.gov/books/NBK25499/
                </a><br>
                Used as background reference for how NCBI E-utilities work and for understanding the
                search/fetch workflow behind protein sequence retrieval.
            </li>

            <li>
                <strong>Biopython SeqIO documentation</strong><br>
                <a href="https://biopython.org/docs/1.82/api/Bio.SeqIO.html">
                    https://biopython.org/docs/1.82/api/Bio.SeqIO.html
                </a><br>
                Used as a reference for parsing FASTA records, reading downloaded protein sequences,
                and generating short preview summaries shown in the website.
            </li>

            <li>
                <strong>EMBOSS patmatmotifs manual</strong><br>
                <a href="https://emboss.bioinformatics.nl/cgi-bin/emboss/help/patmatmotifs">
                    https://emboss.bioinformatics.nl/cgi-bin/emboss/help/patmatmotifs
                </a><br>
                Used as the main reference for PROSITE motif scanning, command-line parameters, and
                interpretation of motif report output in the motif analysis component.
            </li>

            <li>
                <strong>Biopython TreeConstruction documentation</strong><br>
                <a href="https://biopython.org/docs/1.81/api/Bio.Phylo.TreeConstruction.html">
                    https://biopython.org/docs/1.81/api/Bio.Phylo.TreeConstruction.html
                </a><br>
                Used as the reference for calculating pairwise distances from a multiple sequence
                alignment and building a neighbour-joining tree in <code>lib/tree_analysis.py</code>.
            </li>

            <li>
                <strong>Biopython Phylo documentation</strong><br>
                <a href="https://biopython.org/docs/1.79/api/Bio.Phylo.html">
                    https://biopython.org/docs/1.79/api/Bio.Phylo.html
                </a><br>
                Used as the reference for writing Newick tree files and rendering the tree output for
                the additional analysis page.
            </li>

            <li>
                <strong>PHP PDO documentation</strong><br>
                <a href="https://www.php.net/manual/en/pdo.connections.php">
                    https://www.php.net/manual/en/pdo.connections.php
                </a><br>
                Used as the main reference for database connection design and SQL interaction in PHP,
                including the use of PDO for MySQL access in <code>lib/db_connect.php</code>,
                <code>fetch.php</code>, and <code>history.php</code>.
            </li>
        </ul>
    </div>

    <div class="box">
        <h2>2. AI tools used</h2>
        <ul>
            <li>
                <strong>ChatGPT (OpenAI)</strong><br>
                <a href="https://openai.com/chatgpt/">
                    https://openai.com/chatgpt/
                </a><br>
                ChatGPT was used in a detailed and iterative way during development. Its use included:
                <ul>
                    <li>helping break the project into smaller development steps;</li>
                    <li>helping design the overall page structure and workflow between <code>index.php</code>, <code>fetch.php</code>, <code>analyze.php</code>, <code>motif.php</code>, <code>tree.php</code>, and <code>history.php</code>;</li>
                    <li>suggesting draft PHP and Python code for sequence retrieval, JSON handling, motif parsing, tree generation, and history filtering;</li>
                    <li>helping debug practical issues such as path validation, JSON parsing failures, Matplotlib environment warnings, and history page display logic;</li>
                    <li>helping improve wording on user-facing pages, such as status messages, interpretation text, and retention notices;</li>
                    <li>helping draft and refine this Statement of Credits page in a clearer and more specific format.</li>
                </ul>
                The generated suggestions were not copied blindly: they were checked, edited, tested,
                and revised during implementation.
            </li>
        </ul>
    </div>

    <div class="box">
        <h2>3. Notes on authorship</h2>
        <p>
            The overall website was assembled, tested, and adapted by the site author. External
            documentation and AI assistance were used as development support, but the final selection,
            integration, debugging, and deployment decisions were made during the implementation of this
            website.
        </p>
    </div>

    <p><a href="index.php">Back to home</a></p>
</body>
</html>