{{--
    WhatsApp-style voice-note player (self-contained markup).
    Expects: $voiceUrl (string), $voiceMeta (array|null: duration, waveform),
             $voicePlayerId (unique per message so multiple players coexist).
    Behaviour (CSS + delegated JS live in messages.blade.php): one shared
    Audio element, play/pause toggle, seekable waveform, live time label.
--}}
@php
    $vDuration = is_array($voiceMeta) && !empty($voiceMeta['duration']) ? (int) $voiceMeta['duration'] : 0;
    $vWave = [];
    if (is_array($voiceMeta) && !empty($voiceMeta['waveform'])) {
        $vWave = array_values(array_map('intval', (array) $voiceMeta['waveform']));
    }
@endphp
<div class="hv-voice" id="{{ $voicePlayerId }}" data-src="{{ $voiceUrl }}" data-duration="{{ $vDuration }}" data-wave="{{ implode(',', $vWave) }}" role="group" aria-label="Voice note">
    <button type="button" class="hv-play" aria-label="Play voice note">
        <i class="las la-play"></i>
    </button>
    <div class="hv-wave" aria-hidden="true"></div>
    <span class="hv-time">{{ $vDuration > 0 ? floor($vDuration / 60) . ':' . str_pad((string) ($vDuration % 60), 2, '0', STR_PAD_LEFT) : 'Voice' }}</span>
</div>
