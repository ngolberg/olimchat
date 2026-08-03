const supported = typeof window !== 'undefined' && 'speechSynthesis' in window;

export function isSupported() {
  return supported;
}

function findVoice(lang) {
  return window.speechSynthesis.getVoices().find(v => v.lang.startsWith(lang.split('-')[0]));
}

function speakWithVoice(text, lang, rate) {
  window.speechSynthesis.cancel();
  const utt = new SpeechSynthesisUtterance(text);
  utt.lang = lang;
  utt.rate = rate;
  const match = findVoice(lang);
  if (match) utt.voice = match;
  window.speechSynthesis.speak(utt);
}

export function speak(text, lang = 'he-IL', rate = 0.75) {
  if (!supported) return;

  // getVoices() is empty until voiceschanged fires in Chrome/Edge.
  if (window.speechSynthesis.getVoices().length > 0) {
    speakWithVoice(text, lang, rate);
  } else {
    window.speechSynthesis.addEventListener(
      'voiceschanged',
      () => speakWithVoice(text, lang, rate),
      { once: true },
    );
  }
}

export function cancel() {
  if (supported) window.speechSynthesis.cancel();
}
