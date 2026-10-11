"""Generate a deployable English catalog using an offline Argos/CTranslate2 model.

This is a development tool. The deployed website needs only the generated JSON.
Dependencies: ctranslate2, sentencepiece. --model points to an extracted Argos
French-to-English package containing model/ and sentencepiece.model.
"""
from pathlib import Path
import argparse
import json
import re
import time

ROOT = Path(__file__).resolve().parents[1]


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--model", type=Path, required=True)
    parser.add_argument("--batch-size", type=int, default=48)
    args = parser.parse_args()
    import ctranslate2
    import sentencepiece
    inventory = json.loads((ROOT / "tools/translation-inventory.json").read_text(encoding="utf-8"))
    override_file = ROOT / "tools/translation-overrides.json"
    overrides = json.loads(override_file.read_text(encoding="utf-8")) if override_file.exists() else {"strings": {}, "templates": {}}
    output = ROOT / "assets/lang/en.json"
    output.parent.mkdir(exist_ok=True)
    existing = json.loads(output.read_text(encoding="utf-8")) if output.exists() else {"strings": {}}
    strings = existing["strings"]
    strings.update(overrides["strings"])
    tokenizer = sentencepiece.SentencePieceProcessor(model_file=str(args.model / "sentencepiece.model"))
    translator = ctranslate2.Translator(str(args.model / "model"), device="cpu", compute_type="int8", inter_threads=2, intra_threads=2)
    metadata = json.loads((args.model / "metadata.json").read_text(encoding="utf-8"))
    prefix = metadata.get("target_prefix", "")

    missing = [text for text in inventory["strings"] if text not in strings]
    began = time.monotonic()
    for offset in range(0, len(missing), args.batch_size):
        batch = missing[offset:offset + args.batch_size]
        chunks, owners = [], []
        for index, text in enumerate(batch):
            # Keep long policy paragraphs within the model's context window.
            tokens = tokenizer.encode(text, out_type=str)
            for start in range(0, len(tokens), 140):
                chunks.append(tokens[start:start + 140]); owners.append(index)
        options = {"replace_unknowns": True, "beam_size": 2, "max_batch_size": 1024, "batch_type": "tokens", "max_decoding_length": 256, "length_penalty": 0.2}
        if prefix: options["target_prefix"] = [[prefix]] * len(chunks)
        results = translator.translate_batch(chunks, **options)
        rendered = [[] for _ in batch]
        for owner, result in zip(owners, results):
            text = tokenizer.decode(result.hypotheses[0]).strip()
            if prefix and text.startswith(prefix): text = text[len(prefix):].lstrip()
            rendered[owner].append(text)
        for original, translated in zip(batch, rendered):
            strings[original] = " ".join(translated)
        output.write_text(json.dumps({"version": 2, "strings": strings, "templates": []}, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        print(f"Translated {min(offset + len(batch), len(missing))}/{len(missing)} strings ({time.monotonic() - began:.0f}s)", flush=True)

    # SentencePiece models can leave boundary markers in decoded output.
    for source, target in list(strings.items()):
        strings[source] = re.sub(r"\s+", " ", target.replace("\u2581", " ")).strip() or source
        if re.fullmatch(r"[\d\s.,]+(?:FCFA|XAF|%)", source): strings[source] = source
    templates = [{"source": source, "target": target} for source, target in overrides["templates"].items()]
    # Generalize changing totals/counts only when their numbers are preserved.
    number = re.compile(r"\d+(?:[ ,.\u00a0\u202f]\d+)*")
    known = {entry["source"] for entry in templates}
    for source, target in strings.items():
        source_numbers, target_numbers = number.findall(source), number.findall(target)
        if not source_numbers or len(source_numbers) != len(target_numbers): continue
        def replace_numbers(text):
            index = 0
            def replace(_):
                nonlocal index
                value = "{" + str(index) + "}"; index += 1; return value
            return number.sub(replace, text)
        pattern, translated = replace_numbers(source), replace_numbers(target)
        if pattern not in known and len(re.sub(r"\{\d+\}", "", pattern)) >= 4:
            templates.append({"source": pattern, "target": translated}); known.add(pattern)
    output.write_text(json.dumps({"version": 2, "strings": dict(sorted(strings.items())), "templates": templates}, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Catalog ready: {len(strings)} strings and {len(templates)} templates.", flush=True)


if __name__ == "__main__": main()
