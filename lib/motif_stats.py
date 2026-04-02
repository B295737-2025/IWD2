#!/usr/bin/env python3
import sys
import json
import os
from collections import Counter

def main():
    if len(sys.argv) != 2:
        print(json.dumps({
            "status": "error",
            "message": "Usage: motif_stats.py <motif_summary.json>"
        }))
        return

    summary_path = sys.argv[1]

    if not os.path.exists(summary_path):
        print(json.dumps({
            "status": "error",
            "message": f"Motif summary JSON not found: {summary_path}"
        }))
        return

    with open(summary_path, "r") as handle:
        data = json.load(handle)

    counts = Counter()

    for seq_res in data.get("results", []):
        for hit in seq_res.get("hits", []):
            motif_name = (hit.get("motif") or "").strip()
            if motif_name:
                counts[motif_name] += 1

    stats_path = summary_path.replace("_motif_summary.json", "_motif_stats.json")
    png_path = summary_path.replace("_motif_summary.json", "_motif_stats.png")

    stats_payload = {
        "status": "ok",
        "motif_counts": dict(counts),
        "stats_file": stats_path,
        "plot_file": png_path if counts else "",
        "plot_created": False,
        "plot_error": ""
    }

    with open(stats_path, "w") as out:
        json.dump(stats_payload, out, indent=2)

    if not counts:
        print(json.dumps(stats_payload, indent=2))
        return

    try:
        results_dir = os.path.dirname(summary_path)
        mpl_config_dir = os.path.join(results_dir, ".mplconfig")
        os.makedirs(mpl_config_dir, exist_ok=True)
        os.environ["MPLCONFIGDIR"] = mpl_config_dir

        import matplotlib
        matplotlib.use("Agg")
        import matplotlib.pyplot as plt

        motifs = list(counts.keys())
        hit_counts = [counts[m] for m in motifs]

        fig = plt.figure(figsize=(10, 4))
        ax = fig.add_subplot(1, 1, 1)
        ax.bar(motifs, hit_counts)
        ax.set_ylabel("Hit count")
        ax.set_title("Motif Hits Frequency")
        plt.xticks(rotation=45, ha="right")

        fig.tight_layout()
        fig.savefig(png_path, dpi=160, bbox_inches="tight")
        plt.close(fig)

        stats_payload["plot_created"] = True
        stats_payload["plot_file"] = png_path
        stats_payload["plot_error"] = ""

    except Exception as e:
        stats_payload["plot_created"] = False
        stats_payload["plot_file"] = ""
        stats_payload["plot_error"] = str(e)

    with open(stats_path, "w") as out:
        json.dump(stats_payload, out, indent=2)

    print(json.dumps(stats_payload, indent=2))

if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print(json.dumps({
            "status": "error",
            "message": str(e)
        }))
