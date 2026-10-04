// This file is part of Moodle - https://moodle.org/

define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

const fetchMany = Ajax.call;

const recordProgress = (cmid, start, end, duration) => fetchMany([{
    methodname: 'mod_interactivevideo_record_progress',
    args: {cmid, start, end, duration},
}])[0];

const submitAnswer = (cmid, cueid, answer) => fetchMany([{
    methodname: 'mod_interactivevideo_submit_answer',
    args: {cmid, cueid, answer},
}])[0];

const loadVimeoSdk = () => new Promise((resolve, reject) => {
    if (window.Vimeo?.Player) {
        resolve(window.Vimeo);
        return;
    }
    const existing = document.querySelector('script[data-mod-interactivevideo-vimeo]');
    if (existing) {
        existing.addEventListener('load', () => resolve(window.Vimeo), {once: true});
        existing.addEventListener('error', reject, {once: true});
        return;
    }
    const script = document.createElement('script');
    script.src = 'https://player.vimeo.com/api/player.js';
    script.dataset.modInteractivevideoVimeo = '1';
    script.addEventListener('load', () => resolve(window.Vimeo), {once: true});
    script.addEventListener('error', reject, {once: true});
    document.head.append(script);
});

const nativeAdapter = media => ({
    onTime: callback => media.addEventListener('timeupdate', () => callback(media.currentTime, media.duration)),
    onPause: callback => media.addEventListener('pause', callback),
    onEnded: callback => media.addEventListener('ended', callback),
    pause: () => media.pause(),
    play: () => media.play(),
});

const vimeoAdapter = async iframe => {
    const Vimeo = await loadVimeoSdk();
    const player = new Vimeo.Player(iframe);
    return {
        onTime: callback => player.on('timeupdate', data => callback(data.seconds, data.duration)),
        onPause: callback => player.on('pause', callback),
        onEnded: callback => player.on('ended', callback),
        pause: () => player.pause(),
        play: () => player.play(),
    };
};

const updateProgress = (root, result) => {
    root.querySelector('[data-region="progress"]').value = result.percent;
    root.querySelector('[data-region="progress-label"]').textContent = `${result.percent}%`;
};

const setupQuestion = (root, adapter, config, cue, completed) => {
    const panel = root.querySelector('[data-region="question"]');
    const form = root.querySelector('[data-region="answer-form"]');
    const options = root.querySelector('[data-region="options"]');
    const feedback = root.querySelector('[data-region="feedback"]');
    const continueButton = root.querySelector('[data-region="continue"]');

    adapter.pause();
    panel.hidden = false;
    root.querySelector('[data-region="question-text"]').textContent = cue.question;
    root.querySelector('[data-region="submit-answer"]').textContent = config.strings.submit;
    continueButton.textContent = config.strings.continue;
    feedback.hidden = true;
    continueButton.hidden = true;
    options.replaceChildren();
    cue.options.forEach((option, index) => {
        const label = document.createElement('label');
        label.className = 'mod-interactivevideo__option';
        const input = document.createElement('input');
        input.type = 'radio';
        input.name = 'answer';
        input.value = index;
        input.required = true;
        label.append(input, document.createTextNode(` ${option}`));
        options.append(label);
    });
    panel.focus();

    const onSubmit = event => {
        event.preventDefault();
        const selected = form.querySelector('input[name="answer"]:checked');
        if (!selected) {
            return;
        }
        submitAnswer(config.cmid, cue.id, Number(selected.value))
            .then(result => {
                feedback.hidden = false;
                feedback.classList.toggle('alert-success', result.correct);
                feedback.classList.toggle('alert-danger', !result.correct);
                feedback.textContent = result.correct
                    ? `${config.strings.correct} ${result.feedback}`.trim()
                    : `${config.strings.incorrect} ${result.feedback}`.trim();
                if (result.correct || !result.required) {
                    completed.add(cue.id);
                    continueButton.hidden = false;
                }
                return result;
            })
            .catch(Notification.exception);
    };
    form.onsubmit = onSubmit;
    continueButton.onclick = () => {
        panel.hidden = true;
        adapter.play().catch?.(Notification.exception);
    };
};

const init = async config => {
    const root = document.querySelector('[data-region="interactivevideo"]');
    if (!root) {
        return;
    }
    let adapter;
    try {
        adapter = config.type === 'vimeo'
            ? await vimeoAdapter(root.querySelector('[data-region="vimeo"]'))
            : nativeAdapter(root.querySelector('[data-region="media"]'));
    } catch (error) {
        Notification.exception(error);
        return;
    }

    const completed = new Set(config.answered || []);
    let segmentStart = null;
    let previous = null;
    let lastDuration = 0;
    let saving = false;

    const flush = async current => {
        if (saving || segmentStart === null || current <= segmentStart) {
            return;
        }
        const start = Math.floor(segmentStart);
        const end = Math.floor(current);
        segmentStart = current;
        if (end <= start || end - start > 30) {
            return;
        }
        saving = true;
        try {
            const result = await recordProgress(config.cmid, start, end, Math.floor(lastDuration));
            updateProgress(root, result);
        } catch (error) {
            Notification.exception(error);
        } finally {
            saving = false;
        }
    };

    adapter.onTime((current, duration) => {
        if (!Number.isFinite(current) || !Number.isFinite(duration)) {
            return;
        }
        lastDuration = duration;
        const delta = previous === null ? 0 : current - previous;
        if (delta > 0 && delta <= 3) {
            segmentStart ??= previous;
        } else if (delta < 0 || delta > 3) {
            segmentStart = current;
        }
        previous = current;
        if (segmentStart !== null && current - segmentStart >= 8) {
            flush(current);
        }
        const cue = config.cues.find(item => !completed.has(item.id) && current >= item.time);
        if (cue) {
            flush(current);
            setupQuestion(root, adapter, config, cue, completed);
        }
    });
    adapter.onPause(() => previous !== null && flush(previous));
    adapter.onEnded(() => previous !== null && flush(previous));
};

return {init};
});
