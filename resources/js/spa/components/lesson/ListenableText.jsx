import React from 'react';
import { speak, isSupported } from '../../lib/speech.js';

export default function ListenableText({ data }) {
  const handleMouseUp = () => {
    const selection = window.getSelection()?.toString().trim();
    if (selection) speak(selection);
  };

  const handleTouchEnd = () => {
    const selection = window.getSelection()?.toString().trim();
    if (selection) speak(selection);
  };

  return (
    <div className="space-y-3">
      {isSupported() && (
        <p className="text-sm text-sky-600 bg-sky-50 border border-sky-100 rounded-lg px-3 py-2 flex items-center gap-2">
          <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.536 8.464a5 5 0 010 7.072M12 6v12m0 0l-3-3m3 3l3-3M9.464 8.464a5 5 0 000 7.072" />
          </svg>
          {data.hint}
        </p>
      )}
      <div
        className="text-slate-800 leading-8 text-base select-text rounded-xl bg-slate-50 border border-slate-100 p-4 cursor-text"
        onMouseUp={handleMouseUp}
        onTouchEnd={handleTouchEnd}
        dir="rtl"
        lang="he"
      >
        {data.text}
      </div>
    </div>
  );
}
