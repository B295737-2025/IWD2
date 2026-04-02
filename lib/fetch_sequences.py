#!/usr/bin/env python3
import sys
import json
import os
import re
import time
from io import StringIO
from datetime import datetime
from urllib.error import HTTPError, URLError
from Bio import Entrez, SeqIO

def safe_name(text):
    text = re.sub(r'[^A-Za-z0-9._-]+', '_', text.strip())
    return text.strip('_') or 'query'

def esearch_with_retry(term, retmax, retries=5, delay=5):
    last_error = None
    for attempt in range(1, retries + 1):
        try:
            handle = Entrez.esearch(db="protein", term=term, retmax=retmax)
            record = Entrez.read(handle)
            handle.close()
            return record
        except Exception as e:
            last_error = e
            if attempt < retries:
                time.sleep(delay)
    raise last_error

def efetch_with_retry(id_list, retries=5, delay=5):
    last_error = None
    joined_ids = ",".join(id_list)
    for attempt in range(1, retries + 1):
        try:
            handle = Entrez.efetch(
                db="protein",
                id=joined_ids,
                rettype="fasta",
                retmode="text"
            )
            fasta_text = handle.read()
            handle.close()
            return fasta_text
        except Exception as e:
            last_error = e
            if attempt < retries:
                time.sleep(delay)
    raise last_error

def main():
    if len(sys.argv) != 4:
        print(json.dumps({
            "status": "error",
            "message": "Usage: fetch_sequences.py <family> <taxon> <max_sequences>"
        }))
        return
    
    family = sys.argv[1].strip()
    taxon = sys.argv[2].strip()

    try:
        max_sequences = int(sys.argv[3])
    except ValueError:
        print(json.dumps({
            "status": "error",
            "message": "max_sequences must be an integer."
        }))
        return

    if max_sequences < 1:
        max_sequences = 1
    if max_sequences > 1000:
        max_sequences = 1000

    Entrez.email = os.getenv("ENTREZ_EMAIL", "").strip()
    Entrez.api_key = os.getenv("ENTREZ_API_KEY", "").strip()
    Entrez.max_tries = 5
    Entrez.sleep_between_tries = 5
    
    if not Entrez.email:
        print(json.dumps({
            "status": "error",
            "message": "ENTREZ_EMAIL is not configured."
        }))
        return

    term = f"{family} AND {taxon}[Organism]"

    try:
        record = esearch_with_retry(term, max_sequences, retries=5, delay=5)
    except Exception as e:
        print(json.dumps({
            "status": "error",
            "message": f"NCBI esearch failed for term '{term}': {type(e).__name__}: {str(e)}"
        }))
        return

    ids = record.get("IdList", [])

    if not ids:
        print(json.dumps({
            "status": "empty",
            "term": term,
            "message": "No matching protein sequences found."
        }))
        return

    try:
        fasta_text = efetch_with_retry(ids, retries=5, delay=5)
    except Exception as e:
        print(json.dumps({
            "status": "error",
            "message": f"NCBI efetch failed for term '{term}': {type(e).__name__}: {str(e)}"
        }))
        return

    records = list(SeqIO.parse(StringIO(fasta_text), "fasta"))

    project_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    results_dir = os.path.join(project_root, "results")
    os.makedirs(results_dir, exist_ok=True)

    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    filename = f"{safe_name(family)}_{safe_name(taxon)}_{timestamp}.fasta"
    filepath = os.path.join(results_dir, filename)

    with open(filepath, "w") as out:
        out.write(fasta_text)

    preview = []
    for rec in records[:5]:
        preview.append({
            "id": rec.id,
            "description": rec.description,
            "length": len(rec.seq)
        })

    print(json.dumps({
        "status": "ok",
        "term": term,
        "sequence_count": len(records),
        "max_sequences_requested": max_sequences,
        "result_file": f"results/{filename}",
        "preview": preview
    }))

if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print(json.dumps({
            "status": "error",
            "message": f"Unexpected failure: {type(e).__name__}: {str(e)}"
        }))