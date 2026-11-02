# Security test procedure

Use an isolated local database with two ordinary users (A and B), one administrator, and disposable content. Do not scan third-party sites. Record actual outcomes separately from expected outcomes.

| ID | Scenario | Phase 2 expected outcome |
| --- | --- | --- |
| F01 | Register, login, submit, edit and delete own feedback | Actions work and data persists |
| F02 | View feedback history in account B after creating account A records | A's records not shown |
| A01 | Attempt to access admin panel/search as non-admin | HTTP 403 |
| S01 | SQL metacharacters in admin search | Treated as a bound string, not SQL |
| S02 | Store harmless alert test input and view history | HTML shown as text; script not executed |
| S03 | Access/edit B's record ID as A | Not found; no cross-user mutation |
| S04 | Submit/create/edit/delete without CSRF token | HTTP 403 and unchanged record |
| S05 | Submit an altered CSRF token | HTTP 403 and unchanged record |
| S06 | Log out and reuse previous session | Authentication required |
| S07 | Examine Set-Cookie response | HttpOnly and SameSite; Secure when HTTPS |
| S08 | Inspect headers on login, register and authenticated routes | CSP, nosniff, frame policy, no-store |
| S09 | Try malformed feedback IDs | HTTP 400 or 404; no record disclosure |
| S10 | Repeat incorrect login attempts in same session | Lockout after the fifth failure |

## ZAP DAST

Record scan version, target URL, browser and policy, authenticated context, alert name, risk, evidence, affected endpoint, mitigation and rescan result. Investigate approximately three meaningful alerts and do not present informational alerts as exploits.

## Semgrep SAST

Run `semgrep scan --config auto anonymous-feedback-portal-phase2/` and save the complete output. Log every reported finding and its disposition: fixed, accepted with explanation, or false positive. Any reported totals must match the actual output and screenshots.

## Evidence record

For each test record: test ID; application commit; route; authenticated account; initial data state; input; expected result; observed result; HTTP status; screenshot file; result (pass/fail/not run); and notes. Do not report a test as passed unless executed.

## Scope and limitations

Phase 1 intentionally contains SQL injection in admin search, stored XSS in feedback display and IDOR in edit. Its login uses a prepared query and is not an SQL-injection demonstration. Validate each attack using disposable data only. The GitHub PHP syntax workflow is separate from runtime security verification.
