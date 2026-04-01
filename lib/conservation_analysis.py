#!/usr/bin/env python3
import sys
import os
import json
from Bio import AlignIO

def pairwise_identity(seq1, seq2):
    matches = 0
    compared = 0

    for a, b in zip(str(seq1), str(seq2)):
        if a == '-' or b == '-':
            continue
        compared += 1
        if a == b:
            matches += 1

    if compared == 0:
        return 0.0, 0

    return round(matches / compared * 100, 2), compared

def main():
    if len(sys.argv) != 2:
        print(json.dumps({
            "status": "error",
            "message": "Usage: conservation_analysis.py <aligned_fasta>"
        }))
        return

    aligned_file = sys.argv[1]

    if not os.path.exists(aligned_file):
        print(json.dumps({
            "status": "error",
            "message": f"Aligned file not found: {aligned_file}"
        }))
        return

    alignment = AlignIO.read(aligned_file, "fasta")
    seq_count = len(alignment)
    align_length = alignment.get_alignment_length()

    base = os.path.splitext(os.path.basename(aligned_file))[0]
    results_dir = os.path.dirname(aligned_file)

    matrix_file = os.path.join(results_dir, f"{base}_identity_matrix.tsv")
    summary_file = os.path.join(results_dir, f"{base}_conservation_summary.json")

    ids = [record.id for record in alignment]

    # pairwise identity matrix
    with open(matrix_file, "w") as out:
        out.write("Sequence_ID\t" + "\t".join(ids) + "\n")
        for i, rec1 in enumerate(alignment):
            row = [rec1.id]
            for j, rec2 in enumerate(alignment):
                if i == j:
                    row.append("100.00")
                else:
                    identity, compared = pairwise_identity(rec1.seq, rec2.seq)
                    row.append(f"{identity:.2f}")
            out.write("\t".join(row) + "\n")

    # summary: mean pairwise identity
    values = []
    compared_sites = []
    for i in range(seq_count):
        for j in range(i + 1, seq_count):
            identity, compared = pairwise_identity(alignment[i].seq, alignment[j].seq)
            values.append(identity)
            compared_sites.append(compared)

    avg_identity = round(sum(values) / len(values), 2) if values else 0.0
    min_identity = round(min(values), 2) if values else 0.0
    max_identity = round(max(values), 2) if values else 0.0
    avg_compared_sites = round(sum(compared_sites) / len(compared_sites), 2) if compared_sites else 0.0

    summary = {
        "status": "ok",
        "aligned_file": aligned_file,
        "sequence_count": seq_count,
        "alignment_length": align_length,
        "average_pairwise_identity": avg_identity,
        "minimum_pairwise_identity": min_identity,
        "maximum_pairwise_identity": max_identity,
        "average_compared_sites": avg_compared_sites,
        "identity_matrix_file": matrix_file,
        "summary_file": summary_file
    }

    with open(summary_file, "w") as out:
        json.dump(summary, out, indent=2)

    print(json.dumps(summary, indent=2))

if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print(json.dumps({
            "status": "error",
            "message": str(e)
        }))