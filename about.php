<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = "About - UNSAID";
$basePath = "";
include __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="section-inner">
        <h1 class="page-title">About UNSAID</h1>
        <p class="page-subtitle">A simple idea: give people a safe place to say what's on their mind, anonymously.</p>
    </div>
</section>

<section class="about-content">
    <div class="section-inner narrow">
        <p>
            UNSAID started from a simple observation: most honest things stay unsaid.
            Not because people don't want to say them, but because saying them out loud
            can feel risky, awkward, or just straight up nakakahiya.
        </p>

        <p>
            So I (AKO LANG NGANIIII),  built a place for that. You get your own personal link, then share it
            anywhere you want, sa bio, group chat, class, friends, kahit saan. Anyone who
            opens your link can send you a message without logging in, without a username,
            and without their name attached. Walang account. Walang pangalan. Walang hiya.
        </p>

        <p>
            You decide what happens next. Read it, keep it, print it, or ignore it like
            your responsibilities. Every message stays private to you. I don't show
            senders' identities to anyone, and I don't build tools to unmask them either.
            Sorry FBI, wala dito.
        </p>

        <p>
            UNSAID is built to be simple, fast, and honest about what it is: a quiet inbox
            for the things people couldn't say to your face. Compliments, confessions,
            feedback, random thoughts, or whatever kabaliwan they decided to send at
            2 AM. It's yours. Good luck.
        </p>
        <br>
        <br>
        <a href="register.php" class="btn btn-primary">Create your link</a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
