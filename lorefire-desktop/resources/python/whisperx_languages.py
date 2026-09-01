"""English/Spanish allowlist helpers for WhisperX.

No WhisperX/torch imports — unit-tested without the venv.
English-only ``*.en`` checkpoints are coerced to the multilingual twin.
"""

from __future__ import annotations

ALLOWED = ("en", "es")
DEFAULT_ALLOWLIST = ("en", "es")
MULTILINGUAL_MODELS = ("tiny", "base", "small", "medium", "large-v2", "large-v3")


def parse_languages(value: str | None) -> list[str]:
    if not value:
        return list(DEFAULT_ALLOWLIST)
    out: list[str] = []
    for part in value.replace(";", ",").split(","):
        code = part.strip().lower()
        if code in ALLOWED and code not in out:
            out.append(code)
    return out or list(DEFAULT_ALLOWLIST)


def coerce_multilingual_model(name: str | None) -> str:
    raw = (name or "base").strip().lower()
    if raw.endswith(".en"):
        raw = raw[: -len(".en")]
    if raw in ("large", "large-v1"):
        raw = "large-v3"
    if raw not in MULTILINGUAL_MODELS:
        return "base"
    return raw or "base"


def clamp_detected_language(
    detected: str | None,
    probs: dict[str, float] | None,
    allowlist: list[str] | tuple[str, ...],
    previous: str | None = None,
) -> str:
    """Keep only allowlisted languages. Never leave a French (etc.) detect in place."""
    allow = [c for c in allowlist if c in ALLOWED] or list(DEFAULT_ALLOWLIST)
    code = (detected or "").strip().lower()
    if code in allow:
        return code

    best_lang = None
    best_p = 0.0
    for lang in allow:
        p = float((probs or {}).get(lang, 0.0) or 0.0)
        if p > best_p:
            best_p = p
            best_lang = lang
    if best_lang is not None and best_p > 0.0:
        return best_lang

    if previous in allow:
        return previous
    return allow[0]


if __name__ == "__main__":
    # Lightweight self-check used by PHPUnit.
    assert coerce_multilingual_model("large-v3.en") == "large-v3"
    assert coerce_multilingual_model("medium.en") == "medium"
    assert coerce_multilingual_model("tiny.en") == "tiny"
    assert clamp_detected_language("es", None, ["en", "es"], None) == "es"
    fr = clamp_detected_language("fr", {"fr": 0.55, "es": 0.30, "en": 0.10}, ["en", "es"], "en")
    assert fr == "es", fr
    zh = clamp_detected_language("zh", {"zh": 0.9}, ["en", "es"], "en")
    assert zh == "en", zh
    print("ok")
