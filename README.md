# Protein Sequence Analysis Website

This project is a bioinformatics web application developed for the IWD2 website design assignment. It allows a user to retrieve protein sequences from NCBI for a chosen protein family and taxonomic group, and then explore sequence conservation, PROSITE motif matches, and phylogenetic relationships through a web interface.

## Main features

- Retrieve protein sequences from NCBI using a protein family and taxonomic group
- Save raw FASTA files in the server-side `results/` directory
- Run conservation analysis using multiple sequence alignment and pairwise identity summary
- Generate a conservation profile TSV file and conservation profile plot from the aligned FASTA
- Run PROSITE motif scanning using EMBOSS `patmatmotifs`
- Generate motif frequency statistics as JSON and a motif frequency plot
- Run phylogenetic tree analysis using a neighbour-joining tree built from the aligned FASTA
- Provide a fixed pre-computed example dataset for **glucose-6-phosphatase proteins from Aves**
- Store query history in MySQL and allow users to revisit previous results
- Provide a Help page for biological users
- Provide an About page for a developer-oriented overview
- Provide a Statement of Credits page documenting external sources and AI use

## Website entry points

- `index.php` - homepage and input form
- `fetch_status.php` / `fetch.php` - sequence retrieval waiting page and result page
- `analysis_status.php` / `analyze.php` - conservation waiting page and result page
- `motif_status.php` / `motif.php` - motif waiting page and result page
- `tree_status.php` / `tree.php` - phylogenetic tree waiting page and result page
- `history.php` - history and filtering page
- `example.php` - fixed example dataset page
- `help.php` - help and biological context page
- `about.php` - developer-oriented overview page
- `statement_of_credits.php` - credits and AI usage page

## Project structure

- `index.php` - homepage
- `fetch_status.php`, `fetch_run.php`, `fetch.php` - sequence retrieval workflow
- `analysis_status.php`, `run_analysis.php`, `analysis_check.php`, `analyze.php` - conservation workflow
- `motif_status.php`, `motif_run.php`, `motif.php` - motif workflow
- `tree_status.php`, `tree_run.php`, `tree.php` - phylogenetic tree workflow
- `history.php` - query history
- `example.php` - example dataset page
- `help.php` - help page
- `about.php` - developer-oriented overview page
- `statement_of_credits.php` - credits page
- `lib/` - PHP and Python helper scripts
- `lib/motif_stats.py` - motif frequency statistics and plot generation
- `css/style.css` - global stylesheet
- `data/schema.sql` - database schema
- `results/` - generated analysis outputs
- `results/examples/` - fixed pre-computed example dataset

## Dependencies

This project relies on the following software already available on the `bioinfmsc8` server:

- PHP with PDO and `pdo_mysql`
- MySQL
- Python 3
- Biopython
- Clustal Omega (`clustalo`)
- EMBOSS `patmatmotifs`
- Matplotlib

## Database

The website uses a MySQL database called `s2809725_ica`.

Main table:

- `history`
  - `id`
  - `user`
  - `family`
  - `taxon`
  - `result_link`
  - `created_at`

The schema is provided in:

- `data/schema.sql`

## Example dataset

The fixed example dataset is based on:

- **glucose-6-phosphatase proteins from Aves**

The pre-computed files are stored in:

- `results/examples/`

This example dataset is displayed through:

- `example.php`

The example dataset includes fixed output files for:

- conservation summary
- conservation profile TSV and PNG
- motif summary
- motif statistics JSON and PNG
- phylogenetic tree summary, Newick file, and tree PNG

Unlike normal results, the example dataset is not regenerated dynamically.

## Result retention

Normal result files in `results/` are retained on the server for 60 days and may be deleted by the cleanup script.

The fixed example dataset in `results/examples/` is stored separately and is not intended to be removed by routine cleanup.

## Configuration and sensitive information

Database credentials and Entrez credentials are not stored directly inside the public project files.

They are loaded from a private configuration file outside `public_html`.

For this deployment, the private configuration file is expected at:

- `/home/s2809725/ica_private/config.php`

Its structure is:

```php
<?php
return [
    'db_user' => '...',
    'db_pass' => '...',
    'entrez_email' => '...',
    'entrez_api_key' => '...',
];
```

This file should not be uploaded as part of the public website code.

## Repository

Project repository:

https://github.com/B295737-2025/IWD2

## Notes on implementation

- SQL interactions are handled through PHP PDO, not Python MySQL modules
- Sequence retrieval is performed with Biopython Bio.Entrez
- Conservation analysis is based on aligned FASTA files and pairwise identity summary, and a conservation profile plot
- Motif scanning is performed sequence-by-sequence using EMBOSS `patmatmotifs`
- Motif results are additionally summarised into motif frequency statistics and a bar plot
- The extra biological analysis implemented in this project is phylogenetic tree construction
- The fixed example dataset is stored in `results/examples/` and is handled separately from normal user-generated results

## Author note

This website was developed and refined iteratively, with external documentation and AI assistance used as development support. Final testing, debugging, integration, and deployment decisions were made during implementation.
