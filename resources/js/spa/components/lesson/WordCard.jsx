import React, { useState } from 'react';
import { speak } from '../../lib/speech.js';

export default function WordCard({ item }) {
  const [open, setOpen] = useState(false);

  return (
    <div className="border border-slate-200 rounded-xl overflow-hidden">
      <button
        onClick={() => setOpen(o => !o)}
        className="w-full flex items-center justify-between px-4 py-3 bg-white hover:bg-slate-50 transition-colors text-left gap-3"
      >
        <div className="flex items-center gap-3 min-w-0">
          <button
            onClick={e => { e.stopPropagation(); speak(item.word); }}
            className="shrink-0 w-7 h-7 flex items-center justify-center rounded-full bg-sky-50 hover:bg-sky-100 text-sky-600 transition-colors"
            aria-label={`Play ${item.word}`}
          >
            <svg className="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M8 5v14l11-7z" />
            </svg>
          </button>
          <span className="font-semibold text-slate-900" dir="rtl" lang="he">{item.word}</span>
          <span className="text-slate-400 text-sm truncate">{item.transcription}</span>
        </div>
        <div className="flex items-center gap-3 shrink-0">
          <span className="text-slate-600 text-sm hidden sm:block">{item.translation}</span>
          <svg
            className={`w-4 h-4 text-slate-400 transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
      </button>

      {open && (
        <div className="border-t border-slate-100 px-4 py-3 bg-slate-50 space-y-3">
          <p className="text-slate-600 text-sm sm:hidden">{item.translation}</p>

          {item.is_verb && item.forms && (
            <div className="overflow-x-auto">
              <table className="text-sm w-full border-collapse">
                <tbody>
                  {Object.entries(item.forms).map(([pronoun, form]) => (
                    <tr key={pronoun} className="border-b border-slate-100 last:border-0">
                      <td className="py-1 pr-4 text-slate-500 w-1/2">{pronoun}</td>
                      <td className="py-1 font-medium text-slate-800" dir="rtl" lang="he">{form}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          <div className="space-y-1.5">
            {item.sentences.map((s, i) => (
              <div key={i} className="flex items-start gap-2">
                <button
                  onClick={() => speak(s)}
                  className="shrink-0 mt-0.5 w-5 h-5 flex items-center justify-center rounded-full bg-sky-100 hover:bg-sky-200 text-sky-600 transition-colors"
                  aria-label="Play sentence"
                >
                  <svg className="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z" />
                  </svg>
                </button>
                <p className="text-slate-700 text-sm" dir="rtl" lang="he">{s}</p>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
