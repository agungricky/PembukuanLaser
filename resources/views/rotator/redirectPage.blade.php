<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description"
        content="Emblem sound custom akrilik dengan UV DTF timbul. Bisa pakai logo sendiri, edit gratis, kuat menempel, tahan panas dan hujan." />
    <title>Emblem Sound Custom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Meta Pixel Code -->
    <script>
        ! function(f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function() {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');

        fbq('init', '1234805588838630');

        fbq('track', 'PageView');

        fbq('track', 'ViewContent', {
            content_name: 'Emblem Sound Custom'
        });
    </script>

    <noscript>
        <img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=1234805588838630&ev=PageView&noscript=1" />
    </noscript>
    <!-- End Meta Pixel Code -->
    <style>
        :root {
            --paper: #f6f7fb;
            --white: #ffffff;
            --ink: #12141a;
            --muted: #66707d;
            --line: #dfe4eb;
            --cyan: #00cfe8;
            --cyan-2: #8ff6ff;
            --magenta: #e214ff;
            --lime: #c7ff1f;
            --orange: #ff7a00;
            --wa: #20d466;
            --wa-dark: #0e9e45;
            --shadow: 0 22px 55px rgba(20, 28, 40, .12);
            --max: 1180px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        html {
            scroll-behavior: smooth
        }

        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                linear-gradient(135deg, rgba(0, 207, 232, .035) 25%, transparent 25%) 0 0/54px 54px,
                linear-gradient(315deg, rgba(226, 20, 255, .025) 25%, transparent 25%) 0 0/54px 54px,
                var(--paper);
            line-height: 1.55;
            padding-bottom: 88px;
            overflow-x: hidden;
        }

        img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover
        }

        a {
            text-decoration: none;
            color: inherit
        }

        .container {
            width: min(var(--max), calc(100% - 36px));
            margin: auto
        }

        .section {
            padding: 88px 0;
            position: relative
        }

        .center {
            text-align: center;
            margin-inline: auto
        }

        /* RACING TYPOGRAPHY */
        .kicker {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-size: 12px;
            letter-spacing: .16em;
            text-transform: uppercase;
            font-weight: 950;
            color: #131722;
            margin-bottom: 14px;
        }

        .kicker:before {
            content: "";
            width: 34px;
            height: 8px;
            background: linear-gradient(90deg, var(--cyan), var(--magenta));
            clip-path: polygon(0 0, 100% 0, 78% 100%, 0 100%)
        }

        .section-title {
            font-size: clamp(34px, 5vw, 60px);
            line-height: 1.02;
            letter-spacing: -.05em;
            font-weight: 950;
            max-width: 840px;
        }

        .section-title em {
            font-style: italic;
            color: var(--magenta)
        }

        .section-desc {
            color: var(--muted);
            font-size: 17px;
            max-width: 720px;
            margin-top: 16px
        }

        /* BUTTONS */
        .btn {
            position: relative;
            isolation: isolate;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 58px;
            padding: 0 26px;
            font-size: 14px;
            font-weight: 950;
            letter-spacing: .01em;
            border: 0;
            transition: .2s ease;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        }

        .btn:hover {
            transform: translateY(-3px)
        }

        .btn-wa {
            background: var(--wa);
            color: #062a14;
            box-shadow: 0 14px 28px rgba(32, 212, 102, .26)
        }

        .btn-wa:hover {
            background: #31e878
        }

        .btn-secondary {
            background: #fff;
            color: #111;
            border: 1px solid #cfd5dd;
            box-shadow: 0 9px 24px rgba(20, 30, 45, .08)
        }

        .btn-lg {
            min-height: 66px;
            padding: 0 32px;
            font-size: 17px
        }

        /* HERO */
        .hero {
            position: relative;
            overflow: hidden;
            padding: 66px 0 58px;
            background:
                linear-gradient(115deg, transparent 0 52%, rgba(0, 207, 232, .09) 52% 59%, transparent 59%),
                linear-gradient(115deg, transparent 0 67%, rgba(226, 20, 255, .07) 67% 73%, transparent 73%),
                linear-gradient(#fff, #f4f6fa);
            border-bottom: 1px solid var(--line);
        }

        .hero:before {
            content: "SOUND  SOUND  SOUND  SOUND  SOUND";
            position: absolute;
            left: -70px;
            top: 30px;
            font-size: 110px;
            font-weight: 1000;
            font-style: italic;
            letter-spacing: -.06em;
            color: rgba(16, 19, 25, .035);
            white-space: nowrap;
            transform: rotate(-8deg);
            pointer-events: none;
        }

        .hero:after {
            content: "";
            position: absolute;
            right: -70px;
            bottom: -100px;
            width: 360px;
            height: 250px;
            background: repeating-linear-gradient(-55deg, var(--cyan) 0 12px, transparent 12px 26px);
            opacity: .12;
            transform: skewX(-16deg);
            pointer-events: none;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.02fr .98fr;
            gap: 54px;
            align-items: center;
            position: relative;
            z-index: 2
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 9px 13px;
            border: 1px solid #cbd2db;
            background: rgba(255, 255, 255, .75);
            backdrop-filter: blur(8px);
            font-size: 11px;
            font-weight: 950;
            letter-spacing: .12em;
            text-transform: uppercase;
            clip-path: polygon(8px 0, 100% 0, calc(100% - 8px) 100%, 0 100%);
            margin-bottom: 20px;
        }

        .hero-badge:before {
            content: "";
            width: 8px;
            height: 8px;
            background: var(--magenta);
            transform: skewX(-18deg)
        }

        .hero h1 {
            font-size: clamp(48px, 7vw, 84px);
            line-height: .91;
            letter-spacing: -.065em;
            font-weight: 1000;
            margin-bottom: 22px;
            max-width: 650px;
        }

        .hero h1 .line-accent {
            display: inline-block;
            position: relative;
            color: #111;
            font-style: italic;
            letter-spacing: 2px;
        }

        .hero h1 .line-accent:after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: 2px;
            height: 12px;
            z-index: -1;
            background: linear-gradient(90deg, var(--cyan), var(--lime), var(--magenta));
            clip-path: polygon(2% 22%, 100% 0, 96% 100%, 0 78%);
            opacity: .8;
        }

        .hero p {
            font-size: 18px;
            color: #56606e;
            max-width: 650px;
            margin-bottom: 23px
        }

        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-bottom: 28px
        }

        .chip {
            padding: 9px 12px;
            background: #fff;
            border: 1px solid #d8dde5;
            box-shadow: 0 7px 18px rgba(24, 31, 43, .05);
            font-size: 12px;
            font-weight: 900;
            clip-path: polygon(7px 0, 100% 0, calc(100% - 7px) 100%, 0 100%);
        }

        .chip b {
            color: var(--magenta)
        }

        .hero-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap
        }

        .micro {
            font-size: 12px;
            color: #7b8490;
            margin-top: 13px
        }

        .hero-visual {
            position: relative;
            padding: 16px 16px 18px 18px
        }

        .hero-visual:before {
            content: "";
            position: absolute;
            inset: -7px 28px 28px -14px;
            background: linear-gradient(135deg, var(--cyan), var(--magenta));
            clip-path: polygon(8% 0, 100% 0, 95% 94%, 0 100%);
            z-index: 0;
        }

        .hero-visual:after {
            content: "";
            position: absolute;
            right: -14px;
            top: 42px;
            width: 85px;
            height: 150px;
            background: repeating-linear-gradient(180deg, #111 0 8px, transparent 8px 17px);
            opacity: .14;
            transform: skewY(-18deg);
            z-index: 0;
        }

        .hero-card {
            position: relative;
            z-index: 1;
            height: 555px;
            overflow: hidden;
            background: #fff;
            clip-path: polygon(7% 0, 100% 0, 100% 91%, 93% 100%, 0 100%, 0 9%);
            box-shadow: var(--shadow);
            border: 7px solid #fff;
        }

        .hero-card img {
            position: absolute;
            inset: 0;
            object-position: center
        }

        .hero-card:after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 58%, rgba(5, 8, 12, .82) 100%);
            pointer-events: none
        }

        .hero-overlay {
            position: absolute;
            left: 24px;
            right: 24px;
            bottom: 22px;
            z-index: 2;
            color: #fff
        }

        .hero-overlay strong {
            display: block;
            font-size: 21px;
            font-style: italic;
            text-transform: uppercase;
            letter-spacing: -.02em;
            margin-bottom: 3px
        }

        .hero-overlay span {
            font-size: 13px;
            color: #e5e8ec
        }

        .hero-tag {
            position: absolute;
            right: -12px;
            top: 15px;
            z-index: 3;
            background: var(--lime);
            color: #111;
            font-size: 11px;
            font-weight: 1000;
            padding: 9px 20px 9px 14px;
            transform: rotate(4deg);
            box-shadow: 0 10px 24px rgba(0, 0, 0, .12);
            clip-path: polygon(8px 0, 100% 0, calc(100% - 8px) 100%, 0 100%);
        }

        /* TRUST STRIPE */
        .trust {
            position: relative;
            background: #fff;
            border-bottom: 1px solid var(--line);
            overflow: hidden;
        }

        .trust:before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 140px;
            background: var(--cyan);
            clip-path: polygon(0 0, 100% 0, 70% 100%, 0 100%);
            opacity: .12;
        }

        .trust-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            position: relative;
            z-index: 1
        }

        .trust-item {
            padding: 22px 18px;
            text-align: center;
            border-right: 1px solid #e2e6ec
        }

        .trust-item:last-child {
            border-right: none
        }

        .trust-item strong {
            display: block;
            font-size: 15px;
            font-weight: 950;
            font-style: italic;
            text-transform: uppercase
        }

        .trust-item span {
            font-size: 12px;
            color: #75808d
        }

        /* PROOF */
        .proof-zone {
            background: #f7f8fb
        }

        .proof-grid {
            display: grid;
            grid-template-columns: 1.25fr .75fr .75fr;
            grid-template-rows: 260px 260px;
            gap: 16px;
            margin-top: 38px
        }

        .proof-card {
            position: relative;
            overflow: hidden;
            background: #fff;
            border: 1px solid #dce2e9;
            box-shadow: 0 12px 32px rgba(28, 35, 48, .08);
            clip-path: polygon(14px 0, 100% 0, 100% calc(100% - 14px), calc(100% - 14px) 100%, 0 100%, 0 14px);
        }

        .proof-card.big {
            grid-row: 1/3
        }

        .proof-card:before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 100px;
            height: 7px;
            background: linear-gradient(90deg, var(--cyan), var(--magenta));
            z-index: 2
        }

        .proof-card .label {
            position: absolute;
            left: 14px;
            bottom: 14px;
            z-index: 2;
            background: rgba(255, 255, 255, .94);
            color: #111;
            border: 1px solid #d8dde4;
            padding: 8px 11px;
            font-size: 11px;
            font-weight: 950;
            text-transform: uppercase;
            box-shadow: 0 8px 22px rgba(0, 0, 0, .09);
            clip-path: polygon(6px 0, 100% 0, calc(100% - 6px) 100%, 0 100%);
        }

        /* CTA RACING STRIP */
        .cta-strip {
            position: relative;
            overflow: hidden;
            margin-top: 34px;
            padding: 26px 28px;
            background: #fff;
            border: 1px solid #d8dee6;
            box-shadow: 0 12px 34px rgba(24, 32, 44, .08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            clip-path: polygon(14px 0, 100% 0, 100% calc(100% - 14px), calc(100% - 14px) 100%, 0 100%, 0 14px);
        }

        .cta-strip:before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 9px;
            background: linear-gradient(var(--cyan), var(--magenta))
        }

        .cta-strip:after {
            content: "";
            position: absolute;
            right: 210px;
            top: -28px;
            width: 140px;
            height: 140px;
            background: repeating-linear-gradient(-55deg, rgba(226, 20, 255, .12) 0 9px, transparent 9px 18px);
            transform: skewX(-15deg)
        }

        .cta-strip>* {
            position: relative;
            z-index: 1
        }

        .cta-strip strong {
            font-size: 22px;
            font-weight: 950;
            font-style: italic;
            text-transform: uppercase;
            display: block
        }

        .cta-strip span {
            font-size: 14px;
            color: #6f7986
        }

        /* FEATURES */
        .feature-wrap {
            background:
                linear-gradient(135deg, transparent 0 76%, rgba(0, 207, 232, .08) 76% 82%, transparent 82%),
                #fff;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
            overflow: hidden;
        }

        .feature-wrap:before {
            content: " UV DTV • CUSTOM • AUDIO • EMBLEM •";
            position: absolute;
            left: -50px;
            bottom: 6px;
            font-size: 72px;
            font-weight: 1000;
            font-style: italic;
            letter-spacing: -.05em;
            color: rgba(15, 18, 24, .025);
            white-space: nowrap;
        }

        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-top: 40px;
            position: relative;
            z-index: 1
        }

        .feature {
            position: relative;
            padding: 26px;
            background: #fff;
            border: 1px solid #dfe4ea;
            box-shadow: 0 11px 28px rgba(27, 35, 49, .065);
            clip-path: polygon(13px 0, 100% 0, 100% calc(100% - 13px), calc(100% - 13px) 100%, 0 100%, 0 13px);
        }

        .feature:before {
            content: "";
            position: absolute;
            right: -10px;
            top: -12px;
            width: 70px;
            height: 45px;
            background: var(--lime);
            opacity: .16;
            transform: skewX(-24deg)
        }

        .feature:nth-child(2n):before {
            background: var(--magenta)
        }

        .feature:nth-child(3n):before {
            background: var(--cyan)
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            background: #12151b;
            color: #fff;
            margin-bottom: 18px;
            font-size: 21px;
            clip-path: polygon(9px 0, 100% 0, 100% calc(100% - 9px), calc(100% - 9px) 100%, 0 100%, 0 9px);
        }

        .feature h3 {
            font-size: 18px;
            font-weight: 950;
            font-style: italic;
            text-transform: uppercase;
            margin-bottom: 7px
        }

        .feature p {
            color: #6d7682;
            font-size: 14px
        }

        /* SHOWCASE */
        .showcase {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-top: 40px
        }

        .showcase-card {
            background: #fff;
            border: 1px solid #dbe1e8;
            overflow: hidden;
            box-shadow: 0 14px 38px rgba(25, 34, 47, .08);
            clip-path: polygon(18px 0, 100% 0, 100% calc(100% - 18px), calc(100% - 18px) 100%, 0 100%, 0 18px);
        }

        .showcase-card .image {
            height: 380px;
            position: relative;
            overflow: hidden
        }

        .showcase-card .image:after {
            content: "";
            position: absolute;
            inset: auto 0 0 0;
            height: 70px;
            background: linear-gradient(transparent, rgba(0, 0, 0, .14))
        }

        .showcase-card .copy {
            padding: 23px 24px 25px;
            border-top: 5px solid transparent;
            border-image: linear-gradient(90deg, var(--cyan), var(--magenta), var(--lime)) 1
        }

        .showcase-card h3 {
            font-size: 24px;
            font-weight: 950;
            font-style: italic;
            text-transform: uppercase;
            margin-bottom: 7px
        }

        .showcase-card p {
            font-size: 14px;
            color: #717a86
        }

        /* FINAL CTA */
        .final-section {
            padding-top: 28px
        }

        .final-box {
            position: relative;
            overflow: hidden;
            padding: 64px 50px;
            text-align: center;
            background: #fff;
            border: 1px solid #d8dee7;
            box-shadow: 0 24px 64px rgba(22, 32, 46, .11);
            clip-path: polygon(24px 0, 100% 0, 100% calc(100% - 24px), calc(100% - 24px) 100%, 0 100%, 0 24px);
        }

        .final-box:before {
            content: "";
            position: absolute;
            left: -90px;
            top: -70px;
            width: 330px;
            height: 240px;
            background: repeating-linear-gradient(-52deg, var(--cyan) 0 14px, transparent 14px 30px);
            opacity: .09;
            transform: skewX(-20deg)
        }

        .final-box:after {
            content: "";
            position: absolute;
            right: -80px;
            bottom: -80px;
            width: 340px;
            height: 250px;
            background: repeating-linear-gradient(-52deg, var(--magenta) 0 14px, transparent 14px 30px);
            opacity: .08;
            transform: skewX(-20deg)
        }

        .final-box>* {
            position: relative;
            z-index: 1
        }

        .final-box h2 {
            font-size: clamp(40px, 6vw, 70px);
            line-height: .98;
            letter-spacing: -.055em;
            font-weight: 1000;
            font-style: italic;
            text-transform: uppercase;
            max-width: 900px;
            margin: 0 auto 18px
        }

        .final-box h2 span {
            background: linear-gradient(90deg, var(--cyan), var(--magenta));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent
        }

        .final-box p {
            color: #68727f;
            max-width: 720px;
            margin: 0 auto 28px;
            font-size: 16px
        }

        /* FLOATING CTA */
        .floating {
            position: fixed;
            left: 50%;
            bottom: 14px;
            transform: translateX(-50%);
            z-index: 999;
            width: min(730px, calc(100% - 24px));
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 10px 10px 10px 18px;
            background: rgba(255, 255, 255, .93);
            backdrop-filter: blur(14px);
            border: 1px solid #d7dde5;
            box-shadow: 0 18px 60px rgba(18, 27, 40, .18);
            clip-path: polygon(12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%, 0 12px);
        }

        .floating-copy {
            flex: 1
        }

        .floating-copy strong {
            display: block;
            font-size: 13px;
            font-weight: 950;
            font-style: italic;
            text-transform: uppercase
        }

        .floating-copy span {
            display: block;
            color: #76808d;
            font-size: 10px
        }

        .floating .btn {
            min-height: 50px;
            padding: 0 18px;
            font-size: 13px
        }

        footer {
            padding: 18px 0 40px;
            text-align: center;
            color: #7c8693;
            font-size: 12px
        }

        @media(max-width:950px) {

            .hero-grid,
            .showcase {
                grid-template-columns: 1fr
            }

            .hero {
                padding-top: 48px
            }

            .hero-visual {
                max-width: 720px;
                margin: auto;
                width: 100%
            }

            .hero-card {
                height: 520px
            }

            .features {
                grid-template-columns: repeat(2, 1fr)
            }

            .proof-grid {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: 340px 230px 230px
            }

            .proof-card.big {
                grid-column: 1/3;
                grid-row: auto
            }

            .trust-grid {
                grid-template-columns: repeat(2, 1fr)
            }

            .trust-item:nth-child(2) {
                border-right: none
            }

            .trust-item:nth-child(-n+2) {
                border-bottom: 1px solid #e2e6ec
            }
        }

        @media(max-width:620px) {
            body {
                padding-bottom: 82px
            }

            .container {
                width: min(var(--max), calc(100% - 24px))
            }

            .section {
                padding: 66px 0
            }

            .hero {
                padding: 38px 0 34px
            }

            .hero:before {
                font-size: 68px;
                top: 80px
            }

            .hero h1 {
                font-size: 47px
            }

            .hero p {
                font-size: 16px
            }

            .hero-actions {
                display: grid;
                grid-template-columns: 1fr;
                width: 100%
            }

            .hero-actions .btn {
                width: 100%
            }

            .hero-visual {
                padding: 12px 10px 12px 12px
            }

            .hero-card {
                height: 415px
            }

            .hero-tag {
                right: -4px
            }

            .features {
                grid-template-columns: 1fr
            }

            .proof-grid {
                grid-template-columns: 1fr;
                grid-template-rows: none
            }

            .proof-card,
            .proof-card.big {
                grid-column: auto;
                grid-row: auto;
                height: 300px
            }

            .showcase-card .image {
                height: 320px
            }

            .cta-strip {
                flex-direction: column;
                align-items: flex-start;
                padding: 24px 22px
            }

            .cta-strip .btn {
                width: 100%
            }

            .cta-strip:after {
                display: none
            }

            .final-box {
                padding: 46px 22px
            }

            .floating-copy {
                display: none
            }

            .floating {
                padding: 9px
            }

            .floating .btn {
                width: 100%;
                font-size: 15px;
                min-height: 54px
            }
        }
    </style>
    <style>
        .chip i {
            margin-right: 6px;
            color: #7c3aed;
        }

        .btn-wa i {
            font-size: 24px;
            vertical-align: middle;
        }
    </style>

</head>

<body>

    <header class="hero">
        <div class="container hero-grid">
            <div>
                <div class="hero-badge">Emblem Sound Custom • UV DTF Timbul</div>
                <h1>Bikin Sound Kamu <span class="line-accent">Makin Beridentitas.</span></h1>
                <p>Emblem akrilik custom dengan cetakan UV DTF timbul. Bisa pakai logo sendiri, edit desain GRATIS, kuat
                    menempel, tahan panas dan hujan.</p>

                <div class="chips">
                    <div class="chip">
                        <i class="fa-solid fa-circle-check"></i>
                        Bisa Custom Logo
                    </div>

                    <div class="chip">
                        <i class="fa-solid fa-circle-check"></i>
                        Edit Gratis
                    </div>

                    <div class="chip">
                        <i class="fa-solid fa-circle-check"></i>
                        Produksi ±1 Hari
                    </div>

                    <div class="chip">
                        <i class="fa-solid fa-circle-check"></i>
                        Tanpa Minimum Order
                    </div>
                </div>

                <div class="hero-actions">
                    <a class="btn btn-wa btn-lg"
                        href="https://wa.me/6285216458653?text=Halo,%20saya%20mau%20lihat%20katalog%20dan%20harga%20Emblem%20Sound">
                        <i class="fa-brands fa-whatsapp"></i>
                        LIHAT KATALOG & HARGA
                    </a>
                    <a class="btn btn-secondary btn-lg" href="#hasil">LIHAT DETAIL PRODUK</a>
                </div>
                <div class="micro">Konsultasi via WhatsApp • Tidak wajib langsung order</div>
            </div>

            <div class="hero-visual">
                <div class="hero-tag">CUSTOM • EMBLEM UV</div>
                <div class="hero-card">
                    <img src="{{ asset('iklanpage/naya-hologram.png') }}" alt="Emblem sound custom hologram" />
                    <div class="hero-overlay">
                        <strong>Custom Logo + Efek Hologram</strong>
                        <span>Visual mencolok, bentuk potongan mengikuti desain emblem.</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="trust">
        <div class="container trust-grid">
            <div class="trust-item"><strong>Custom Bebas</strong><span>Pakai logo sendiri</span></div>
            <div class="trust-item"><strong>Edit Gratis</strong><span>Dibantu tim editor</span></div>
            <div class="trust-item"><strong>±1 Hari</strong><span>Proses produksi</span></div>
            <div class="trust-item"><strong>Lem Kuat</strong><span>Tinggal kupas & tempel</span></div>
        </div>
    </div>

    <section class="section proof-zone" id="hasil">
        <div class="container">
            <div class="kicker">Bukti Produk</div>
            <h2 class="section-title">Bukan sekadar stiker biasa. <em>Ini emblem akrilik.</em></h2>
            <p class="section-desc">Base menggunakan akrilik sekitar 2–2,5 mm. Di atasnya dicetak UV DTF timbul, lalu
                bagian belakang diberi double tape kuat untuk pemasangan.</p>

            <div class="proof-grid">
                <div class="proof-card big">
                    <img src="{{ asset('iklanpage/x.jpg') }}" alt="Emblem sound timbul close-up" />
                    <div class="label">UV DTF timbul • tekstur terasa</div>
                </div>
                <div class="proof-card">
                    <img src="{{ asset('iklanpage/thickness.jpg') }}" alt="Ketebalan emblem sound akrilik" />
                    <div class="label">Akrilik ±2–2,5 mm</div>
                </div>
                <div class="proof-card">
                    <img src="{{ asset('iklanpage/backing-tape.png') }}" alt="Double tape belakang emblem" />
                    <div class="label">Double tape kuat</div>
                </div>
                <div class="proof-card">
                    <img src="{{ asset('iklanpage/a.jpg') }}" alt="Uji air emblem sound" />
                    <div class="label">Tahan panas & hujan</div>
                </div>
                <div class="proof-card">
                    <img src="{{ asset('iklanpage/hologram-pair.png') }}" alt="Emblem sound efek hologram" />
                    <div class="label">Varian efek hologram</div>
                </div>
            </div>

            <div class="cta-strip">
                <div>
                    <strong>Mau lihat model lain & harganya?</strong>
                    <span>CS akan kirim katalog dan bantu pilih ukuran/desain yang sesuai.</span>
                </div>
                <a class="btn btn-wa"
                    href="https://wa.me/6285216458653?text=Halo,%20boleh%20kirim%20katalog%20Emblem%20Sound?">
                    <i class="fa-brands fa-whatsapp"></i>
                    MINTA KATALOG</a>
            </div>
        </div>
    </section>

    <section class="section feature-wrap">
        <div class="container">
            <div class="kicker center">Kenapa Pilih Emblem Ini?</div>
            <h2 class="section-title center">Dibuat untuk branding sekaligus bikin sound <em>lebih standout.</em></h2>

            <div class="features">
                <div class="feature">
                    <div class="feature-icon">01</div>
                    <h3>Bisa Custom Logo</h3>
                    <p>Pakai nama atau logo sound kamu sendiri. Ukuran juga bisa menyesuaikan kebutuhan.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">02</div>
                    <h3>Edit Desain Gratis</h3>
                    <p>File belum rapi? Tim editor bantu rapikan sebelum masuk produksi.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">03</div>
                    <h3>UV DTF Timbul</h3>
                    <p>Cetakan punya tekstur dan lapisan timbul yang terasa ketika disentuh.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">04</div>
                    <h3>Tahan Panas & Hujan</h3>
                    <p>Cocok untuk pemakaian indoor maupun outdoor.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">05</div>
                    <h3>Daya Rekat Kuat</h3>
                    <p>Bagian belakang memakai double tape kuat. Cukup kupas lalu tempel.</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">06</div>
                    <h3>Produksi Cepat</h3>
                    <p>Proses produksi biasanya sekitar satu hari.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="kicker">Pilih Gaya Kamu</div>
            <h2 class="section-title">Pilih katalog atau <em>kirim logo sendiri.</em></h2>
            <p class="section-desc">CS akan menawarkan katalog terlebih dahulu. Kalau ingin identitas sendiri, kamu bisa
                langsung kirim logo atau desain custom.</p>

            <div class="showcase">
                <div class="showcase-card">
                    <div class="image"><img src="{{ asset('iklanpage/katalog.jpg') }}"
                            alt="Contoh katalog emblem sound" /></div>
                    <div class="copy">
                        <h3>Pilih dari Katalog</h3>
                        <p>Pilih model yang sudah tersedia dan konsultasikan pilihan ukuran dengan CS.</p>
                    </div>
                </div>
                <div class="showcase-card">
                    <div class="image"><img src="{{ asset('iklanpage/rzl-audio.jpg') }}"
                            alt="Contoh emblem custom logo sendiri" /></div>
                    <div class="copy">
                        <h3>Pakai Logo Sendiri</h3>
                        <p>Kirim desainmu. Kalau belum siap cetak, tim editor bantu rapikan gratis.</p>
                    </div>
                </div>
            </div>

            <div class="cta-strip">
                <div>
                    <strong>Punya logo sound sendiri?</strong>
                    <span>Kirim sekarang. Kami bantu cek dan rapikan desainnya GRATIS.</span>
                </div>
                <a class="btn btn-wa"
                    href="https://wa.me/6285216458653?text=Halo,%20saya%20punya%20logo%20sendiri%20dan%20ingin%20dibuatkan%20Emblem%20Sound">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    KIRIM LOGO SEKARANG
                </a>
            </div>
        </div>
    </section>

    <section class="section final-section">
        <div class="container">
            <div class="final-box">
                <div class="kicker center">SUDAH SIAP</div>
                <h2>Punya logo sound? <span>Bikin jadi emblem sekarang.</span></h2>
                <p>Kirim logomu ke WhatsApp. Tim kami bantu cek dan rapikan GRATIS. Bisa custom ukuran, tanpa minimum
                    order, dan proses produksi sekitar satu hari.</p>
                <a class="btn btn-wa btn-lg"
                    href="https://wa.me/6285216458653?text=Halo,%20saya%20mau%20buat%20Emblem%20Sound%20Custom.%20Saya%20punya%20logo%20sendiri.">
                    <i class="fa-brands fa-whatsapp"></i>
                    KIRIM LOGO & KONSULTASI GRATIS</a>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">© 2026 Emblem Sound Custom</div>
    </footer>

    <div class="floating">
        <div class="floating-copy"><strong>Mau lihat katalog & harga?</strong><span>Chat CS sekarang</span></div>
        <a class="btn btn-wa"
            href="https://wa.me/6285216458653?text=Halo,%20saya%20tertarik%20dengan%20Emblem%20Sound">
            <i class="fa-brands fa-whatsapp"></i>
            CHAT WHATSAPP</a>
    </div>

</body>

</html>
