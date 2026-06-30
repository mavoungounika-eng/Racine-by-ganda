#!/usr/bin/env python3
import subprocess, re
from pathlib import Path
from datetime import date

def run(cmd):
    r = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    return r.stdout.strip()

today = date.today().strftime("%-d %B %Y")
phpunit_tail = run("redis-cli FLUSHDB > /dev/null 2>&1 && ./vendor/bin/phpunit 2>&1 | tail -3")

match = re.search(r"Tests:\s*(\d+).*?(?:Failures?:\s*(\d+).*?)?Skipped:\s*(\d+)", phpunit_tail)
if match:
    tests, failures, skipped = match.groups()
    ref_line = f"Tests: {tests} | Failures: {failures} (stable) | Skipped: {skipped} | Flaky Redis: 0-4 par run"
    print(f"PHPUnit: {ref_line}")
else:
    print(f"Output brut: {phpunit_tail}")
    ref_line = None

p = Path("CLAUDE.md")
content = p.read_text(encoding="utf-8")

if ref_line:
    content = re.sub(
        r"Tests: \d+ \| Failures: \d+ \(stable\) \| Skipped: \d+ \| Flaky Redis: [\d-]+ par run",
        ref_line,
        content
    )

p.write_text(content, encoding="utf-8")
print(f"OK ‒ CLAUDE.md mis a jour ({today})")
