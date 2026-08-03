import React, { useState } from 'react';

export default function SentencesUL({ data }) {
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
                <p className="text-slate-900">{item.text}</p>
                {revealed[i] && (
                  <p className="mt-1.5 text-emerald-700 font-medium text-sm" dir="rtl" lang="he">{item.answer}</p>
                )}
              </div>
              <button
                onClick={() => toggle(i)}
                className="shrink-0 text-xs text-slate-500 hover:text-slate-700 underline underline-offset-2 transition-colors"
              >
                {revealed[i] ? 'Hide' : 'Answer'}
              </button>
            </div>
          </div>
      ))}
    </div>
  );
}
