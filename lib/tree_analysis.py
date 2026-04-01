#!/usr/bin/env python3
import sys
import os
import json

from Bio import AlignIO, Phylo
from Bio.Phylo.TreeConstruction import DistanceCalculator, DistanceTreeConstructor

def summarize_distances(distance_matrix):
    values = []
    for i, row in enumerate(distance_matrix.matrix):
        for j in range(i):
            values.append(float(row[j]))

    if not values:
        return {
            "min_distance": 0.0,
            "max_distance": 0.0,
            "average_distance": 0.0
        }

    return {
        "min_distance": round(min(values), 4),
        "max_distance": round(max(values), 4),
        "average_distance": round(sum(values) / len(values), 4)
    }

def main():
    if len(sys.argv) != 2:
        print(json.dumps({
            "status": "error",
            "message": "Usage: tree_analysis.py <aligned_fasta>"
        }))
        return

    aligned_file = sys.argv[1]

    if not os.path.exists(aligned_file):
        print(json.dumps({
            "status": "error",
            "message": f"Aligned FASTA not found: {aligned_file}"
        }))
        return

    alignment = AlignIO.read(aligned_file, "fasta")
    seq_count = len(alignment)

    if seq_count < 2:
        print(json.dumps({
            "status": "error",
            "message": "At least two sequences are required to build a tree."
        }))
        return

    results_dir = os.path.dirname(aligned_file)
    base = os.path.splitext(os.path.basename(aligned_file))[0]

    tree_file = os.path.join(results_dir, f"{base}_nj_tree.nwk")
    png_file = os.path.join(results_dir, f"{base}_nj_tree.png")
    summary_file = os.path.join(results_dir, f"{base}_tree_summary.json")

    calculator = DistanceCalculator("identity")
    distance_matrix = calculator.get_distance(alignment)

    constructor = DistanceTreeConstructor()
    tree = constructor.nj(distance_matrix)

    Phylo.write(tree, tree_file, "newick")

    png_created = False
    png_error = ""

    try:
        mpl_config_dir = os.path.join(results_dir, ".mplconfig")
        os.makedirs(mpl_config_dir, exist_ok=True)
        os.environ["MPLCONFIGDIR"] = mpl_config_dir

        import matplotlib
        matplotlib.use("Agg")
        import matplotlib.pyplot as plt

        fig_height = max(6, seq_count * 0.28)
        fig = plt.figure(figsize=(14, fig_height))
        ax = fig.add_subplot(1, 1, 1)
        Phylo.draw(tree, axes=ax, do_show=False)
        plt.tight_layout()
        fig.savefig(png_file, dpi=200, bbox_inches="tight")
        plt.close(fig)
        png_created = True
    except Exception as e:
        png_error = str(e)

    dist_summary = summarize_distances(distance_matrix)

    summary = {
        "status": "ok",
        "aligned_file": aligned_file,
        "sequence_count": seq_count,
        "tree_file": tree_file,
        "png_file": png_file if png_created else "",
        "png_created": png_created,
        "png_error": png_error,
        "summary_file": summary_file,
        "min_distance": dist_summary["min_distance"],
        "max_distance": dist_summary["max_distance"],
        "average_distance": dist_summary["average_distance"]
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
