# Anonymous Feedback Portal

A PHP/MySQL web-security coursework project with two executable versions: a deliberately vulnerable baseline and a hardened implementation.

**Privacy model:** Feedback is associated with accounts. Administrators can see usernames, so the portal is *not anonymous to administrators*. It should not be deployed for sensitive or confidential reporting.

## Layout

| Path | Contents |
| --- | --- |
| `apps/insecure/` | Insecure baseline, **isolated local testing only** |
| `apps/secure/` | Secure reference implementation |
| `database/schema.sql` | MySQL schema for clean setup |
| `tests/TEST_PLAN.md` | Manual security and regression test matrix |
| `docs/THREAT_MODEL.md` | Assets, threats, controls and residual risks |
| `.github/workflows/php-lint.yml` | Automated PHP syntax checks |

## Local installation

Prerequisites: PHP 8.2 or later with `mysqli` and `mysqlnd`, MySQL/MariaDB and Apache or PHP's built-in web server.

1. Import `database/schema.sql` into MySQL.
2. Create a low-privilege database account. From the MySQL administrator session:

   ```sql
   CREATE USER 'feedback_app'@'127.0.0.1' IDENTIFIED BY 'replace-with-a-unique-long-password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON anonymous_feedback.* TO 'feedback_app'@'127.0.0.1';
   ```

3. Set environment variables in the web-server/PHP process. For a local development shell:

   ```sh
   export DB_HOST=127.0.0.1
   export DB_NAME=anonymous_feedback
   export DB_USER=feedback_app
   export DB_PASSWORD='your-local-password'
   php -S 127.0.0.1:8080 -t apps/secure
   ```

4. Visit `http://127.0.0.1:8080/register.php`. Register two test users, log in, submit feedback, and verify account separation.
5. Admin access is assigned separately by a database administrator: `UPDATE users SET role='admin' WHERE username='your-test-admin';`. Do not build a self-service admin role selection into registration.

Phase 1 retains legacy local database settings and intentional vulnerabilities; run it only with disposable local data in an isolated environment. Do not put Phase 1 on the public internet.

## Security comparison

| Category | Phase 1 | Phase 2 |
| --- | --- | --- |
| SQL injection | Unsafe administrator search concatenation | Parameterized search |
| Stored XSS | Unencoded feedback message on history page | HTML output encoding and restrictive CSP |
| IDOR | Edit uses an unscoped feedback ID | Owner-scoped record read and update |
| CSRF | State-changing actions without token validation | Server-validated CSRF tokens |
| Authentication | Password hashing and prepared login | Additional CSRF, strict sessions and basic session-local lockout |
| Browser controls | Missing or inconsistent protections | CSP, anti-framing, cache and MIME controls |
| Database credentials | Local default account | Environment-based dedicated DB account |

**Important:** Phase 1 login already uses a prepared query. The SQL-injection comparison is through the **administrator feedback search**, not a login bypass. Phase 1 is not intended to be insecure on every route.

## Evidence and tests

Follow `tests/TEST_PLAN.md` to reproduce individual tests. For each claim, retain the input, relevant HTTP response, data state, code location, result and screenshot. ZAP and Semgrep reports should record the actual tool configuration and all findings, including false positives and residual risks. A missing alert is not proof that the whole system is secure.

The workflow validates PHP syntax only. The GitHub repository does not claim automated integration, DAST or SAST tests passed without execution.

## Limitations

This is an educational comparison, not a production-ready service. A fresh browser session can bypass session-local login throttling; production deployment would require centralized throttling, monitoring, HTTPS, backups and independent review. Secure cookies and HSTS are enabled for directly detected HTTPS; reverse proxies need explicit trusted-HTTPS configuration.
