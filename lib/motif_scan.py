#!/usr/bin/env python3
import sys
import os
import json
import re
import tempfile
import subprocess
from Bio import SeqIO

PATMAT = "/usr/bin/patmatmotifs"

def parse_patmatmotifs_report(report_text):
    """
    Parser for EMBOSS patmatmotifs text output.
    Returns:
      {
        "sequence_id": "...",
        "hit_count": int or None,
        "hits": [
          {
            "motif": "...",
            "start": "...",
            "end": "...",
            "length": "...",
            "raw": "..."
          },
          ...
        ]
      }
    """
    result = {
        "sequence_id": None,
        "hit_count": None,
        "hits": []
    }

    seq_match = re.search(r"# Sequence:\s+(\S+)", report_text)
    if seq_match:
        result["sequence_id"] = seq_match.group(1)

    hit_match = re.search(r"# HitCount:\s*(\d+)", report_text)
    if hit_match:
        result["hit_count"] = int(hit_match.group(1))

    lines = report_text.splitlines()

    current_hit = None

    for line in lines:
        stripped = line.strip()

        if stripped.startswith("Length ="):
            # If an unfinished hit somehow already exists, save it first
            if current_hit is not None:
                current_hit["raw"] = current_hit["raw"].strip()
                result["hits"].append(current_hit)

            length_match = re.search(r"Length\s*=\s*(\d+)", stripped)
            current_hit = {
                "motif": "",
                "start": "",
                "end": "",
                "length": length_match.group(1) if length_match else "",
                "raw": line + "\n"
            }
            continue

        if current_hit is not None:
            current_hit["raw"] += line + "\n"

            start_match = re.search(r"Start\s*=\s*position\s*(\d+)\s*of sequence", stripped, re.IGNORECASE)
            if start_match:
                current_hit["start"] = start_match.group(1)
                continue

            end_match = re.search(r"End\s*=\s*position\s*(\d+)\s*of sequence", stripped, re.IGNORECASE)
            if end_match:
                current_hit["end"] = end_match.group(1)
                continue

            motif_match = re.search(r"Motif\s*=\s*(.+)", stripped)
            if motif_match:
                current_hit["motif"] = motif_match.group(1).strip()
                current_hit["raw"] = current_hit["raw"].strip()
                result["hits"].append(current_hit)
                current_hit = None
                continue

    # safety fallback
    if current_hit is not None:
        current_hit["raw"] = current_hit["raw"].strip()
        result["hits"].append(current_hit)

    return result

def run_patmat_on_one_record(record):
    with tempfile.TemporaryDirectory() as tmpdir:
        fasta_path = os.path.join(tmpdir, "one.fasta")
        out_path = os.path.join(tmpdir, "one.patmatmotifs")

        with open(fasta_path, "w") as handle:
            SeqIO.write(record, handle, "fasta")

        cmd = [
            PATMAT,
            "-sequence", fasta_path,
            "-outfile", out_path,
            "-full", "Y",
            "-auto"
        ]

        proc = subprocess.run(cmd, capture_output=True, text=True)

        if not os.path.exists(out_path):
            return {
                "status": "error",
                "sequence_id": record.id,
                "message": proc.stderr.strip() or proc.stdout.strip() or "patmatmotifs did not create output file."
            }

        with open(out_path, "r") as handle:
            report_text = handle.read()

        parsed = parse_patmatmotifs_report(report_text)
        if not parsed["sequence_id"]:
            parsed["sequence_id"] = record.id

        return {
            "status": "ok",
            "sequence_id": parsed["sequence_id"],
            "length": len(record.seq),
            "hit_count": parsed["hit_count"] if parsed["hit_count"] is not None else 0,
            "hits": parsed["hits"],
            "raw_report": report_text
        }

def main():
    if len(sys.argv) != 2:
        print(json.dumps({
            "status": "error",
            "message": "Usage: motif_scan.py <input_fasta>"
        }))
        return

    input_fasta = sys.argv[1]

    if not os.path.exists(input_fasta):
        print(json.dumps({
            "status": "error",
            "message": f"Input FASTA not found: {input_fasta}"
        }))
        return

    records = list(SeqIO.parse(input_fasta, "fasta"))
    if not records:
        print(json.dumps({
            "status": "error",
            "message": "No sequences found in input FASTA."
        }))
        return

    all_results = []
    total_hits = 0
    sequences_with_hits = 0
    raw_reports = []

    for record in records:
        one_result = run_patmat_on_one_record(record)
        all_results.append(one_result)

        if one_result["status"] == "ok":
            hc = one_result.get("hit_count", 0) or 0
            total_hits += hc
            if hc > 0:
                sequences_with_hits += 1
            raw_reports.append(
                "########################################\n"
                f"# Sequence: {one_result['sequence_id']}\n"
                f"# HitCount: {hc}\n"
                "########################################\n\n"
                + one_result.get("raw_report", "")
            )

    print(json.dumps({
        "status": "ok",
        "sequence_count": len(records),
        "sequences_with_hits": sequences_with_hits,
        "total_hits": total_hits,
        "results": all_results,
        "combined_raw_report": "\n\n".join(raw_reports)
    }))

if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print(json.dumps({
            "status": "error",
            "message": str(e)
        }))
