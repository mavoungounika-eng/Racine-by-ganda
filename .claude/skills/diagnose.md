| name | diagnose |
| description | Disciplined diagnosis loop for hard bugs and performance regressions. Reproduce → minimise → hypothesise → instrument → fix → regression-test. Use when user says "diagnose this" / "debug this", reports a bug, says something is broken/throwing/failing, or describes a performance regression. |

# Diagnose

A discipline for hard bugs. Skip phases only when explicitly justified.

## Phase 1 — Build a feedback loop

This is the skill. Everything else is mechanical. If you have a fast, deterministic, agent-runnable pass/fail signal for the bug, you will find the cause.

Ways to construct one:
1. Failing test at whatever seam reaches the bug (unit, integration, e2e)
2. Curl / HTTP script against a running dev server
3. CLI invocation with a fixture input, diffing stdout against a known-good snapshot
4. Throwaway harness — minimal subset of the system that exercises the bug code path
5. Property / fuzz loop — if the bug is "sometimes wrong output", run 1000 random inputs

Iterate on the loop: make it faster, make the signal sharper, make it deterministic.
A 2-second deterministic loop is a debugging superpower.

Do not proceed to Phase 2 until you have a loop you believe in.

## Phase 2 — Reproduce

Run the loop. Confirm the failure mode matches what the user described. Confirm reproducible.

## Phase 3 — Hypothesise

Generate 3-5 ranked hypotheses before testing any of them.
Each must be falsifiable: "If X is the cause, then Y will make the bug disappear."
Show the ranked list to the user before testing — they often have domain knowledge that re-ranks instantly.

## Phase 4 — Instrument

Each probe must map to a specific prediction from Phase 3. Change one variable at a time.
Tag every debug log with a unique prefix e.g. [DEBUG-a4f2]. Cleanup = single grep.
Never "log everything and grep".

## Phase 5 — Fix + regression test

Write the regression test BEFORE the fix — but only if there is a correct seam for it.
1. Turn the minimised repro into a failing test
2. Watch it fail
3. Apply the fix
4. Watch it pass
5. Re-run the Phase 1 feedback loop

## Phase 6 — Cleanup + post-mortem

- Original repro no longer reproduces
- All [DEBUG-...] instrumentation removed
- Throwaway prototypes deleted
- The hypothesis that turned out correct is stated in the commit message
- Ask: what would have prevented this bug?
