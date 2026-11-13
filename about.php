<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = "About - UNSAID";
$basePath = "";
$bgMode = 'scroll';
$bgImages = [10, 3, 6];
include __DIR__ . '/includes/header.php';
?>

<article class="about-page">
    <section class="about-block about-intro" data-bg="10">
        <div class="section-inner">
            <h1 class="page-title">About UNSAID</h1>
            <p class="page-subtitle">A simple idea: give people a safe place to say what's on their mind, anonymously.</p>
        </div>
    </section>

    <section class="about-block" data-bg="3">
        <div class="section-inner narrow about-prose">
            <p class="about-lede">
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
        </div>
    </section>

    <section class="about-block about-close" data-bg="6">
        <div class="section-inner narrow about-prose">

            <p>
                UNSAID was developed as a required academic project (WEB SYSTEMS, WS1). BTW I'm from section
                OLSA211A002SC. The core system was built in 3 days, followed by
                2 additional days of polishing, feature enhancements, and improvements.
                From a working system to a more refined experience, the entire project
                took just 5 days to develop.
            </p>

            <p>
                More than just a school requirement, UNSAID was built to explore how a simple
                web application can make anonymous communication easier, more accessible, and
                more engaging. Every feature is designed to keep the experience straightforward
                while giving users more control over their inbox.
            </p>

            <p>
                UNSAID is built to be simple, fast, and honest about what it is: a quiet inbox
                for the things people couldn't say to your face. Compliments, confessions,
                feedback, random thoughts, or whatever kabaliwan they decided to send at
                2 AM. It's yours. Good luck.
            </p>

            <!-- https://facebook.com/zacuia -->

            <p class="about-credit">Made with ❤️ by John David C. Zamora | <a href="https://i.pinimg.com/1200x/32/60/47/3260474a82e1c708cd7a6764bbd3db00.jpg" target="_blank" rel="noopener noreferrer">@Zacuia</a></p>

            <div class="about-social">
                <a href="https://facebook.com/zacuia" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://vk.ru/zacuia" target="_blank" rel="noopener noreferrer" aria-label="VK"><i class="fab fa-vk"></i></a>
                <a href="https://t.me/zacuia" target="_blank" rel="noopener noreferrer" aria-label="Telegram"><i class="fab fa-telegram-plane"></i></a>
            </div>
        </div>
    </section><br><br><br><br>
</article>

<?php include __DIR__ . '/includes/footer.php'; ?>
