| name | tdd |
| description | Test-driven development with red-green-refactor loop. Use when user wants to build features or fix bugs using TDD, mentions red-green-refactor, wants integration tests, or asks for test-first development. |

# Test-Driven Development

## Philosophy

Tests should verify behavior through public interfaces, not implementation details.
Code can change entirely; tests should not.

Good tests exercise real code paths through public APIs.
Bad tests are coupled to implementation — they mock internal collaborators or test private methods.
Warning sign: your test breaks when you refactor, but behavior has not changed.

## Anti-Pattern: Horizontal Slices

DO NOT write all tests first, then all implementation.

WRONG (horizontal):
  RED:   test1, test2, test3, test4, test5
  GREEN: impl1, impl2, impl3, impl4, impl5

RIGHT (vertical):
  RED->GREEN: test1->impl1
  RED->GREEN: test2->impl2
  RED->GREEN: test3->impl3

Vertical slices via tracer bullets. One test -> one implementation -> repeat.

## Workflow

### 1. Planning
- Confirm which behaviors to test (prioritize)
- Design interfaces for testability
- List behaviors to test (not implementation steps)
- Get user approval on the plan

### 2. Tracer Bullet
Write ONE test that confirms ONE thing about the system:
  RED:   Write test for first behavior -> test fails
  GREEN: Write minimal code to pass -> test passes

### 3. Incremental Loop
For each remaining behavior:
  RED:   Write next test -> fails
  GREEN: Minimal code to pass -> passes

Rules:
- One test at a time
- Only enough code to pass current test
- Do not anticipate future tests

### 4. Refactor
After all tests pass, look for refactor candidates.
NEVER refactor while RED. Get to GREEN first.

## Checklist Per Cycle
- Test describes behavior, not implementation
- Test uses public interface only
- Test would survive internal refactor
- Code is minimal for this test
- No speculative features added
