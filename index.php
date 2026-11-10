<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;
$name = '';
$email = '';
$subject = '';
$message = '';

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    $token = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    }
    if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 80) {
        $errors[] = 'Please enter your name (2–80 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($subject === '' || mb_strlen($subject) > 120) {
        $errors[] = 'Please enter a subject (up to 120 characters).';
    }
    if ($message === '' || mb_strlen($message) < 10 || mb_strlen($message) > 3000) {
        $errors[] = 'Your message must be between 10 and 3,000 characters.';
    }

    if (!$errors) {
        $record = [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'submitted_at' => date(DATE_ATOM),
        ];
        $file = __DIR__ . '/storage/messages.json';
        $existing = [];
        if (is_file($file)) {
            $decoded = json_decode((string)file_get_contents($file), true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }
        $existing[] = $record;
        $saved = file_put_contents(
            $file,
            json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
            LOCK_EX
        );

        if ($saved === false) {
            $errors[] = 'Sorry, the message could not be saved. Please try again later.';
        } else {
            $success = true;
            $name = $email = $subject = $message = '';
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A clean, responsive PHP contact form project.">
    <title>Let's Connect — PHP Contact Form</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="page-shell">
        <section class="contact-card">
            <div class="visual-panel">
                <div class="brand"><span class="brand-mark">R</span><span>Rizwan<span class="brand-light">.dev</span></span></div>
                <div class="visual-copy">
                    <span class="eyebrow"><span class="dot"></span> OPEN TO OPPORTUNITIES</span>
                    <h1>Have a project<br>in <em>mind?</em></h1>
                    <p>Have a question, an idea, or an opportunity? Send a message. I’d love to hear from you.</p>
                </div>
                <div class="visual-footer">
                    <div class="orb orb-one"></div><div class="orb orb-two"></div>
                    <span>BUILDING IDEAS INTO REALITY</span>
                    <span class="footer-line"></span>
                </div>
            </div>

            <div class="form-panel">
                <div class="form-heading">
                    <span class="eyebrow dark-eyebrow">CONTACT FORM <span class="heading-line"></span></span>
                    <h2>Let’s talk<span>.</span></h2>
                    <p>Fill in the details below and I’ll get back to you.</p>
                </div>

                <?php if ($success): ?>
                    <div class="notice success" role="status">✓ Your message has been saved successfully. Thank you for reaching out!</div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="notice error" role="alert">
                        <strong>Please check the following:</strong>
                        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <div class="field-row">
                        <label class="field">
                            <span>Your name</span>
                            <input type="text" name="name" placeholder="e.g. Alex Morgan" value="<?= e($name) ?>" maxlength="80" required>
                        </label>
                        <label class="field">
                            <span>Email address</span>
                            <input type="email" name="email" placeholder="alex@example.com" value="<?= e($email) ?>" maxlength="190" required>
                        </label>
                    </div>
                    <label class="field">
                        <span>Subject</span>
                        <input type="text" name="subject" placeholder="What would you like to discuss?" value="<?= e($subject) ?>" maxlength="120" required>
                    </label>
                    <label class="field">
                        <span>Your message</span>
                        <textarea name="message" rows="5" placeholder="Tell me a little about it..." maxlength="3000" required><?= e($message) ?></textarea>
                    </label>
                    <button class="submit-btn" type="submit">Send message <span aria-hidden="true">↗</span></button>
                    <p class="privacy-note">Your details are used only to respond to your message.</p>
                </form>
            </div>
        </section>
        <footer class="site-footer"><span>© <?= date('Y') ?> Rizwan.dev</span><span>Designed with care · Powered by PHP</span></footer>
    </main>
</body>
</html>
