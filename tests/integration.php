<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

final class TestBrowser
{
    private string $cookieFile;

    public function __construct(private readonly string $baseUrl)
    {
        $jar = tempnam(sys_get_temp_dir(), 'feedback_cookie_');
        if ($jar === false) {
            throw new RuntimeException('Cannot initialise test cookie jar.');
        }
        $this->cookieFile = $jar;
    }

    public function __destruct()
    {
        if (is_file($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    /** @return array{status:int, headers:string, body:string} */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /** @param array<string,string> $data
     *  @return array{status:int, headers:string, body:string}
     */
    public function post(string $path, array $data): array
    {
        return $this->request('POST', $path, $data);
    }

    /** @param array<string,string> $data
     *  @return array{status:int, headers:string, body:string}
     */
    private function request(string $method, string $path, array $data = []): array
    {
        $curl = curl_init();
        if ($curl === false) {
            throw new RuntimeException('Could not start HTTP request.');
        }

        $options = [
            CURLOPT_URL => rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => 'FeedbackPortalLocalIntegrationTest/1.0',
        ];
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($data);
        } else {
            $options[CURLOPT_HTTPGET] = true;
        }
        curl_setopt_array($curl, $options);
        $raw = curl_exec($curl);
        if ($raw === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('HTTP request failed: ' . $error);
        }
        $headerLength = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return [
            'status' => $status,
            'headers' => substr($raw, 0, $headerLength),
            'body' => substr($raw, $headerLength),
        ];
    }
}

function expect(bool $condition, string $description): void
{
    if (!$condition) {
        throw new RuntimeException('FAILED: ' . $description);
    }
    echo 'PASS: ' . $description . "\n";
}

/** @param array{status:int,headers:string,body:string} $response */
function expectStatus(array $response, int $status, string $description): void
{
    expect($response['status'] === $status, $description . ' (HTTP ' . $response['status'] . ')');
}

function csrf(string $html): string
{
    if (!preg_match('/name="csrf_token"\s+value="([a-f0-9]{64})"/', $html, $match)) {
        throw new RuntimeException('CSRF token was not found in HTML response.');
    }
    return $match[1];
}

function registerAccount(TestBrowser $browser, string $username, string $password): void
{
    $page = $browser->get('register.php');
    expectStatus($page, 200, "Registration form loads for $username");
    $response = $browser->post('register.php', [
        'csrf_token' => csrf($page['body']),
        'username' => $username,
        'password' => $password,
    ]);
    expect($response['status'] === 200 && str_contains($response['body'], 'Registration successful'), "Registration works for $username");
}

function loginAccount(TestBrowser $browser, string $username, string $password): void
{
    $page = $browser->get('login.php');
    expectStatus($page, 200, "Login form loads for $username");
    $response = $browser->post('login.php', [
        'csrf_token' => csrf($page['body']),
        'username' => $username,
        'password' => $password,
    ]);
    expectStatus($response, 303, "Login succeeds for $username");
    expectStatus($browser->get('feedback.php'), 200, "Authenticated dashboard works for $username");
}

function scalar(mysqli $db, string $sql): string
{
    $result = $db->query($sql);
    $value = $result->fetch_row()[0] ?? '';
    $result->free();
    return (string) $value;
}

function baselineLogin(TestBrowser $browser, string $username, string $password): void
{
    $response = $browser->post('login.php', ['username' => $username, 'password' => $password]);
    expectStatus($response, 302, "Insecure baseline login works for $username");
}

$secureUrl = getenv('SECURE_URL') ?: 'http://127.0.0.1:8090';
$baselineUrl = getenv('BASELINE_URL') ?: 'http://127.0.0.1:8091';
$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'anonymous_feedback';
$dbUser = getenv('DB_USER') ?: '';
$dbPassword = getenv('DB_PASSWORD') ?: '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli($host, $dbUser, $dbPassword, $dbName);
$db->set_charset('utf8mb4');

$password = 'Correct-Horse-Feedback-2026!';
$a = new TestBrowser($secureUrl);
$b = new TestBrowser($secureUrl);
$admin = new TestBrowser($secureUrl);

try {
    echo "SECURE APPLICATION: integration and adversarial regression tests\n";
    $public = $a->get('index.php');
    expectStatus($public, 200, 'Home page loads');
    expect(stripos($public['headers'], 'content-security-policy:') !== false, 'CSP header is present');
    expect(stripos($public['headers'], 'x-content-type-options: nosniff') !== false, 'MIME sniffing is disabled');
    expect(stripos($public['headers'], 'x-frame-options: DENY') !== false, 'Framing is prohibited');

    $loginForm = $a->get('login.php');
    expectStatus($loginForm, 200, 'Public login form loads');
    expect(stripos($loginForm['headers'], 'httponly') !== false, 'Session cookie has HttpOnly');
    expect(stripos($loginForm['headers'], 'samesite=lax') !== false, 'Session cookie has SameSite=Lax');
    expectStatus($a->post('login.php', ['username' => 'anyone', 'password' => 'no-token']), 403, 'Login without a CSRF token is rejected');

    registerAccount($a, 'audit_user_a', $password);
    registerAccount($b, 'audit_user_b', $password);
    registerAccount($admin, 'audit_admin', $password);
    $db->query("UPDATE users SET role = 'admin' WHERE username = 'audit_admin'");

    loginAccount($a, 'audit_user_a', $password);
    loginAccount($b, 'audit_user_b', $password);
    loginAccount($admin, 'audit_admin', $password);

    $aForm = $a->get('submit_feedback.php');
    expectStatus($aForm, 200, 'Authenticated submission form loads');
    expectStatus($a->post('submit_feedback.php', ['subject' => 'NoToken', 'message' => 'Must not save']), 403, 'Submission without CSRF token is denied');

    $payload = '<script>alert("local-security-test")</script>';
    $postA = $a->post('submit_feedback.php', [
        'csrf_token' => csrf($aForm['body']),
        'subject' => 'A-private-security-test',
        'message' => $payload,
    ]);
    expect($postA['status'] === 200 && str_contains($postA['body'], 'submitted successfully'), 'User A can create feedback');

    $bForm = $b->get('submit_feedback.php');
    $postB = $b->post('submit_feedback.php', [
        'csrf_token' => csrf($bForm['body']),
        'subject' => 'B-private-security-test',
        'message' => 'Only account B should own this record.',
    ]);
    expect($postB['status'] === 200 && str_contains($postB['body'], 'submitted successfully'), 'User B can create feedback');

    $aId = (int) scalar($db, "SELECT id FROM feedback WHERE subject = 'A-private-security-test'");
    $bId = (int) scalar($db, "SELECT id FROM feedback WHERE subject = 'B-private-security-test'");
    expect($aId > 0 && $bId > 0 && $aId !== $bId, 'Both accounts have distinct persistent records');

    $historyA = $a->get('feedback_history.php');
    expectStatus($historyA, 200, 'User A can open personal history');
    expect(str_contains($historyA['body'], 'A-private-security-test') && !str_contains($historyA['body'], 'B-private-security-test'), 'User A history excludes User B records');
    expect(str_contains($historyA['body'], '&lt;script&gt;') && !str_contains($historyA['body'], '<script>'), 'Stored script is rendered as text, not executable HTML');

    $historyB = $b->get('feedback_history.php');
    expect(str_contains($historyB['body'], 'B-private-security-test') && !str_contains($historyB['body'], 'A-private-security-test'), 'User B history excludes User A records');
    expectStatus($a->get('edit_feedback.php?id=' . $bId), 404, 'User A cannot read User B feedback by ID');
    expectStatus($a->get('edit_feedback.php?id=invalid'), 400, 'Malformed feedback identifiers are rejected');

    $editOwn = $a->get('edit_feedback.php?id=' . $aId);
    expectStatus($editOwn, 200, 'User A can access own edit form');
    expectStatus($a->post('edit_feedback.php?id=' . $aId, [
        'csrf_token' => '0000000000000000000000000000000000000000000000000000000000000000',
        'subject' => 'AlteredWithoutAuthorization',
        'message' => 'This must not save',
    ]), 403, 'Tampered edit token is rejected');

    expectStatus($a->post('edit_feedback.php?id=' . $aId, [
        'csrf_token' => csrf($editOwn['body']),
        'subject' => 'A-updated-security-test',
        'message' => 'Updated by rightful owner',
    ]), 303, 'Owner can update own feedback');
    expect(scalar($db, "SELECT subject FROM feedback WHERE id = $aId") === 'A-updated-security-test', 'Database reflects authorized edit');
    expectStatus($a->post('edit_feedback.php?id=' . $bId, [
        'csrf_token' => csrf($editOwn['body']),
        'subject' => 'A-tried-to-change-B',
        'message' => 'Should not save',
    ]), 404, 'Cross-account edit mutation is denied');
    expect(scalar($db, "SELECT subject FROM feedback WHERE id = $bId") === 'B-private-security-test', 'Cross-account edit did not alter record');

    $deleteToken = csrf($a->get('feedback_history.php')['body']);
    expectStatus($a->post('delete_feedback.php', [
        'feedback_id' => (string) $bId,
        'csrf_token' => $deleteToken,
    ]), 303, 'Cross-account delete request has non-disclosing response');
    expect(scalar($db, "SELECT COUNT(*) FROM feedback WHERE id = $bId") === '1', 'Cross-account delete did not remove User B record');

    $deleteToken = csrf($a->get('feedback_history.php')['body']);
    expectStatus($a->post('delete_feedback.php', [
        'feedback_id' => (string) $aId,
        'csrf_token' => 'bad-token',
    ]), 403, 'Delete with invalid CSRF token is rejected');
    expect(scalar($db, "SELECT COUNT(*) FROM feedback WHERE id = $aId") === '1', 'Denied delete preserved User A record');
    expectStatus($a->post('delete_feedback.php', [
        'feedback_id' => (string) $aId,
        'csrf_token' => $deleteToken,
    ]), 303, 'Owner can delete own record');
    expect(scalar($db, "SELECT COUNT(*) FROM feedback WHERE id = $aId") === '0', 'Authorized delete removed User A record');

    expectStatus($a->get('admin.php'), 403, 'Ordinary users cannot open admin panel');
    expectStatus($a->get('search_feedback.php'), 403, 'Ordinary users cannot open admin search');
    expectStatus($admin->get('admin.php'), 200, 'Administrator can open admin panel');

    $search = $admin->get('search_feedback.php?search=B-private-security-test');
    expect($search['status'] === 200 && str_contains($search['body'], 'B-private-security-test'), 'Administrator search returns a legitimate match');
    $injection = $admin->get('search_feedback.php?search=' . rawurlencode("' OR 1=1 #"));
    expect($injection['status'] === 200 && str_contains($injection['body'], 'No matching feedback found'), 'SQL-injection string is treated as search data');

    $brute = new TestBrowser($secureUrl);
    $bruteForm = $brute->get('login.php');
    $bruteToken = csrf($bruteForm['body']);
    for ($i = 0; $i < 5; $i++) {
        $attempt = $brute->post('login.php', [
            'csrf_token' => $bruteToken,
            'username' => 'audit_user_b',
            'password' => 'incorrect-password',
        ]);
        expectStatus($attempt, 200, 'Incorrect login attempt ' . ($i + 1) . ' handled');
    }
    $blocked = $brute->post('login.php', [
        'csrf_token' => $bruteToken,
        'username' => 'audit_user_b',
        'password' => $password,
    ]);
    expectStatus($blocked, 429, 'Sixth attempt is blocked in the same session');

    $logoutPage = $a->get('feedback.php');
    expectStatus($a->post('logout.php', ['csrf_token' => csrf($logoutPage['body'])]), 303, 'Logout succeeds using POST and CSRF token');
    expectStatus($a->get('feedback.php'), 302, 'Old session cannot access private page after logout');

    echo "\nINSECURE BASELINE: reproducible, isolated demonstrations\n";
    $baselineUserA = new TestBrowser($baselineUrl);
    $baselineUserB = new TestBrowser($baselineUrl);
    $baselineAdmin = new TestBrowser($baselineUrl);
    baselineLogin($baselineUserA, 'audit_user_a', $password);
    baselineLogin($baselineUserB, 'audit_user_b', $password);
    baselineLogin($baselineAdmin, 'audit_admin', $password);

    $idor = $baselineUserA->get('edit_feedback.php?id=' . $bId);
    expect($idor['status'] === 200 && str_contains($idor['body'], 'B-private-security-test'), 'Baseline exhibits IDOR on feedback edit');

    $baselineXss = $baselineUserB->post('submit_feedback.php', [
        'subject' => 'Baseline-XSS-Test',
        'message' => '<script>alert("baseline-only")</script>',
    ]);
    expectStatus($baselineXss, 200, 'Baseline accepts feedback test data');
    $baselineHistory = $baselineUserB->get('feedback_history.php');
    expect(str_contains($baselineHistory['body'], '<script>alert("baseline-only")</script>'), 'Baseline reproduces stored XSS');

    $baselineInjection = $baselineAdmin->get('search_feedback.php?search=' . rawurlencode("' OR 1=1 #"));
    expect($baselineInjection['status'] === 200 && str_contains($baselineInjection['body'], 'B-private-security-test'), 'Baseline reproduces SQL injection in administrator search');

    echo "\nAll executed HTTP/database security regression checks passed.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
