# Known Test Limitations

## SQLite & CHECK Constraints

The test suite uses SQLite by default.
Some accounting migrations rely on `ALTER TABLE ... ADD CONSTRAINT CHECK`,
which is not supported by SQLite.

Impact:
- RBAC tests may fail before assertions
- PaymentsHub RBAC tests may fail during migrations

Status:
- Known limitation
- No impact on production (MySQL)
- To be addressed in Phase 2 (Stabilisation)
