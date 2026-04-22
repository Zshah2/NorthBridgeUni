#!/usr/bin/env python3
"""
Read Data/student_data_vertical - student_data_vertical.csv (two-column vertical use cases)
and write Google-Doc-friendly + Markdown copies under docs/.

Source format per row: "FieldName:", "value" (CSV; Basic Flow may contain commas inside quotes).
"""

from __future__ import annotations

import csv
from dataclasses import dataclass
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "Data" / "student_data_vertical - student_data_vertical.csv"
OUT_TXT = ROOT / "docs" / "USE_CASES_GOOGLE_DOC.txt"
OUT_MD = ROOT / "docs" / "STUDENT_USE_CASES.md"


@dataclass
class UseCase:
    name: str = ""
    actors: str = ""
    initial: str = ""
    basic: str = ""
    exit_: str = ""

    def score(self) -> int:
        return sum(1 for x in (self.name, self.actors, self.initial, self.basic, self.exit_) if (x or "").strip())


def normalize_key(raw: str) -> str | None:
    k = (raw or "").strip()
    if not k:
        return None
    if k.endswith(":"):
        k = k[:-1].strip()
    return k or None


def flush_best_by_name(store: dict[str, UseCase], uc: UseCase) -> None:
    """Keep one row per use-case *name* (spreadsheet repeats); prefer the most complete row."""
    name = uc.name.strip()
    if not name:
        return
    key = name.casefold()
    prev = store.get(key)
    if prev is None or uc.score() > prev.score():
        store[key] = uc


def parse_vertical_csv(path: Path) -> list[UseCase]:
    store: dict[str, UseCase] = {}
    uc = UseCase()

    with path.open(newline="", encoding="utf-8", errors="replace") as f:
        reader = csv.reader(f)
        for row in reader:
            if not row or all(not (c or "").strip() for c in row):
                continue
            key_raw = (row[0] or "").strip()
            val = (row[1] if len(row) > 1 else "") or ""
            val = val.strip()

            nk = normalize_key(key_raw)
            if nk is None:
                continue

            if nk.lower() == "name":
                flush_best_by_name(store, uc)
                uc = UseCase(name=val)
            elif nk.lower() == "actors":
                uc.actors = val
            elif nk.lower() == "initial condition":
                uc.initial = val
            elif nk.lower() == "basic flow":
                uc.basic = val
            elif nk.lower() == "exit condition":
                uc.exit_ = val
            # ignore unknown keys

    flush_best_by_name(store, uc)

    # Stable order: by name then actors
    items = list(store.values())
    items.sort(key=lambda x: (x.name.lower(), x.actors.lower()))
    return items


def render_txt(cases: list[UseCase]) -> str:
    parts: list[str] = []
    parts.append("CollegeWeb — Use cases (from student_data_vertical CSV)\n")
    parts.append("Paste into Google Docs as needed. Each block is one use case.\n")
    for uc in cases:
        parts.append("\n" + "—" * 40 + "\n\n")
        parts.append(f"Name:\n{uc.name}\n\n")
        parts.append(f"Actors:\n{uc.actors}\n\n")
        parts.append(f"Initial Condition:\n{uc.initial}\n\n")
        parts.append(f"Basic Flow:\n{uc.basic}\n\n")
        parts.append(f"Exit Condition:\n{uc.exit_}\n")
    parts.append("\n")
    return "".join(parts)


def render_md(cases: list[UseCase]) -> str:
    parts: list[str] = []
    parts.append("# CollegeWeb — Student use cases\n\n")
    parts.append(
        "This file is **generated from** "
        "`Data/student_data_vertical - student_data_vertical.csv`. "
        "Regenerate with: `python3 scripts/build_use_cases_from_vertical_csv.py`\n\n"
        "For Google Docs, use **`docs/USE_CASES_GOOGLE_DOC.txt`**.\n\n"
        "For database / SRS SQL notes, see **`docs/SQL_NOTES.md`**.\n\n"
    )
    for uc in cases:
        parts.append("---\n\n")
        parts.append(f"Name:\n{uc.name}\n\n")
        parts.append(f"Actors:\n{uc.actors}\n\n")
        parts.append(f"Initial Condition:\n{uc.initial}\n\n")
        parts.append(f"Basic Flow:\n{uc.basic}\n\n")
        parts.append(f"Exit Condition:\n{uc.exit_}\n\n")
    return "".join(parts)


def main() -> None:
    if not SRC.exists():
        raise SystemExit(f"Missing source file: {SRC}")

    cases = parse_vertical_csv(SRC)
    OUT_TXT.parent.mkdir(parents=True, exist_ok=True)
    OUT_TXT.write_text(render_txt(cases), encoding="utf-8")
    OUT_MD.write_text(render_md(cases), encoding="utf-8")
    print(f"Wrote {len(cases)} use cases -> {OUT_TXT}")
    print(f"Wrote {len(cases)} use cases -> {OUT_MD}")


if __name__ == "__main__":
    main()
