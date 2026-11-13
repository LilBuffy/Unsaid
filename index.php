<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = "UNSAID - Say what you can't say.";
$basePath = "";
$bgMode = 'scroll';
$bgImages = [7, 9, 8, 5];
include __DIR__ . '/includes/header.php';
?>

<header class="hero" data-bg="7">
    <div class="hero-inner">
        <div class="hero-content">
            <span class="hero-tag">Anonymous. Honest. Bahala Ka.</span>
            <h1 class="hero-title">UNSAID</h1>
            <p class="hero-line">Say what you can't say.</p>
            <p class="hero-desc">Make your anonymous inbox, drop the link, and let people say what they really think. Walang account. Walang pangalan. Walang hiya. Bahala ka na sa emotional damage.</p>
            <div class="hero-actions">
                <a href="register.php" class="btn btn-primary">Create your link</a>
                <a href="login.php" class="btn btn-ghost">Login</a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="floating-card floating-card-1">
                <span>Anonymous</span>
                <p>"May sasabihin sana ako, pero nakalimutan ko."</p>
                <small>Received 2m ago</small>
            </div>
            <div class="floating-card floating-card-2">
                <span>Anonymous</span>
                <p>"HOY! YUNG MGA BARKADA MO NANGBABATO SA BAHAY NAMIN!"</p>
                <small>Received 1h ago</small>
            </div>
            <div class="floating-card floating-card-3">
                <span>Anonymous</span>
                <p>"Wala kanin buseng?! P*tnginang h*yop na yan!"</p>
                <small>Received 3h ago</small>
            </div>
        </div>
    </div>
</header>

<section class="features" id="features" data-bg="9">
    <div class="section-inner features-layout">
        <div class="features-intro">
            <span class="section-eyebrow">Features</span>
            <h2 class="section-title">Everything you need, nothing you don't</h2>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <h3>Anonymous messages</h3>
                <p>Anyone with your link can send you a message. No account needed, no name, no hiya.</p>
            </div>
            <div class="feature-card">
                <h3>Your own link</h3>
                <p>A personal profile page you can share anywhere, sa bio, socials, group chats, kahit saan.</p>
            </div>
            <div class="feature-card">
                <h3>Custom profile</h3>
                <p>Set your own title, placeholder, and button text. Gawin mong sariling style mo, bahala ka sa trip mo.</p>
            </div>
            <div class="feature-card">
                <h3>Attachments</h3>
                <p>Senders can add an image, GIF, or link to their message. Bawal video kasi baka BOLD YON.</p>
            </div>
            <div class="feature-card">
                <h3>Message management</h3>
                <p>Mark messages as read or unread, delete the ones you don't want, and keep your inbox clean. Walang kalat, parang buhay mo sana.</p>
            </div>
            <div class="feature-card">
                <h3>Search &amp; filter</h3>
                <p>Find exactly what you're looking for across your inbox. Kasi may mga bagay na hindi dapat nakakalimutan.</p>
            </div>
        </div>
    </div>
</section>

<section class="how-it-works" id="how-it-works" data-bg="8">
    <div class="section-inner">
        <span class="section-eyebrow">Getting started</span>
        <h2 class="section-title">How it works</h2>
        <div class="steps">
            <div class="step">
                <span class="step-number">01</span>
                <h3>Create your account</h3>
                <p>Sign up in seconds and get your own UNSAID profile. Saglit lang ’to, hindi ka naman mag-aapply sa gobyerno.</p>
            </div>
            <div class="step">
                <span class="step-number">02</span>
                <h3>Share your link</h3>
                <p>Share your profile link anywhere. I-post mo, ikalat mo, bahala ka. Basta may makakita, may magsesend. At sana mentally prepared ka.</p>
            </div>
            <div class="step">
                <span class="step-number">03</span>
                <h3>Receive honest messages</h3>
                <p>Anyone can send you a message anonymously. Walang account, walang pangalan, walang hiyaan dito lods.</p>
            </div>
            <div class="step">
                <span class="step-number">04</span>
                <h3>Read them your way</h3>
                <p>Manage, search, and print the messages that actually matter to you. Oo, pati yung screenshots na gusto mong gawing ebidensya.</p>
            </div>
        </div>
    </div>
</section>

<section class="about-teaser" data-bg="5">
    <div class="section-inner about-inner">
        <div class="about-copy">
            <span class="section-eyebrow">The idea</span>
            <h2 class="section-title">Why UNSAID exists</h2>
            <p class="about-text">May mga bagay na mas madaling i-type kaysa sabihin sa mukha mo. UNSAID gives people a quiet place to say what they really think, compliments, confessions, feedback, o kung ano mang kabobohang matagal nilang kinikimkim. Anonymous naman. Bahala na si Batman.</p>
        </div>
        <div class="about-visual" aria-hidden="true"><img src="assets/aboutimage/about-image.png" alt="UNSAID"></div>
    </div>
    <div class="section-inner about-more"><br>
        <a href="about.php" class="btn btn-outline">Read more</a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
