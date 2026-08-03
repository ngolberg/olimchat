import React from 'react';
import DOMPurify from 'dompurify';

export default function SimpleText({ data }) {
  const clean = DOMPurify.sanitize(data.html);
  return (
    <div
      className="prose prose-slate max-w-none text-slate-700 [&_h1]:text-xl [&_h1]:font-bold [&_h2]:text-lg [&_h2]:font-semibold [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_li]:mb-1 [&_p]:mb-3 [&_strong]:font-semibold [&_em]:italic"
      dangerouslySetInnerHTML={{ __html: clean }}
    />
  );
}
