# Cash Orphan Recovery — POS

## 1) Scenario
A POS session crashes or cannot be closed before closeSession() is executed. Cash sales stay in pending status.

## 2) Current state
- Cash payments are confirmed only at session close in PosSessionService::closeSession().
- If the session never closes, cash payments remain pending indefinitely.

## 3) Detection query
`sql
SELECT ps.id, ps.machine_id, ps.opened_at, COUNT(psa.id) as pending_sales
FROM pos_sessions ps
JOIN pos_sales psa ON psa.session_id = ps.id
WHERE ps.status != 'closed'
AND ps.updated_at < NOW() - INTERVAL 12 HOUR
AND psa.status = 'pending'
GROUP BY ps.id;
`

## 4) Manual recovery procedure
1. Identify sessions older than 12 hours with pending cash sales using the query above.
2. Verify with the store supervisor that the session should be closed.
3. Manually close the session using the POS admin workflow or a privileged maintenance endpoint.
4. Confirm that pending cash payments transition to confirmed after close.
5. Record a reason in session notes (example: [RECOVERY] Manual close after crash).

## 5) Future automated recovery recommendation
- Add a scheduled job that flags or auto-closes stale sessions after a defined SLA.
- Include a supervisor approval workflow to avoid auto-closing active sessions.
- Log all automatic recovery actions in the audit trail.
