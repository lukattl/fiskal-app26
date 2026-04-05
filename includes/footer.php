<style>
    html,
    body {
        min-height: 100%;
    }

    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .app-footer {
        margin-top: auto;
        border-top: 1px solid #d8deea;
        background: #ffffff;
    }

    .app-footer__inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: 1.25rem 1rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .app-footer__text {
        color: #4b5563;
        font-size: 0.95rem;
    }

    .app-footer__text a {
        color: #0d6efd;
        text-decoration: none;
    }

    .app-footer__text a:hover {
        text-decoration: underline;
    }

    .app-footer__socials {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .app-footer__icon {
        width: 40px;
        height: 40px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #d8deea;
        background: #f8fafc;
        color: #1f2937;
        text-decoration: none;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }

    .app-footer__icon:hover {
        background: #e9f2ff;
        border-color: #9ec5fe;
        color: #0d6efd;
    }

    .app-footer__icon svg {
        width: 18px;
        height: 18px;
        fill: currentColor;
    }
</style>
<footer class="app-footer">
    <div class="app-footer__inner">
        <div class="app-footer__text">
            Support and onboarding help:
            <a href="mailto:luka@tvz.hr">luka@tvz.hr</a>
        </div>
        <div class="app-footer__socials" aria-label="Support links">
            <a class="app-footer__icon" href="https://www.facebook.com/" target="_blank" rel="noreferrer" aria-label="Facebook">
                <svg viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M8.94 6.5V4.95c0-.46.3-.57.52-.57h1.31V2.01L8.96 2C6.78 2 6.28 3.62 6.28 4.66V6.5H4.75V9h1.53v5H8.94V9h1.8l.24-2.5H8.94z"/>
                </svg>
            </a>
            <a class="app-footer__icon" href="https://www.linkedin.com/" target="_blank" rel="noreferrer" aria-label="LinkedIn">
                <svg viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M1.15 5.01h2.69V14H1.15V5.01zm1.35-4A1.56 1.56 0 1 1 2.5 4.13 1.56 1.56 0 0 1 2.5 1zm2.98 4h2.57v1.23h.04c.36-.68 1.23-1.39 2.53-1.39 2.7 0 3.2 1.78 3.2 4.09V14h-2.68V9.28c0-1.13-.02-2.58-1.57-2.58-1.58 0-1.82 1.23-1.82 2.5V14H5.48V5.01z"/>
                </svg>
            </a>
            <a class="app-footer__icon" href="https://www.instagram.com/" target="_blank" rel="noreferrer" aria-label="Instagram">
                <svg viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M8 3.9A4.1 4.1 0 1 0 8 12.1 4.1 4.1 0 0 0 8 3.9zm0 6.76A2.66 2.66 0 1 1 8 5.34a2.66 2.66 0 0 1 0 5.32zm5.22-6.8a.96.96 0 1 1-.96-.96.96.96 0 0 1 .96.96z"/>
                    <path d="M8 1.55c2.1 0 2.35.01 3.17.05.75.03 1.15.16 1.42.27.36.14.62.31.89.58.27.27.44.53.58.89.11.27.24.67.27 1.42.04.82.05 1.07.05 3.17s-.01 2.35-.05 3.17c-.03.75-.16 1.15-.27 1.42-.14.36-.31.62-.58.89-.27.27-.53.44-.89.58-.27.11-.67.24-1.42.27-.82.04-1.07.05-3.17.05s-2.35-.01-3.17-.05c-.75-.03-1.15-.16-1.42-.27a2.4 2.4 0 0 1-.89-.58 2.4 2.4 0 0 1-.58-.89c-.11-.27-.24-.67-.27-1.42C1.56 10.35 1.55 10.1 1.55 8s.01-2.35.05-3.17c.03-.75.16-1.15.27-1.42.14-.36.31-.62.58-.89.27-.27.53-.44.89-.58.27-.11.67-.24 1.42-.27C5.65 1.56 5.9 1.55 8 1.55zm0-1.55C5.86 0 5.59.01 4.76.05 3.92.09 3.34.23 2.83.43c-.53.2-.97.47-1.4.9-.43.43-.7.87-.9 1.4-.2.51-.34 1.09-.38 1.93C.01 5.59 0 5.86 0 8c0 2.14.01 2.41.05 3.24.04.84.18 1.42.38 1.93.2.53.47.97.9 1.4.43.43.87.7 1.4.9.51.2 1.09.34 1.93.38.83.04 1.1.05 3.24.05 2.14 0 2.41-.01 3.24-.05.84-.04 1.42-.18 1.93-.38.53-.2.97-.47 1.4-.9.43-.43.7-.87.9-1.4.2-.51.34-1.09.38-1.93.04-.83.05-1.1.05-3.24 0-2.14-.01-2.41-.05-3.24-.04-.84-.18-1.42-.38-1.93a3.84 3.84 0 0 0-.9-1.4 3.84 3.84 0 0 0-1.4-.9c-.51-.2-1.09-.34-1.93-.38C10.41.01 10.14 0 8 0z"/>
                </svg>
            </a>
            <a class="app-footer__icon" href="https://www.youtube.com/" target="_blank" rel="noreferrer" aria-label="YouTube video manual">
                <svg viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M8.05 1.95c1.84 0 3.69.05 5.52.15.53.03 1.02.18 1.31.68.17.29.24.66.28 1 .1.81.14 1.63.14 2.45v3.54c0 .82-.04 1.64-.14 2.45-.04.34-.11.71-.28 1-.29.5-.78.65-1.31.68-3.68.2-7.37.2-11.05 0-.53-.03-1.02-.18-1.31-.68-.17-.29-.24-.66-.28-1A20.9 20.9 0 0 1 .8 9.77V6.23c0-.82.04-1.64.14-2.45.04-.34.11-.71.28-1 .29-.5.78-.65 1.31-.68 1.84-.1 3.69-.15 5.52-.15zm-1.7 3.17v5.76L11.2 8 6.35 5.12z"/>
                </svg>
            </a>
        </div>
    </div>
</footer>
