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

def compute_conservation_profile(alignment):
    profile = []
    align_length = alignment.get_alignment_length()

    for col_idx in range(align_length):
        counts = {}

        for record in alignment:
            aa = str(record.seq[col_idx])
            if aa in ('-', '.'):
                continue
            counts[aa] = counts.get(aa, 0) + 1

        if counts:
            max_count = max(counts.values())
            total = sum(counts.values())
            profile.append(round(max_count / total * 100, 2))
        else:
            profile.append(0.0)

    return profile

def write_profile_tsv(profile, profile_file):
    with open(profile_file, "w") as out:
        out.write("position\tconservation_percent\n")
        for pos, value in enumerate(profile, start=1):
            out.write(f"{pos}\t{value}\n")

def plot_conservation_profile(profile, plot_file, results_dir):
    try:
        mpl_config_dir = os.path.join(results_dir, ".mplconfig")
        os.makedirs(mpl_config_dir, exist_ok=True)
        os.environ["MPLCONFIGDIR"] = mpl_config_dir

        import matplotlib
        matplotlib.use("Agg")
        import matplotlib.pyplot as plt

        fig = plt.figure(figsize=(10, 4))
        ax = fig.add_subplot(1, 1, 1)

        ax.plot(range(1, len(profile) + 1), profile, linewidth=1)
        ax.set_xlabel("Alignment column")
        ax.set_ylabel("Conservation (%)")
        ax.set_title("Conservation Profile")
        ax.set_ylim(0, 100)

        fig.tight_layout()
        fig.savefig(plot_file, dpi=160, bbox_inches="tight")
        plt.close(fig)

        return True, ""
    except Exception as e:
        return False, str(e)

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
    profile_file = os.path.join(results_dir, f"{base}_conservation_profile.tsv")
    plot_file = os.path.join(results_dir, f"{base}_conservation_plot.png")

    ids = [record.id for record in alignment]

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

    profile = compute_conservation_profile(alignment)
    write_profile_tsv(profile, profile_file)
    plot_created, plot_error = plot_conservation_profile(profile, plot_file, results_dir)

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
        "profile_tsv_file": profile_file,
        "profile_plot_file": plot_file if plot_created else "",
        "profile_plot_created": plot_created,
        "profile_plot_error": plot_error,
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