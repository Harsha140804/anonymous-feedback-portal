# Anonymous Feedback Portal

A PHP/MySQL web-security coursework project with two executable versions: a deliberately vulnerable baseline and a hardened implementation.

**Privacy model:** Feedback is associated with accounts. Administrators can see usernames, so the portal is *not anonymous to administrators*. It should not be deployed for sensitive or confidential reporting.

## Layout

| Path | Contents |
| --- | --- |
| `apps/insecure/` | Insecure baseline, **isolated local testing only** |
| `apps/secure/` | Secure reference implementation |
| `database/schema.sql` | MySQL schema for clean setup |
| `tests/TEST_PLAN.md` | Manual testing and evidence procedures |
| `tests/integration.php` | Executable HTTP and database security regression tests |
| `tests/setup_db.php` | Initialises disposable CI database from the schema |
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
   php -d expose_php=Off -S 127.0.0.1:8080 -t apps/secure apps/secure/router.php
   ```

4. Visit `http://127.0.0.1:8080/register.php`. Register two test users, log in, submit feedback, and verify account separation.
5. Admin access is assigned separately by a database administrator: `UPDATE users SET role='admin' WHERE username='your-test-admin';`. Do not build a self-service admin role selection into registration.

Phase 1 uses the same environment-based database configuration and retains deliberate vulnerabilities. Run it only with disposable local data in an isolated environment; **never expose it to the public internet**.

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

GitHub Actions performs PHP syntax checks, live PHP/MySQL security regression tests and an independent Semgrep scan scoped to `apps/secure/`. The integration suite exercises two accounts, an administrator, CRUD, session security, CSRF, stored XSS, cross-user access restrictions and search SQL injection. It also checks that the intentionally vulnerable baseline remains demonstrable. Semgrep JSON output is preserved as a workflow artifact for review.

The automated suite is **not** a substitute for an authenticated OWASP ZAP assessment, manual browser testing or a full source-code review. Individual scanner findings must be investigated, not silently discarded.

## Limitations

This is an educational comparison, not a production-ready service. A fresh browser session can bypass session-local login throttling; production deployment would require centralized throttling, monitoring, HTTPS, backups and independent review. Secure cookies and HSTS are enabled for directly detected HTTPS; reverse proxies need explicit trusted-HTTPS configuration.

## Continuous integration

The following workflows run on pushes and pull requests:

- `PHP syntax checks`: parses both application versions and checks repository hygiene.
- `PHP and MySQL security regression`: starts disposable MySQL 8 and two loopback PHP servers, then executes `tests/integration.php` using disposable users.
- `PHP static security scan`: runs Semgrep with the reported ruleset and saves its complete JSON output. A completed scan does not mean zero findings.

To execute integration tests locally, provision a **disposable** MySQL database, configure `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, and `DB_ROOT_PASSWORD` in a local test environment, run `php tests/setup_db.php` (which deletes existing test users), start PHP development servers for both app versions on ports 8090 and 8091, and run `php tests/integration.php`. Never point the setup script at a database holding real data.

## Passive ZAP evidence (2 November 2026)

The authenticated initial ZAP 2.17.0 baseline discovered 10 URLs and recorded 13 JSON alert entries. After security header coverage was extended to static and missing-file responses and PHP version exposure was disabled in the scan environment, a follow-up baseline recorded four informational entries and no High/Medium/Low risk entries on the explored routes. The HTML, JSON and Markdown reports are retained as GitHub Actions artifacts. The baseline is passive, not a replacement for active manual security testing. Both scan results must be interpreted within their respective runtime configurations.
