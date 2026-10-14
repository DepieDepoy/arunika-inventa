{{-- resources/views/components/vasetra-ai.blade.php --}}

<div
    id="vasetra-ai-root"
    data-ask-url="{{ route('dashboard.intelligence.ask') }}"
    data-confirm-url="{{ route('dashboard.intelligence.confirm') }}"
>

    {{-- ============================================================
         FLOATING BUTTON
    ============================================================ --}}

    <button
        type="button"
        id="vasetra-ai-button"
        class="vasetra-ai-button"
        aria-label="Buka Tera AI"
    >

        <span class="vasetra-ai-button-icon">
            <img
                src="{{ asset('assets/images/ai/tera.jpg') }}"
                alt="Tera"
            >
        </span>

        <span class="vasetra-ai-button-label">
            Tera
        </span>

        <span class="vasetra-ai-online"></span>

    </button>


    {{-- ============================================================
         CHAT WINDOW
    ============================================================ --}}

    <div
        id="vasetra-ai-chat"
        class="vasetra-ai-chat"
        aria-hidden="true"
    >

        {{-- ========================================================
             HEADER
        ======================================================== --}}

        <div class="vasetra-ai-header">

            <div class="vasetra-ai-header-left">

                <div class="vasetra-ai-avatar">

                    <img
                        src="{{ asset('assets/images/ai/tera.jpg') }}"
                        alt="Tera"
                    >

                </div>

                <div class="vasetra-ai-header-info">

                    <div class="vasetra-ai-title">
                        Tera
                    </div>

                    <div class="vasetra-ai-status">

                        <span></span>

                        AI Assistant

                    </div>

                </div>

            </div>


            <div class="vasetra-ai-header-actions">

                <button
                    type="button"
                    id="vasetra-ai-minimize"
                    class="vasetra-ai-header-button"
                    title="Minimize"
                >
                    <i class="bi bi-dash-lg"></i>
                </button>

                <button
                    type="button"
                    id="vasetra-ai-close"
                    class="vasetra-ai-header-button"
                    title="Tutup"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

            </div>

        </div>


        {{-- ========================================================
             CHAT BODY
        ======================================================== --}}

        <div
            id="vasetra-ai-messages"
            class="vasetra-ai-messages"
        >

            {{-- ====================================================
                 WELCOME
            ==================================================== --}}

            <div
                id="vasetra-ai-welcome"
                class="vasetra-ai-welcome"
            >

                <div class="vasetra-ai-welcome-avatar">

                    <img
                        src="{{ asset('assets/images/ai/tera.jpg') }}"
                        alt="Tera"
                    >

                </div>


                <h5>
                    Halo, saya Tera 👋
                </h5>


                <p>
                    Saya adalah AI Assistant Vasetra.
                    Saya bisa membantu mencari informasi,
                    membaca data aset, dan menjalankan
                    beberapa tindakan di Vasetra.
                </p>


                <div class="vasetra-ai-suggestions">

                    <button
                        type="button"
                        class="vasetra-ai-suggestion"
                        data-question="Berapa total asset perusahaan saya?"
                    >

                        <i class="bi bi-box-seam"></i>

                        <span>
                            Total asset
                        </span>

                    </button>


                    <button
                        type="button"
                        class="vasetra-ai-suggestion"
                        data-question="Berapa asset yang menjadi tanggung jawab saya?"
                    >

                        <i class="bi bi-person-badge"></i>

                        <span>
                            Asset saya
                        </span>

                    </button>


                    <button
                        type="button"
                        class="vasetra-ai-suggestion"
                        data-question="Berapa asset yang belum memiliki PIC?"
                    >

                        <i class="bi bi-person-x"></i>

                        <span>
                            Asset tanpa PIC
                        </span>

                    </button>


                    <button
                        type="button"
                        class="vasetra-ai-suggestion"
                        data-question="Berapa maintenance yang terlambat?"
                    >

                        <i class="bi bi-tools"></i>

                        <span>
                            Maintenance terlambat
                        </span>

                    </button>


                    <button
                        type="button"
                        class="vasetra-ai-suggestion"
                        data-question="Berapa maintenance minggu ini?"
                    >

                        <i class="bi bi-calendar-week"></i>

                        <span>
                            Maintenance minggu ini
                        </span>

                    </button>

                </div>

            </div>

        </div>


        {{-- ========================================================
             TYPING / LOADING
        ======================================================== --}}

        <div
            id="vasetra-ai-loading"
            class="vasetra-ai-loading"
        >

            <div class="vasetra-ai-loading-avatar">

                <img
                    src="{{ asset('assets/images/ai/tera.jpg') }}"
                    alt="Tera"
                >

            </div>


            <div class="vasetra-ai-loading-bubble">

                <span></span>
                <span></span>
                <span></span>

            </div>


            <small>
                Tera sedang berpikir...
            </small>

        </div>


        {{-- ========================================================
             INPUT AREA
        ======================================================== --}}

        <div class="vasetra-ai-input-area">

            <div class="vasetra-ai-input-wrapper">

                <textarea
                    id="vasetra-ai-input"
                    class="vasetra-ai-input"
                    rows="1"
                    maxlength="500"
                    placeholder="Tanyakan sesuatu kepada Tera..."
                ></textarea>


                <button
                    type="button"
                    id="vasetra-ai-send"
                    class="vasetra-ai-send"
                    title="Kirim"
                >

                    <i class="bi bi-arrow-up"></i>

                </button>

            </div>


            <div class="vasetra-ai-footer-text">

                <span>
                    Tera AI
                </span>

                <span>
                    Enter untuk mengirim
                </span>

            </div>

        </div>

    </div>

</div>


<style>

/* ================================================================
   ROOT
================================================================ */

#vasetra-ai-root {
    position: relative;
    z-index: 99999;

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}


/* ================================================================
   FLOATING BUTTON
================================================================ */

.vasetra-ai-button {
    position: fixed;

    right: 26px;
    bottom: 26px;

    height: 54px;
    min-width: 54px;

    padding: 0 18px 0 7px;

    border: 0;
    border-radius: 30px;

    background: #0f4cdb;

    color: #ffffff;

    display: flex;
    align-items: center;

    gap: 10px;

    cursor: pointer;

    box-shadow:
        0 10px 30px rgba(15, 76, 219, 0.28);

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        background 0.2s ease;
}


.vasetra-ai-button:hover {
    transform: translateY(-2px);

    background: #0b42c5;

    box-shadow:
        0 14px 35px rgba(15, 76, 219, 0.34);
}


/* ================================================================
   FLOATING BUTTON IMAGE
================================================================ */

.vasetra-ai-button-icon {
    width: 40px;
    height: 40px;

    border-radius: 50%;

    background: #ffffff;

    display: flex;
    align-items: center;
    justify-content: center;

    overflow: hidden;

    flex-shrink: 0;
}


.vasetra-ai-button-icon img {
    width: 30px;
    height: 30px;

    object-fit: contain;

    display: block;
}


.vasetra-ai-button-label {
    font-size: 14px;
    font-weight: 600;

    white-space: nowrap;
}


/* ================================================================
   ONLINE INDICATOR
================================================================ */

.vasetra-ai-online {
    position: absolute;

    right: 7px;
    top: 6px;

    width: 9px;
    height: 9px;

    border-radius: 50%;

    background: #20c997;

    border: 2px solid #0f4cdb;
}


/* ================================================================
   CHAT WINDOW
================================================================ */

.vasetra-ai-chat {
    position: fixed;

    right: 26px;
    bottom: 92px;

    width: 390px;

    max-width:
        calc(100vw - 32px);

    height: 590px;

    max-height:
        calc(100vh - 120px);

    background: #ffffff;

    border: 1px solid #e8edf5;

    border-radius: 20px;

    box-shadow:
        0 20px 70px rgba(15, 23, 42, 0.18);

    overflow: hidden;

    display: flex;
    flex-direction: column;

    opacity: 0;

    visibility: hidden;

    transform:
        translateY(15px)
        scale(0.97);

    transform-origin:
        bottom right;

    transition:
        opacity 0.2s ease,
        visibility 0.2s ease,
        transform 0.2s ease;
}


.vasetra-ai-chat.open {
    opacity: 1;

    visibility: visible;

    transform:
        translateY(0)
        scale(1);
}


/* ================================================================
   HEADER
================================================================ */

.vasetra-ai-header {
    height: 72px;

    padding: 0 16px;

    background: #ffffff;

    border-bottom:
        1px solid #edf1f7;

    display: flex;
    align-items: center;
    justify-content: space-between;

    flex-shrink: 0;
}


.vasetra-ai-header-left {
    display: flex;
    align-items: center;

    gap: 11px;
}


/* ================================================================
   HEADER AVATAR
================================================================ */

.vasetra-ai-avatar {
    width: 42px;
    height: 42px;

    border-radius: 13px;

    background: #edf4ff;

    display: flex;
    align-items: center;
    justify-content: center;

    overflow: hidden;

    flex-shrink: 0;
}


.vasetra-ai-avatar img {
    width: 34px;
    height: 34px;

    object-fit: contain;

    display: block;
}


/* ================================================================
   HEADER TEXT
================================================================ */

.vasetra-ai-title {
    color: #172033;

    font-size: 15px;

    font-weight: 700;

    line-height: 1.2;
}


.vasetra-ai-status {
    display: flex;
    align-items: center;

    gap: 5px;

    margin-top: 4px;

    color: #7c879b;

    font-size: 11px;
}


.vasetra-ai-status span {
    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #20c997;
}


/* ================================================================
   HEADER ACTIONS
================================================================ */

.vasetra-ai-header-actions {
    display: flex;

    gap: 3px;
}


.vasetra-ai-header-button {
    width: 34px;
    height: 34px;

    border: 0;

    border-radius: 9px;

    background: transparent;

    color: #7c879b;

    display: flex;
    align-items: center;
    justify-content: center;

    cursor: pointer;

    transition:
        background 0.15s ease,
        color 0.15s ease;
}


.vasetra-ai-header-button:hover {
    background: #f2f5fa;

    color: #172033;
}


/* ================================================================
   MESSAGES
================================================================ */

.vasetra-ai-messages {
    flex: 1;

    overflow-y: auto;

    padding: 20px 16px;

    background: #f8fafc;

    scroll-behavior: smooth;
}


.vasetra-ai-messages::-webkit-scrollbar {
    width: 5px;
}


.vasetra-ai-messages::-webkit-scrollbar-thumb {
    background: #d8dfeb;

    border-radius: 10px;
}


/* ================================================================
   WELCOME
================================================================ */

.vasetra-ai-welcome {
    text-align: center;

    padding:
        10px 5px 20px;
}


/* ================================================================
   WELCOME AVATAR
================================================================ */

.vasetra-ai-welcome-avatar {
    width: 72px;
    height: 72px;

    margin:
        0 auto 14px;

    border-radius: 20px;

    background: #eaf2ff;

    display: flex;
    align-items: center;
    justify-content: center;

    overflow: hidden;
}


.vasetra-ai-welcome-avatar img {
    width: 58px;
    height: 58px;

    object-fit: contain;

    display: block;
}


/* ================================================================
   WELCOME TEXT
================================================================ */

.vasetra-ai-welcome h5 {
    margin:
        0 0 7px;

    color: #172033;

    font-size: 17px;

    font-weight: 700;
}


.vasetra-ai-welcome p {
    max-width: 300px;

    margin:
        0 auto 20px;

    color: #7c879b;

    font-size: 12px;

    line-height: 1.65;
}


/* ================================================================
   SUGGESTIONS
================================================================ */

.vasetra-ai-suggestions {
    display: flex;

    flex-direction: column;

    gap: 8px;

    text-align: left;
}


.vasetra-ai-suggestion {
    width: 100%;

    padding:
        11px 12px;

    border:
        1px solid #e5eaf2;

    border-radius: 11px;

    background: #ffffff;

    color: #344054;

    display: flex;
    align-items: center;

    gap: 10px;

    cursor: pointer;

    font-size: 12px;

    text-align: left;

    transition:
        border-color 0.15s ease,
        background 0.15s ease,
        transform 0.15s ease;
}


.vasetra-ai-suggestion:hover {
    border-color: #bdd1fa;

    background: #f7faff;

    transform:
        translateX(2px);
}


.vasetra-ai-suggestion i {
    width: 28px;
    height: 28px;

    border-radius: 8px;

    background: #f0f5ff;

    color: #0f4cdb;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;
}


/* ================================================================
   MESSAGE
================================================================ */

.vasetra-ai-message {
    display: flex;

    margin-bottom: 16px;
}


.vasetra-ai-message.user {
    justify-content: flex-end;
}


.vasetra-ai-message.ai {
    justify-content: flex-start;

    gap: 8px;

    align-items: flex-end;
}


.vasetra-ai-message-bubble {
    max-width: 82%;

    padding:
        11px 13px;

    border-radius: 14px;

    font-size: 13px;

    line-height: 1.55;

    word-break: break-word;
}


.vasetra-ai-message.user
.vasetra-ai-message-bubble {
    background: #0f4cdb;

    color: #ffffff;

    border-bottom-right-radius: 5px;
}


.vasetra-ai-message.ai
.vasetra-ai-message-bubble {
    background: #ffffff;

    color: #344054;

    border:
        1px solid #e7ebf2;

    border-bottom-left-radius: 5px;

    box-shadow:
        0 2px 5px rgba(15, 23, 42, 0.03);
}


/* ================================================================
   MINI TERA AVATAR
================================================================ */

.vasetra-ai-mini-avatar {
    width: 27px;
    height: 27px;

    border-radius: 8px;

    background: #eaf2ff;

    display: flex;
    align-items: center;
    justify-content: center;

    overflow: hidden;

    flex-shrink: 0;
}


.vasetra-ai-mini-avatar img {
    width: 23px;
    height: 23px;

    object-fit: contain;

    display: block;
}


/* ================================================================
   CONFIRMATION CARD
================================================================ */

.vasetra-ai-confirmation {
    margin-top: 10px;

    border:
        1px solid #dfe7f2;

    border-radius: 14px;

    background: #ffffff;

    overflow: hidden;

    box-shadow:
        0 4px 15px rgba(15, 23, 42, 0.05);
}


.vasetra-ai-confirmation-header {
    padding:
        12px 13px;

    background: #f7faff;

    border-bottom:
        1px solid #e8edf5;

    display: flex;
    align-items: center;

    gap: 8px;
}


.vasetra-ai-confirmation-header i {
    color: #0f4cdb;
}


.vasetra-ai-confirmation-header strong {
    font-size: 12px;

    color: #172033;
}


.vasetra-ai-confirmation-body {
    padding:
        12px 13px;
}


.vasetra-ai-confirmation-row {
    display: flex;

    justify-content: space-between;

    gap: 10px;

    padding:
        5px 0;

    font-size: 11px;
}


.vasetra-ai-confirmation-row span:first-child {
    color: #8a94a6;
}


.vasetra-ai-confirmation-row span:last-child {
    color: #344054;

    font-weight: 600;

    text-align: right;
}


.vasetra-ai-confirmation-actions {
    display: flex;

    gap: 8px;

    padding:
        11px 13px;

    border-top:
        1px solid #edf1f6;
}


.vasetra-ai-confirm-button,
.vasetra-ai-cancel-button {
    flex: 1;

    height: 35px;

    border-radius: 8px;

    font-size: 11px;

    font-weight: 600;

    cursor: pointer;
}


.vasetra-ai-confirm-button {
    border: 0;

    background: #0f4cdb;

    color: #ffffff;
}


.vasetra-ai-confirm-button:hover {
    background: #0b42c5;
}


.vasetra-ai-cancel-button {
    border:
        1px solid #dfe5ee;

    background: #ffffff;

    color: #667085;
}


.vasetra-ai-cancel-button:hover {
    background: #f8fafc;
}


/* ================================================================
   LOADING
================================================================ */

.vasetra-ai-loading {
    display: none;

    align-items: center;

    gap: 8px;

    padding:
        0 16px 10px;

    background: #f8fafc;
}


.vasetra-ai-loading.active {
    display: flex;
}


/* ================================================================
   LOADING AVATAR
================================================================ */

.vasetra-ai-loading-avatar {
    width: 27px;
    height: 27px;

    border-radius: 8px;

    background: #eaf2ff;

    display: flex;
    align-items: center;
    justify-content: center;

    overflow: hidden;

    flex-shrink: 0;
}


.vasetra-ai-loading-avatar img {
    width: 23px;
    height: 23px;

    object-fit: contain;

    display: block;
}


/* ================================================================
   TYPING BUBBLE
================================================================ */

.vasetra-ai-loading-bubble {
    display: flex;

    align-items: center;

    gap: 3px;

    height: 30px;

    padding:
        0 11px;

    background: #ffffff;

    border:
        1px solid #e5eaf2;

    border-radius: 10px;
}


.vasetra-ai-loading-bubble span {
    width: 5px;
    height: 5px;

    border-radius: 50%;

    background: #9aa5b5;

    animation:
        vasetraAiTyping
        1.2s
        infinite
        ease-in-out;
}


.vasetra-ai-loading-bubble span:nth-child(2) {
    animation-delay:
        0.15s;
}


.vasetra-ai-loading-bubble span:nth-child(3) {
    animation-delay:
        0.3s;
}


.vasetra-ai-loading small {
    color: #98a2b3;

    font-size: 10px;
}


@keyframes vasetraAiTyping {

    0%,
    60%,
    100% {
        transform:
            translateY(0);

        opacity:
            0.45;
    }

    30% {
        transform:
            translateY(-3px);

        opacity:
            1;
    }

}


/* ================================================================
   INPUT AREA
================================================================ */

.vasetra-ai-input-area {
    padding:
        12px 14px 10px;

    background: #ffffff;

    border-top:
        1px solid #edf1f6;

    flex-shrink: 0;
}


.vasetra-ai-input-wrapper {
    display: flex;

    align-items: flex-end;

    gap: 8px;

    padding: 6px;

    border:
        1px solid #dfe5ee;

    border-radius: 13px;

    background: #ffffff;

    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}


.vasetra-ai-input-wrapper:focus-within {
    border-color: #9dbaf3;

    box-shadow:
        0 0 0 3px
        rgba(15, 76, 219, 0.07);
}


.vasetra-ai-input {
    flex: 1;

    resize: none;

    min-height: 34px;

    max-height: 100px;

    padding:
        7px;

    border:
        0 !important;

    outline:
        0 !important;

    background:
        transparent !important;

    color: #344054;

    font-size: 12px;

    line-height: 1.5;

    box-shadow:
        none !important;
}


.vasetra-ai-input::placeholder {
    color: #a0a9b8;
}


/* ================================================================
   SEND BUTTON
================================================================ */

.vasetra-ai-send {
    width: 34px;
    height: 34px;

    border: 0;

    border-radius: 9px;

    background: #0f4cdb;

    color: #ffffff;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    cursor: pointer;

    transition:
        background 0.15s ease,
        transform 0.15s ease;
}


.vasetra-ai-send:hover {
    background: #0b42c5;

    transform:
        translateY(-1px);
}


.vasetra-ai-send:disabled {
    opacity: 0.45;

    cursor: not-allowed;

    transform: none;
}


/* ================================================================
   FOOTER
================================================================ */

.vasetra-ai-footer-text {
    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        6px 2px 0;

    color: #a0a9b8;

    font-size: 9px;
}


.vasetra-ai-footer-text span:first-child {
    font-weight: 600;
}


/* ================================================================
   MOBILE
================================================================ */

@media (max-width: 575px) {

    .vasetra-ai-button {

        right: 16px;

        bottom: 16px;

        width: 52px;

        min-width: 52px;

        height: 52px;

        padding: 0;

        justify-content: center;
    }


    .vasetra-ai-button-label {
        display: none;
    }


    .vasetra-ai-button-icon {

        width: 40px;

        height: 40px;
    }


    .vasetra-ai-button-icon img {

        width: 30px;

        height: 30px;
    }


    .vasetra-ai-online {

        right: 5px;

        top: 5px;
    }


    .vasetra-ai-chat {

        right: 10px;

        bottom: 78px;

        width:
            calc(100vw - 20px);

        max-width: none;

        height:
            calc(100vh - 100px);

        max-height: none;

        border-radius: 18px;
    }

}

</style>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const root =
            document.getElementById(
                'vasetra-ai-root'
            );


        if (!root) {
            return;
        }


        /* ========================================================
           ELEMENTS
        ======================================================== */

        const button =
            document.getElementById(
                'vasetra-ai-button'
            );


        const chat =
            document.getElementById(
                'vasetra-ai-chat'
            );


        const closeButton =
            document.getElementById(
                'vasetra-ai-close'
            );


        const minimizeButton =
            document.getElementById(
                'vasetra-ai-minimize'
            );


        const messages =
            document.getElementById(
                'vasetra-ai-messages'
            );


        const input =
            document.getElementById(
                'vasetra-ai-input'
            );


        const sendButton =
            document.getElementById(
                'vasetra-ai-send'
            );


        const loading =
            document.getElementById(
                'vasetra-ai-loading'
            );


        const welcome =
            document.getElementById(
                'vasetra-ai-welcome'
            );


        const askUrl =
            root.dataset.askUrl || '';


        const confirmUrl =
            root.dataset.confirmUrl || '';


        const csrfToken =
            document
                .querySelector(
                    'meta[name="csrf-token"]'
                )
                ?.getAttribute(
                    'content'
                ) || '';


        let isLoading = false;


        /* ========================================================
           OPEN CHAT
        ======================================================== */

        function openChat() {

            chat.classList.add(
                'open'
            );


            chat.setAttribute(
                'aria-hidden',
                'false'
            );


            setTimeout(
                function () {

                    input.focus();

                },
                200
            );

        }


        /* ========================================================
           CLOSE CHAT
        ======================================================== */

        function closeChat() {

            chat.classList.remove(
                'open'
            );


            chat.setAttribute(
                'aria-hidden',
                'true'
            );

        }


        /* ========================================================
           BUTTON EVENTS
        ======================================================== */

        button.addEventListener(
            'click',
            function () {

                if (
                    chat.classList.contains(
                        'open'
                    )
                ) {

                    closeChat();

                } else {

                    openChat();

                }

            }
        );


        closeButton.addEventListener(
            'click',
            closeChat
        );


        minimizeButton.addEventListener(
            'click',
            closeChat
        );


        /* ========================================================
           TEXTAREA AUTO HEIGHT
        ======================================================== */

        input.addEventListener(
            'input',
            function () {

                this.style.height =
                    'auto';


                this.style.height =
                    Math.min(
                        this.scrollHeight,
                        100
                    ) + 'px';

            }
        );


        /* ========================================================
           ENTER SEND
        ======================================================== */

        input.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Enter' &&
                    !event.shiftKey
                ) {

                    event.preventDefault();

                    sendQuestion();

                }

            }
        );


        /* ========================================================
           SEND BUTTON
        ======================================================== */

        sendButton.addEventListener(
            'click',
            sendQuestion
        );


        /* ========================================================
           QUICK SUGGESTIONS
        ======================================================== */

        document
            .querySelectorAll(
                '.vasetra-ai-suggestion'
            )
            .forEach(
                function (suggestion) {

                    suggestion.addEventListener(
                        'click',
                        function () {

                            const question =
                                this.dataset.question ||
                                '';


                            if (!question) {
                                return;
                            }


                            input.value =
                                question;


                            input.dispatchEvent(
                                new Event(
                                    'input'
                                )
                            );


                            sendQuestion();

                        }
                    );

                }
            );


        /* ========================================================
           ADD USER MESSAGE
        ======================================================== */

        function addUserMessage(text) {

            const wrapper =
                document.createElement(
                    'div'
                );


            wrapper.className =
                'vasetra-ai-message user';


            const bubble =
                document.createElement(
                    'div'
                );


            bubble.className =
                'vasetra-ai-message-bubble';


            bubble.textContent =
                text;


            wrapper.appendChild(
                bubble
            );


            messages.appendChild(
                wrapper
            );


            scrollMessages();

        }


        /* ========================================================
           ADD AI MESSAGE
        ======================================================== */

        function addAiMessage(text) {

            const wrapper =
                document.createElement(
                    'div'
                );


            wrapper.className =
                'vasetra-ai-message ai';


            const avatar =
                document.createElement(
                    'div'
                );


            avatar.className =
                'vasetra-ai-mini-avatar';


            const avatarImage =
                document.createElement(
                    'img'
                );


            avatarImage.src =
                '/assets/images/ai/tera.jpg';


            avatarImage.alt =
                'Tera';


            avatar.appendChild(
                avatarImage
            );


            const bubble =
                document.createElement(
                    'div'
                );


            bubble.className =
                'vasetra-ai-message-bubble';


            bubble.textContent =
                text ||
                'Tidak ada jawaban.';


            wrapper.appendChild(
                avatar
            );


            wrapper.appendChild(
                bubble
            );


            messages.appendChild(
                wrapper
            );


            scrollMessages();

        }


        /* ========================================================
           CONFIRMATION CARD
        ======================================================== */

        function addConfirmationCard(data) {

            if (!data) {
                return;
            }


            const wrapper =
                document.createElement(
                    'div'
                );


            wrapper.className =
                'vasetra-ai-message ai';


            const avatar =
                document.createElement(
                    'div'
                );


            avatar.className =
                'vasetra-ai-mini-avatar';


            const avatarImage =
                document.createElement(
                    'img'
                );


            avatarImage.src =
                '/assets/images/ai/tera.jpg';


            avatarImage.alt =
                'Tera';


            avatar.appendChild(
                avatarImage
            );


            const container =
                document.createElement(
                    'div'
                );


            container.style.maxWidth =
                '82%';


            const bubble =
                document.createElement(
                    'div'
                );


            bubble.className =
                'vasetra-ai-message-bubble';


            bubble.textContent =
                'Data sudah lengkap. Apakah Anda ingin membuat asset ini?';


            const card =
                document.createElement(
                    'div'
                );


            card.className =
                'vasetra-ai-confirmation';


            const header =
                document.createElement(
                    'div'
                );


            header.className =
                'vasetra-ai-confirmation-header';


            const headerIcon =
                document.createElement(
                    'i'
                );


            headerIcon.className =
                'bi bi-clipboard-check';


            const headerText =
                document.createElement(
                    'strong'
                );


            headerText.textContent =
                'Konfirmasi Tambah Asset';


            header.appendChild(
                headerIcon
            );


            header.appendChild(
                headerText
            );


            const body =
                document.createElement(
                    'div'
                );


            body.className =
                'vasetra-ai-confirmation-body';


            addConfirmationRow(
                body,
                'Asset',
                data.asset_name
            );


            addConfirmationRow(
                body,
                'Category',
                data.category_name ||
                data.category ||
                '-'
            );


            addConfirmationRow(
                body,
                'Subcategory',
                data.subcategory_name ||
                data.subcategory ||
                '-'
            );


            addConfirmationRow(
                body,
                'Vendor',
                data.vendor_name ||
                data.vendor ||
                '-'
            );


            addConfirmationRow(
                body,
                'Harga',
                formatRupiah(
                    data.purchase_price
                )
            );


            const actions =
                document.createElement(
                    'div'
                );


            actions.className =
                'vasetra-ai-confirmation-actions';


            const cancelButton =
                document.createElement(
                    'button'
                );


            cancelButton.type =
                'button';


            cancelButton.className =
                'vasetra-ai-cancel-button';


            cancelButton.textContent =
                'Batal';


            const confirmButton =
                document.createElement(
                    'button'
                );


            confirmButton.type =
                'button';


            confirmButton.className =
                'vasetra-ai-confirm-button';


            confirmButton.textContent =
                'Konfirmasi';


            actions.appendChild(
                cancelButton
            );


            actions.appendChild(
                confirmButton
            );


            card.appendChild(
                header
            );


            card.appendChild(
                body
            );


            card.appendChild(
                actions
            );


            container.appendChild(
                bubble
            );


            container.appendChild(
                card
            );


            wrapper.appendChild(
                avatar
            );


            wrapper.appendChild(
                container
            );


            messages.appendChild(
                wrapper
            );


            cancelButton.addEventListener(
                'click',
                function () {

                    card.remove();


                    addAiMessage(
                        'Baik, tindakan dibatalkan.'
                    );

                }
            );


            confirmButton.addEventListener(
                'click',
                function () {

                    confirmButton.disabled =
                        true;


                    confirmButton.textContent =
                        'Memproses...';


                    executeConfirmation(
                        data.token,
                        confirmButton,
                        cancelButton
                    );

                }
            );


            scrollMessages();

        }


        /* ========================================================
           CONFIRMATION ROW
        ======================================================== */

        function addConfirmationRow(
            parent,
            label,
            value
        ) {

            const row =
                document.createElement(
                    'div'
                );


            row.className =
                'vasetra-ai-confirmation-row';


            const labelElement =
                document.createElement(
                    'span'
                );


            labelElement.textContent =
                label;


            const valueElement =
                document.createElement(
                    'span'
                );


            valueElement.textContent =
                value || '-';


            row.appendChild(
                labelElement
            );


            row.appendChild(
                valueElement
            );


            parent.appendChild(
                row
            );

        }


        /* ========================================================
           FORMAT RUPIAH
        ======================================================== */

        function formatRupiah(value) {

            if (
                value === null ||
                value === undefined ||
                value === ''
            ) {

                return '-';

            }


            const number =
                Number(value);


            if (
                Number.isNaN(number)
            ) {

                return value;

            }


            return new Intl.NumberFormat(
                'id-ID',
                {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0
                }
            ).format(number);

        }


        /* ========================================================
           SEND QUESTION
        ======================================================== */

        async function sendQuestion() {

            if (isLoading) {
                return;
            }


            const question =
                input.value.trim();


            if (!question) {
                return;
            }


            if (!askUrl) {

                addAiMessage(
                    'Endpoint AI belum tersedia.'
                );

                return;

            }


            isLoading =
                true;


            sendButton.disabled =
                true;


            if (welcome) {

                welcome.style.display =
                    'none';

            }


            addUserMessage(
                question
            );


            input.value =
                '';


            input.style.height =
                'auto';


            loading.classList.add(
                'active'
            );


            scrollMessages();


            try {

                const response =
                    await fetch(
                        askUrl,
                        {
                            method:
                                'POST',

                            headers: {

                                'Content-Type':
                                    'application/json',

                                'Accept':
                                    'application/json',

                                'X-CSRF-TOKEN':
                                    csrfToken

                            },

                            body:
                                JSON.stringify({
                                    question:
                                        question
                                })
                        }
                    );


                const data =
                    await response.json();


                if (!response.ok) {

                    throw new Error(
                        data.message ||
                        'Terjadi kesalahan pada server.'
                    );

                }


                handleAiResponse(
                    data
                );


            } catch (error) {

                addAiMessage(
                    error.message ||
                    'Terjadi kesalahan saat menghubungi Tera AI.'
                );


            } finally {

                loading.classList.remove(
                    'active'
                );


                isLoading =
                    false;


                sendButton.disabled =
                    false;


                input.focus();

            }

        }


        /* ========================================================
           HANDLE AI RESPONSE
        ======================================================== */

        function handleAiResponse(data) {

            const type =
                data.type ||
                'answer';


            if (
                type ===
                'confirmation_required'
            ) {

                if (data.message) {

                    addAiMessage(
                        data.message
                    );

                }


                const confirmationData =
                    Object.assign(
                        {},
                        data.data || {},
                        {
                            token:
                                data.token ||
                                ''
                        }
                    );


                addConfirmationCard(
                    confirmationData
                );


                return;

            }


            if (
                type ===
                'action_completed'
            ) {

                addAiMessage(
                    data.message ||
                    'Tindakan berhasil dilakukan.'
                );


                return;

            }


            if (
                type ===
                'action_failed'
            ) {

                addAiMessage(
                    data.message ||
                    'Tindakan gagal dilakukan.'
                );


                return;

            }


            if (
                type ===
                'missing_fields'
            ) {

                addAiMessage(
                    data.message ||
                    'Data yang diperlukan masih belum lengkap.'
                );


                return;

            }


            if (
                type ===
                'permission_denied'
            ) {

                addAiMessage(
                    data.message ||
                    'Anda tidak memiliki permission untuk tindakan tersebut.'
                );


                return;

            }


            const message =
                data.message ||
                data.answer ||
                data.data?.message ||
                'Tidak ada jawaban.';


            addAiMessage(
                message
            );

        }


        /* ========================================================
           EXECUTE CONFIRMATION
        ======================================================== */

        async function executeConfirmation(
            token,
            confirmButton,
            cancelButton
        ) {

            if (!token) {

                addAiMessage(
                    'Token konfirmasi tidak tersedia.'
                );

                return;

            }


            if (!confirmUrl) {

                addAiMessage(
                    'Endpoint konfirmasi AI belum tersedia.'
                );

                return;

            }


            try {

                const response =
                    await fetch(
                        confirmUrl,
                        {
                            method:
                                'POST',

                            headers: {

                                'Content-Type':
                                    'application/json',

                                'Accept':
                                    'application/json',

                                'X-CSRF-TOKEN':
                                    csrfToken

                            },

                            body:
                                JSON.stringify({
                                    token:
                                        token
                                })
                        }
                    );


                const data =
                    await response.json();


                if (!response.ok) {

                    throw new Error(
                        data.message ||
                        'Konfirmasi gagal.'
                    );

                }


                addAiMessage(
                    data.message ||
                    'Tindakan berhasil dilakukan.'
                );


                confirmButton.textContent =
                    'Berhasil';


                confirmButton.disabled =
                    true;


                cancelButton.style.display =
                    'none';


                if (
                    data.data &&
                    data.data.asset_code
                ) {

                    addAiMessage(
                        'Asset ' +
                        data.data.asset_code +
                        ' berhasil dibuat.'
                    );

                }


            } catch (error) {

                confirmButton.disabled =
                    false;


                confirmButton.textContent =
                    'Konfirmasi';


                addAiMessage(
                    error.message ||
                    'Terjadi kesalahan saat melakukan konfirmasi.'
                );

            }

        }


        /* ========================================================
           SCROLL
        ======================================================== */

        function scrollMessages() {

            setTimeout(
                function () {

                    messages.scrollTop =
                        messages.scrollHeight;

                },
                30
            );

        }

    }

);

</script>