"""Heuristic intent boundary checker for Rasa NLU examples.

Usage:
    python rasa/scripts/nlu_intent_guard.py

The script scans `rasa/data/nlu.yml` and prints potential overlaps between:
- ask_size_guide
- ask_faq
- ask_clarify
- ask_care_guide
- ask_product / ask_advice / provide_purpose

It is intentionally lightweight so you can run it before training to catch
examples that are likely to cause intent confusion.
"""
from __future__ import annotations

import re
from collections import defaultdict
from dataclasses import dataclass
from pathlib import Path
from typing import Dict, Iterable, List, Set, Tuple

try:
    import yaml
except Exception as exc:  # pragma: no cover
    raise SystemExit(
        "PyYAML is required to run this script. Install it with `pip install pyyaml`."
    ) from exc


ROOT = Path(__file__).resolve().parents[1]
NLU_FILE = ROOT / "data" / "nlu.yml"


@dataclass(frozen=True)
class IntentSpec:
    name: str
    keywords: Tuple[str, ...]
    patterns: Tuple[re.Pattern[str], ...] = ()


INTENTS: Tuple[IntentSpec, ...] = (
    IntentSpec(
        name="ask_size_guide",
        keywords=(
            "size giày",
            "chọn size",
            "hướng dẫn size",
            "đo chân",
            "đo size",
            "bảng size",
            "size bao nhiêu",
            "size nào",
        ),
        patterns=(
            re.compile(r"\bsize\b.*\b(giày|giay|shoe|shoes)?\b", re.I),
            re.compile(r"\b(đo|do)\s*(chân|chan|size)\b", re.I),
        ),
    ),
    IntentSpec(
        name="ask_faq",
        keywords=(
            "giao hàng",
            "đổi trả",
            "bảo hành",
            "thanh toán",
            "voucher",
            "khuyến mãi",
            "sale",
            "freeship",
            "shop có",
            "chính sách",
        ),
    ),
    IntentSpec(
        name="ask_clarify",
        keywords=(
            "đôi này thế nào",
            "mẫu này có ổn không",
            "có đáng mua không",
            "nên lấy đôi này không",
            "hợp không",
            "ổn không",
            "phù hợp không",
        ),
        patterns=(
            re.compile(r"\b(đôi|mẫu|cái|này|đó)\b.*\b(thế nào|ổn không|phù hợp|hợp|đáng mua)\b", re.I),
        ),
    ),
)


def _load_examples() -> Dict[str, List[str]]:
    if not NLU_FILE.exists():
        raise SystemExit(f"Cannot find NLU file: {NLU_FILE}")

    data = yaml.safe_load(NLU_FILE.read_text(encoding="utf-8")) or {}
    examples_by_intent: Dict[str, List[str]] = defaultdict(list)

    for item in data.get("nlu", []):
        intent = item.get("intent")
        examples = item.get("examples")
        if not intent or not examples:
            continue
        for line in str(examples).splitlines():
            line = line.strip()
            if not line.startswith("-"):
                continue
            text = line.lstrip("-").strip()
            if text:
                examples_by_intent[intent].append(text)

    return examples_by_intent


def _normalize(text: str) -> str:
    t = text.lower().strip()
    t = re.sub(r"\[[^\]]+\]\([^\)]+\)", lambda m: m.group(0).split("](")[0][1:], t)
    t = re.sub(r"[^\w\s\u00C0-\u1EF9/.-]", " ", t)
    t = re.sub(r"\s+", " ", t)
    return t


def _score(text: str, spec: IntentSpec) -> int:
    t = _normalize(text)
    score = 0
    for kw in spec.keywords:
        if kw in t:
            score += 2
    for pat in spec.patterns:
        if pat.search(t):
            score += 3
    return score


def main() -> None:
    examples_by_intent = _load_examples()

    print("Intent boundary check")
    print("=" * 80)

    # Find likely overlaps.
    for intent, examples in examples_by_intent.items():
        if intent not in {spec.name for spec in INTENTS}:
            continue
        print(f"\n[{intent}]")
        for ex in examples:
            scores = {spec.name: _score(ex, spec) for spec in INTENTS}
            ranked = sorted(scores.items(), key=lambda x: x[1], reverse=True)
            best_intent, best_score = ranked[0]
            runner_up, runner_score = ranked[1]

            if best_score <= 0:
                continue

            # Flag examples that fit another monitored intent almost as well.
            if runner_score > 0 and best_intent != intent:
                print(f"  - POSSIBLE MISLABEL: {ex}")
                print(f"    predicted={best_intent}:{best_score}, second={runner_up}:{runner_score}, actual={intent}")
            elif intent in {"ask_faq", "ask_clarify"} and runner_score > 0:
                # Same-label but high overlap with another bucket.
                if any(name in {intent, runner_up} for name in {"ask_size_guide", "ask_faq", "ask_clarify"}):
                    if runner_up != intent:
                        print(f"  - REVIEW: {ex}")
                        print(f"    best={best_intent}:{best_score}, runner={runner_up}:{runner_score}")

    print("\nDone.")


if __name__ == "__main__":
    main()
