import React, { useState } from 'react';
import { speak } from '../../lib/speech.js';

export default function SentencesLL({ data }) {
  const [revealed, setRevealed] = useState({});

  const toggle = (i) => setRevealed(r => ({ ...r, [i]: !r[i] }));

  return (
    <div className="space-y-2">
      {data.items.map((item, i) => (
          <div key={i} className={`rounded-xl overflow-hidden ${i % 2 === 0 ? 'bg-sky-50' : 'bg-violet-50'}`}>
            <div className="flex items-start gap-3 px-4 py-3">
              <span className="shrink-0 w-6 h-6 rounded-full bg-white/70 text-slate-500 text-xs flex items-center justify-center font-semibold mt-0.5 tabular-nums">
                {i + 1}
              </span>
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 flex-wrap">
                  <p className="text-slate-900 font-medium" dir="rtl" lang="he">{item.text}</p>
                  <button
                    onClick={() => speak(item.text)}
                    className="shrink-0 w-6 h-6 flex items-center justify-center rounded-full bg-white/70 hover:bg-white text-sky-600 transition-colors"
                    aria-label="Play"
                  >
                    <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M8 5v14l11-7z" />
                    </svg>
                  </button>
                </div>
                {revealed[i] && (
                  <div className="mt-1.5 space-y-0.5">
                    <p className="text-slate-500 text-sm">{item.transcription}</p>
                    <p className="text-slate-600 text-sm">{item.translation}</p>
                  </div>
                )}
              </div>
              <button
                onClick={() => toggle(i)}
                className="shrink-0 text-xs text-slate-500 hover:text-slate-700 underline underline-offset-2 transition-colors"
              >
                {revealed[i] ? 'Hide' : 'Show'}
              </button>
            </div>
          </div>
      ))}
    </div>
  );
}
