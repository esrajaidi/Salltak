from pathlib import Path
import re

s = Path("resources/views/orders/show.blade.php").read_text()
inline_positions=[m.start() for m in re.finditer(r"@php\s*\(", s)]
block_positions=[m.start() for m in re.finditer(r"@php\s*(?:\r?\n|\s+\$)", s)]
# A block @php after an inline @php is unsafe on affected Blade compilers.
assert not (inline_positions and block_positions and min([b for b in block_positions if b>min(inline_positions)] or [10**18]) < 10**18), "block @php appears after inline @php"
