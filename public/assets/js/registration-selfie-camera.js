/*
 * Live-camera selfie capture with client-side quality gating.
 *
 * Why this exists: the selfie used to be a plain file input, so members
 * uploaded screenshots, photos-of-photos and very dark images. The AI identity
 * service then answered FACE_NOT_DETECTED *after* registration had finished,
 * leaving the account stuck in manual review with nothing the member could do
 * from that screen. A real case on this install came back with
 * brightness_score 13.2 while blur (94.4) and resolution (97.7) were fine -
 * the photo was simply too dark to find a face in.
 *
 * So: capture from the camera, measure the same things the model cares about,
 * and refuse to accept the frame until it would plausibly pass. The check is a
 * pre-filter, not a guarantee - the model still has the final say.
 */
(function () {
    'use strict';

    // Tuned against the model's own thresholds. Mean luma is 0-255.
    var MIN_BRIGHTNESS = 70;
    var MAX_BRIGHTNESS = 205;
    var MIN_SHARPNESS = 22;      // variance of a Laplacian-style edge response
    var MIN_SHORT_SIDE = 480;    // px, before any downscale
    var CAPTURE_MAX_SIDE = 1280; // keep the upload reasonable
    var JPEG_QUALITY = 0.92;
    var MAX_UPLOAD_BYTES = 10 * 1024 * 1024;

    function t(key, fallback) {
        var msgs = (window.registrationSelfieMessages || {});
        return msgs[key] || fallback;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-selfie-capture]').forEach(setup);
    });

    function setup(root) {
        var video = root.querySelector('[data-selfie-video]');
        var canvas = root.querySelector('[data-selfie-canvas]');
        var preview = root.querySelector('[data-selfie-preview]');
        var placeholder = root.querySelector('[data-selfie-placeholder]');
        var fileInput = root.querySelector('[data-selfie-file]');
        var feedback = root.querySelector('[data-selfie-feedback]');
        var btnStart = root.querySelector('[data-selfie-start]');
        var btnShoot = root.querySelector('[data-selfie-shoot]');
        var btnRetake = root.querySelector('[data-selfie-retake]');
        var btnUpload = root.querySelector('[data-selfie-upload-open]');
        var picker = root.querySelector('[data-selfie-picker]');
        var stream = null;

        if (!video || !canvas || !fileInput) return;

        function say(html, level) {
            var cls = level === 'ok' ? 'text-success' : (level === 'warn' ? 'text-warning' : 'text-danger');
            feedback.innerHTML = '<span class="' + cls + '">' + html + '</span>';
        }

        function show(el, on) { if (el) el.classList.toggle('d-none', !on); }

        function stop() {
            if (stream) {
                stream.getTracks().forEach(function (track) { track.stop(); });
                stream = null;
            }
        }

        // Mark the hidden input valid/invalid so validateCurrentStep() can read it.
        function setAccepted(accepted) {
            root.dataset.selfieAccepted = accepted ? '1' : '0';
            fileInput.classList.toggle('is-invalid', !accepted);
        }

        setAccepted(false);

        btnStart.addEventListener('click', function () {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                say(t('unsupported', 'This browser cannot open the camera. Please use a modern browser on a device with a camera.'));
                return;
            }

            say(t('opening', 'Opening camera…'), 'warn');

            navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 1280 } },
                audio: false
            }).then(function (s) {
                stream = s;
                video.srcObject = s;
                video.play();
                show(video, true);
                show(preview, false);
                show(placeholder, false);
                show(btnShoot, true);
                show(btnRetake, false);
                btnStart.classList.add('d-none');
                say(t('framing', 'Face the camera in even light, then press Capture.'), 'warn');
            }).catch(function (err) {
                var denied = err && (err.name === 'NotAllowedError' || err.name === 'SecurityError');
                say(denied
                    ? t('denied', 'Camera permission was refused. Allow camera access to continue.')
                    : t('nocam', 'No camera could be started. Check that another app is not using it.'));
            });
        });

        /**
         * Shared tail for both paths: the frame is already on the canvas and
         * has passed `assess`. Encode it, attach it to the submitted input and
         * show it back to the member.
         */
        function acceptCanvas(verdict, sourceLabel) {
            canvas.toBlob(function (blob) {
                if (!blob) {
                    say(t('encodefail', 'Could not process the photo. Please capture again.'));
                    setAccepted(false);
                    return;
                }

                var file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
                try {
                    var dt = new DataTransfer();
                    dt.items.add(file);
                    fileInput.files = dt.files;
                } catch (e) {
                    say(t('attachfail', 'This browser cannot attach the captured photo. Please update your browser.'));
                    setAccepted(false);
                    return;
                }

                preview.src = canvas.toDataURL('image/jpeg', JPEG_QUALITY);
                show(preview, true);
                show(video, false);
                show(btnShoot, false);
                show(placeholder, false);
                show(btnRetake, true);
                btnStart.classList.add('d-none');
                stop();
                setAccepted(true);
                say(t('accepted', 'Selfie accepted.') + ' <span class="opacity-70">(' + sourceLabel + ': ' + verdict.detail + ')</span>', 'ok');
            }, 'image/jpeg', JPEG_QUALITY);
        }

        /*
         * Upload fallback. Same gate as the camera: a device without a working
         * camera should not become a way to submit an unusable selfie.
         */
        if (btnUpload && picker) {
            btnUpload.addEventListener('click', function () { picker.click(); });

            picker.addEventListener('change', function () {
                var chosen = picker.files && picker.files[0];
                if (!chosen) return;

                if (!/^image\//.test(chosen.type)) {
                    say(t('notimage', 'That file is not an image. Choose a JPG or PNG photo.'));
                    setAccepted(false);
                    picker.value = '';
                    return;
                }

                if (chosen.size > MAX_UPLOAD_BYTES) {
                    say(t('toobig', 'That photo is too large. Choose one under 10 MB.'));
                    setAccepted(false);
                    picker.value = '';
                    return;
                }

                say(t('reading', 'Checking the photo...'), 'warn');

                var url = URL.createObjectURL(chosen);
                var img = new Image();

                img.onload = function () {
                    URL.revokeObjectURL(url);

                    var scale = Math.min(1, CAPTURE_MAX_SIDE / Math.max(img.naturalWidth, img.naturalHeight));
                    canvas.width = Math.round(img.naturalWidth * scale);
                    canvas.height = Math.round(img.naturalHeight * scale);

                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    // Resolution is judged on the ORIGINAL short side, not the
                    // downscaled canvas, so a big photo is not penalised for
                    // being resized here.
                    var verdict = assess(ctx, canvas, Math.min(img.naturalWidth, img.naturalHeight));

                    if (!verdict.ok) {
                        say(verdict.message + ' <span class="opacity-70">(' + verdict.detail + ')</span>');
                        setAccepted(false);
                        fileInput.value = '';
                        picker.value = '';
                        return;
                    }

                    acceptCanvas(verdict, t('uploadedLabel', 'uploaded'));
                    picker.value = '';
                };

                img.onerror = function () {
                    URL.revokeObjectURL(url);
                    say(t('loadfail', 'Could not read that photo. Try another one.'));
                    setAccepted(false);
                    picker.value = '';
                };

                img.src = url;
            });
        }

        btnShoot.addEventListener('click', function () {
            if (!video.videoWidth) {
                say(t('notready', 'The camera is not ready yet. Wait a moment and try again.'));
                return;
            }

            var w = video.videoWidth;
            var h = video.videoHeight;
            var scale = Math.min(1, CAPTURE_MAX_SIDE / Math.max(w, h));
            canvas.width = Math.round(w * scale);
            canvas.height = Math.round(h * scale);

            var ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            var verdict = assess(ctx, canvas, Math.min(w, h));

            if (!verdict.ok) {
                // Keep the camera live so they can just try again.
                say(verdict.message + ' <span class="opacity-70">(' + verdict.detail + ')</span>');
                setAccepted(false);
                return;
            }

            acceptCanvas(verdict, t('capturedLabel', 'captured'));
        });

        btnRetake.addEventListener('click', function () {
            setAccepted(false);
            fileInput.value = '';
            show(preview, false);
            show(btnRetake, false);
            btnStart.classList.remove('d-none');
            if (btnUpload) btnUpload.classList.remove('d-none');
            show(placeholder, true);
            feedback.innerHTML = '';
            if (picker) picker.value = '';
        });

        window.addEventListener('beforeunload', stop);
    }

    /**
     * Measure the frame the way the model does: is it bright enough, sharp
     * enough and big enough to find a face in?
     */
    function assess(ctx, canvas, sourceShortSide) {
        if (sourceShortSide < MIN_SHORT_SIDE) {
            return {
                ok: false,
                message: t('lowres', 'The photo is too small for verification - it must be at least 480 pixels on the short side.'),
                detail: sourceShortSide + 'px'
            };
        }

        var data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;

        // Downscale to a grid for the statistics; full-res is needless work.
        var step = Math.max(1, Math.floor(Math.min(canvas.width, canvas.height) / 240));
        var luma = [];
        var sum = 0;

        for (var y = 0; y < canvas.height; y += step) {
            var row = [];
            for (var x = 0; x < canvas.width; x += step) {
                var i = (y * canvas.width + x) * 4;
                // Rec. 601 luma
                var l = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                row.push(l);
                sum += l;
            }
            luma.push(row);
        }

        var count = luma.length * (luma[0] ? luma[0].length : 0);
        if (!count) {
            return { ok: false, message: t('readfail', 'Could not read the frame.'), detail: 'empty' };
        }

        var mean = sum / count;

        if (mean < MIN_BRIGHTNESS) {
            return {
                ok: false,
                message: t('dark', 'Too dark - use brighter, even light and try again.'),
                detail: 'brightness ' + Math.round(mean) + '/' + MIN_BRIGHTNESS
            };
        }

        if (mean > MAX_BRIGHTNESS) {
            return {
                ok: false,
                message: t('bright', 'Too bright - avoid direct light and try again.'),
                detail: 'brightness ' + Math.round(mean) + '/' + MAX_BRIGHTNESS
            };
        }

        // Laplacian-style edge response; its variance tracks focus.
        var edges = [];
        var edgeSum = 0;
        for (var r = 1; r < luma.length - 1; r++) {
            for (var c = 1; c < luma[r].length - 1; c++) {
                var v = Math.abs(
                    4 * luma[r][c] - luma[r - 1][c] - luma[r + 1][c] - luma[r][c - 1] - luma[r][c + 1]
                );
                edges.push(v);
                edgeSum += v;
            }
        }

        if (edges.length) {
            var eMean = edgeSum / edges.length;
            var variance = 0;
            for (var k = 0; k < edges.length; k++) {
                variance += (edges[k] - eMean) * (edges[k] - eMean);
            }
            variance /= edges.length;
            var sharpness = Math.sqrt(variance);

            if (sharpness < MIN_SHARPNESS) {
                return {
                    ok: false,
                    message: t('blurry', 'Too blurry - use a sharper photo and try again.'),
                    detail: 'sharpness ' + Math.round(sharpness) + '/' + MIN_SHARPNESS
                };
            }

            return {
                ok: true,
                detail: 'brightness ' + Math.round(mean) + ', sharpness ' + Math.round(sharpness)
            };
        }

        return { ok: true, detail: 'brightness ' + Math.round(mean) };
    }
})();
