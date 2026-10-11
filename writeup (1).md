# Locker: Predictable password-reset tokens

**Location:** The reset_token_for() and consume_reset_token() functions in src/crypto.php, called by POST /reset in public/index.php.

**Problem:** Reset tokens are calculated from an email address and the current UTC date. The app accepts this predictable token even without a reset-token row in SQLite.

**Impact:** An unauthenticated visitor can change another account's password without knowing its current password or receiving a reset email. In the local test, resetting advisor@campus.edu allowed sign-in and exposed the advisor's private vault secret, LOCKER-4410.

**Live Demo:** Not deployed yet.

## Steps to reproduce:

Use the original application with a fresh, disposable database. These steps change the seeded advisor account's password.

1. From the original Locker folder, run `DATABASE_PATH=./data/reproduction.db php -S 127.0.0.1:3000 -t public public/router.php` and open http://localhost:3000.
2. Do not click “Forgot password” or sign in.
3. Calculate the advisor's token using `php -r 'echo sha1("advisor@campus.edu|" . gmdate("Y-m-d")), PHP_EOL;'`.
4. Open http://localhost:3000/reset, enter advisor@campus.edu, paste the calculated token, and set the password to Updated123!.
5. Sign in as advisor@campus.edu with Updated123! and observe the advisor hold containing LOCKER-4410.

**Expected behavior:** A password change requires a valid reset token actually issued for that account. An email address and the date should not authorize a reset.

**Observed behavior before the patch:**

The advisor had no reset-token row. An incorrect token was rejected, but the calculated token changed the password without /forgot. Signing in displayed LOCKER-4410. The token could also be reused.

**Cause:** The app treats a publicly reproducible hash as proof that a user received a reset link. The missing-record fallback accepts that hash even when no link was issued. Despite its name, consume_reset_token() does not delete the token.

## Code Examples

### Vulnerable Code (Before Patch)

In src/crypto.php:

```php
function reset_token_for(string $email, ?string $day = null): string
{
    $day = $day ?? gmdate("Y-m-d");
    return sha1(strtolower($email) . "|" . $day);
}

function consume_reset_token(string $email, string $token): bool
{
    $expected = reset_token_for($email);
    if (!hash_equals($expected, $token)) {
        return false;
    }
    $pdo = db();
    $row = $pdo->prepare("SELECT token FROM reset_tokens WHERE email = ?");
    $row->execute([$email]);
    $stored = $row->fetchColumn();
    if (!$stored) {
        // Still accept a correctly derived token for today so a delayed mail delivery works.
        return true;
    }
    return hash_equals((string)$stored, $token);
}
```

The token has no secret input. The `return true` fallback accepts it without prior issuance. public/index.php then calls set_user_password() for the supplied email.

### Patched Code (After Fix)

In src/crypto.php:

```php
function issue_reset_token(string $email): string
{
    $token = bin2hex(random_bytes(32));
    $pdo = db();
    $pdo->prepare(
        "INSERT INTO reset_tokens (email, token, created_at) VALUES (?, ?, datetime('now'))
         ON CONFLICT(email) DO UPDATE SET token = excluded.token, created_at = excluded.created_at"
    )->execute([$email, hash("sha256", $token)]);
    return $token;
}

function consume_reset_token(string $email, string $token): bool
{
    if (!preg_match('/\A[0-9a-f]{64}\z/', $token)) {
        return false;
    }
    // Delete only an issued, matching token from today (SQLite dates use UTC).
    $stmt = db()->prepare(
        "DELETE FROM reset_tokens
         WHERE email = ? AND token = ?
           AND created_at >= date('now') AND created_at < date('now', '+1 day')"
    );
    $stmt->execute([$email, hash("sha256", $token)]);
    return $stmt->rowCount() === 1;
}
```

The patch stores a random token's SHA-256 digest. Reset requires a matching email and current-day record. Deleting it prevents reuse; reissuing replaces the previous token.

The reset route in public/index.php also wraps token consumption and the password update in a transaction:

```php
    $pdo = db();
    $pdo->beginTransaction();
    try {
        if (!consume_reset_token($email, $token)) {
            $pdo->rollBack();
            render("reset", ["title" => "Choose a new password", "email" => $email, "token" => $token, "error" => "That reset token is not valid for this address today."]);
        }
        set_user_password($email, $password);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
```

If the password update fails, the transaction restores the token instead of consuming it without changing the password.

**Fix:** Replaced deterministic tokens with random, account-bound, same-day tokens stored as digests. Removed the missing-record acceptance and added single-use redemption with a transactional password update.

**Observed behavior after the patch:**

The calculated advisor token was rejected, and the attempted replacement password could not sign in. A valid issued token changed the password once; replay, another account's token, and previous-day tokens were rejected.

## Exact test requests

Run from the application folder against disposable original and patched databases.

**Calculate the original token:**

```bash
TOKEN=$(php -r 'echo sha1("advisor@campus.edu|" . gmdate("Y-m-d"));')
```

**Reset without requesting a link first:**

```bash
curl --data-urlencode 'email=advisor@campus.edu' \
  --data-urlencode "token=$TOKEN" \
  --data-urlencode 'password=Updated123!' \
  http://localhost:3000/reset
```

**Sign in with the attempted replacement password:**

```bash
curl -L -c cookies.txt -b cookies.txt \
  --data-urlencode 'email=advisor@campus.edu' \
  --data-urlencode 'password=Updated123!' \
  http://localhost:3000/login
```

**Valid reset on the patched local application:**

Campus mail is a stub. Issue a local test token through PHP CLI using the server's DATABASE_PATH:

```bash
php -r 'require "src/bootstrap.php"; echo issue_reset_token("devon@campus.edu"), PHP_EOL;'
```

Paste the returned token into /reset with devon@campus.edu and a new password. The database contains its digest, which cannot be used as the token.

## Hosted testing

No deployment was tested. Results came from local PHP/SQLite instances with disposable databases. Campus-mail delivery still needs implementation.

## Normal functionality checks:

| Test | Actual result |
|---|---|
| Existing demo sign-in | Maya signed in and saw her seeded secrets. An incorrect password returned HTTP 401. |
| Forged or missing-record token | Reset was rejected; the attempted advisor password could not sign in. |
| Forgot-password request | Known and unknown emails returned the same notice; only the known account received a token row. |
| Token issuance and replacement | Tokens differed, only their digests were stored, and issuing again invalidated the previous token. |
| Valid same-day reset | Password changed, the token row disappeared, the old password failed, and the new password worked. |
| Replay, account mismatch, and expiry | Used, wrong-account, previous-day, future-day, and legacy tokens were rejected. |
| Short password | Rejected without consuming the valid token. |
| Failed password update | Token deletion rolled back; the same token worked after removing the simulated failure. |
| Concurrent token redemption | Exactly one of two database connections redeemed the token. |
| Register, sign in, and sign out | .edu registration worked; other email domains were rejected. Signing out hid secrets and blocked protected pages. |
| Create and view a secret | The saved secret appeared and remained after refresh and a new sign-in. |
| Vault ownership | Devon could not see Maya's, the advisor's, or the new student's secrets. |

## Summary

The predictable password-reset vulnerability in Locker has been patched. The fix replaces tokens derived from public information with random tokens that must match an issued database record.

**Key improvements:**

- Reset tokens cannot be calculated from an email address and date.
- Each token belongs to one account and expires at the end of its UTC calendar day.
- Successful reset consumes the token, preventing reuse.
- The transaction keeps token consumption and the password update together.

**Verification status:**

All 38 automated local checks passed: PHP syntax, original-app reproduction, reset acceptance and rejection, and normal functionality. These were programmatic HTTP and database checks, not manual browser tests.

The patch preserves existing sign-in and vault ownership behavior. Passwords still use the original unsalted SHA-256 scheme; upgrading password storage is separate work. This report covers the supplied Locker-student.zip source and this reset defect, without claiming that broader application security has been assessed.
