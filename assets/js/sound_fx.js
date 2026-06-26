(function () {
    const STORAGE_KEY = "colesterol_game_sound_enabled";
    let audioContext = null;
    let enabled = localStorage.getItem(STORAGE_KEY) !== "0";

    function getContext() {
        if (!audioContext) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return null;
            audioContext = new AudioCtx();
        }

        if (audioContext.state === "suspended") {
            audioContext.resume().catch(() => {});
        }

        return audioContext;
    }

    function tone({ frequency, endFrequency = null, start, duration, type = "sine", gain = 0.08 }) {
        const ctx = getContext();
        if (!ctx || !enabled) return;

        const oscillator = ctx.createOscillator();
        const amp = ctx.createGain();
        const t0 = ctx.currentTime + start;
        const t1 = t0 + duration;

        oscillator.type = type;
        oscillator.frequency.setValueAtTime(frequency, t0);

        if (endFrequency) {
            oscillator.frequency.exponentialRampToValueAtTime(endFrequency, t1);
        }

        amp.gain.setValueAtTime(0.0001, t0);
        amp.gain.exponentialRampToValueAtTime(gain, t0 + 0.012);
        amp.gain.exponentialRampToValueAtTime(0.0001, t1);

        oscillator.connect(amp);
        amp.connect(ctx.destination);
        oscillator.start(t0);
        oscillator.stop(t1 + 0.02);
    }

    const patterns = {
        click: [
            { frequency: 520, start: 0, duration: 0.045, type: "triangle", gain: 0.035 }
        ],
        select: [
            { frequency: 420, start: 0, duration: 0.05, type: "triangle", gain: 0.04 },
            { frequency: 620, start: 0.045, duration: 0.055, type: "triangle", gain: 0.035 }
        ],
        correct: [
            { frequency: 523.25, start: 0, duration: 0.07, type: "triangle", gain: 0.06 },
            { frequency: 659.25, start: 0.06, duration: 0.08, type: "triangle", gain: 0.065 },
            { frequency: 880, start: 0.14, duration: 0.14, type: "sine", gain: 0.06 },
            { frequency: 1318.51, start: 0.24, duration: 0.12, type: "sine", gain: 0.035 }
        ],
        incorrect: [
            { frequency: 220, start: 0, duration: 0.11, type: "sawtooth", gain: 0.045 },
            { frequency: 164.81, start: 0.1, duration: 0.14, type: "sawtooth", gain: 0.035 }
        ],
        gameOver: [
            { frequency: 185, endFrequency: 92, start: 0, duration: 0.34, type: "sawtooth", gain: 0.06 },
            { frequency: 146.83, endFrequency: 73.42, start: 0.04, duration: 0.32, type: "triangle", gain: 0.048 },
            { frequency: 98, endFrequency: 61.74, start: 0.16, duration: 0.28, type: "sine", gain: 0.038 }
        ],
        timeout: [
            { frequency: 392, start: 0, duration: 0.08, type: "square", gain: 0.035 },
            { frequency: 293.66, start: 0.11, duration: 0.08, type: "square", gain: 0.03 },
            { frequency: 196, start: 0.22, duration: 0.14, type: "square", gain: 0.03 }
        ],
        continue: [
            { frequency: 440, start: 0, duration: 0.055, type: "triangle", gain: 0.035 }
        ],
        finish: [
            { frequency: 523.25, endFrequency: 554.37, start: 0, duration: 0.16, type: "sawtooth", gain: 0.052 },
            { frequency: 659.25, endFrequency: 698.46, start: 0.17, duration: 0.16, type: "sawtooth", gain: 0.052 },
            { frequency: 783.99, endFrequency: 830.61, start: 0.34, duration: 0.2, type: "sawtooth", gain: 0.056 },
            { frequency: 1046.5, start: 0.58, duration: 0.38, type: "square", gain: 0.04 },
            { frequency: 1318.51, start: 0.58, duration: 0.38, type: "triangle", gain: 0.032 },
            { frequency: 1567.98, start: 0.7, duration: 0.22, type: "sine", gain: 0.024 }
        ],
        badge: [
            { frequency: 740, endFrequency: 1180, start: 0, duration: 0.22, type: "sawtooth", gain: 0.04 },
            { frequency: 880, endFrequency: 1320, start: 0.08, duration: 0.24, type: "triangle", gain: 0.035 },
            { frequency: 1320, endFrequency: 1760, start: 0.26, duration: 0.28, type: "sine", gain: 0.032 }
        ]
    };

    function play(name) {
        const pattern = patterns[name];
        if (!pattern || !enabled) return;
        pattern.forEach(tone);
    }

    function timerTick(remainingSeconds, totalSeconds = 20) {
        if (!enabled) return;

        const remaining = Number(remainingSeconds);
        const total = Math.max(1, Number(totalSeconds) || 20);

        if (!Number.isFinite(remaining) || remaining <= 0) {
            return;
        }

        const progress = 1 - Math.max(0, Math.min(remaining / total, 1));
        const baseFrequency = 440 + (progress * 260);
        const gain = remaining <= 5 ? 0.045 : 0.025;
        const repeats = remaining <= 3 ? 3 : (remaining <= 5 ? 2 : 1);
        const gap = remaining <= 3 ? 0.08 : 0.12;

        for (let i = 0; i < repeats; i++) {
            tone({
                frequency: baseFrequency + (i * 80),
                start: i * gap,
                duration: remaining <= 5 ? 0.045 : 0.035,
                type: "square",
                gain
            });
        }
    }

    function confetti(options = {}) {
        const count = Number(options.count || 90);
        const mode = options.mode || "fall";
        const colors = ["#5b5cf0", "#22c55e", "#f59e0b", "#ef4444", "#38bdf8", "#f8fafc"];
        const layer = document.createElement("div");

        layer.className = "confetti-layer";

        if (mode === "side") {
            layer.classList.add("is-side-burst");
        }

        for (let i = 0; i < count; i++) {
            const piece = document.createElement("span");
            const duration = 1700 + Math.random() * 1300;
            const delay = Math.random() * 420;
            const spin = (Math.random() > 0.5 ? 1 : -1) * (360 + Math.random() * 720);

            piece.className = "confetti-piece";
            piece.style.background = colors[i % colors.length];
            piece.style.animationDelay = `${delay}ms`;
            piece.style.setProperty("--fall-duration", `${duration}ms`);
            piece.style.setProperty("--spin", `${spin}deg`);

            if (mode === "side") {
                const fromLeft = i % 2 === 0;
                const startY = 18 + Math.random() * 64;
                const travelX = fromLeft
                    ? 36 + Math.random() * 54
                    : -(36 + Math.random() * 54);
                const travelY = -26 + Math.random() * 66;

                piece.style.setProperty("--start-x", fromLeft ? "-18px" : "calc(100vw + 18px)");
                piece.style.setProperty("--start-y", `${startY}vh`);
                piece.style.setProperty("--travel-x", `${travelX}vw`);
                piece.style.setProperty("--travel-y", `${travelY}vh`);
            } else {
                const left = Math.random() * 100;
                const drift = (Math.random() * 220) - 110;

                piece.style.left = `${left}%`;
                piece.style.setProperty("--drift", `${drift}px`);
            }

            layer.appendChild(piece);
        }

        document.body.appendChild(layer);
        setTimeout(() => layer.remove(), 3600);
    }

    function setEnabled(nextEnabled) {
        enabled = Boolean(nextEnabled);
        localStorage.setItem(STORAGE_KEY, enabled ? "1" : "0");
        updateToggle();
    }

    function toggle() {
        setEnabled(!enabled);
        if (enabled) {
            play("select");
        }
    }

    function updateToggle() {
        const button = document.getElementById("sound-toggle-btn");
        if (!button) return;

        button.innerHTML = enabled
            ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9v6h4l5 4V5L7 9H3Zm13.5 3A4.5 4.5 0 0 0 14 8v8a4.5 4.5 0 0 0 2.5-4Zm0-7.5v2.2A7 7 0 0 1 19 12a7 7 0 0 1-2.5 5.3v2.2A9 9 0 0 0 21 12a9 9 0 0 0-4.5-7.5Z"/></svg>'
            : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9v6h4l5 4v-6.8l-7.1-7.1L3.5 6.5 6 9H3Zm17.5 10.1L4.9 3.5 3.5 4.9l15.6 15.6 1.4-1.4ZM12 5v3.2L9.8 6 12 5Zm6.7 7c0 .9-.2 1.7-.6 2.4l-1.5-1.5c.1-.3.1-.6.1-.9 0-1.7-.9-3.1-2.2-3.9V5.9A7 7 0 0 1 18.7 12Z"/></svg>';
        button.title = enabled ? "Silenciar sonidos" : "Activar sonidos";
        button.setAttribute("aria-label", button.title);
        button.classList.toggle("is-muted", !enabled);
    }

    function injectToggle() {
        if (document.getElementById("sound-toggle-btn")) return;

        const button = document.createElement("button");
        button.type = "button";
        button.id = "sound-toggle-btn";
        button.className = "sound-toggle-btn";
        button.addEventListener("click", toggle);
        document.body.appendChild(button);
        updateToggle();
    }

    document.addEventListener("DOMContentLoaded", injectToggle);
    document.addEventListener("click", event => {
        if (
            event.target.closest("button, a") &&
            !event.target.closest(".option-btn") &&
            !event.target.closest("#sound-toggle-btn")
        ) {
            play("click");
        }
    }, true);

    window.GameSounds = {
        play,
        timerTick,
        confetti,
        setEnabled,
        isEnabled: () => enabled
    };
})();
