# Threat model

## Trust boundaries
Browser input is untrusted. The PHP web application processes credentials and feedback, holds server-side sessions, and accesses MySQL through a dedicated least-privilege account. Only authenticated users may access personal submissions; administrator privileges permit viewing account-linked feedback.

## Protected assets
Credentials and password hashes; login sessions; feedback contents and ownership; database credentials; integrity of create/edit/delete operations.

## Threat analysis

| Threat | Entry point | Mitigation | Regression |
| --- | --- | --- | --- |
| SQL injection | Administrator search | Prepared statements | Special characters remain data |
| Stored XSS | Feedback history | Output encoding and CSP | Test string renders as text |
| IDOR | Feedback IDs in edit/delete | Ownership predicate | User A cannot access User B records |
| CSRF | State-changing POST forms | Server-side session token | Missing/invalid token returns 403 |
| Session hijacking | Cookies, login and logout | HttpOnly/SameSite, strict session, ID rotation, timeout | Check cookies and invalidate sessions |
| Role bypass | Administrator pages | Session role checks | Non-admin receives 403 |
| Credential guessing | Login | Session-local attempt limit | Rate-limiting test; note bypass risk |
| Credential disclosure | Source/configuration | Environment-only secrets | Repository review |

## Residual risks

The login attempt limit is session-scoped, not a production-grade shared rate limiter. Browser-to-server transport requires HTTPS outside isolated local development. A clean DAST or SAST scan covers only tested routes/rules. Administrative access prevents the service being fully anonymous. Operational monitoring, backup controls and independent review are not included in this classroom implementation.
