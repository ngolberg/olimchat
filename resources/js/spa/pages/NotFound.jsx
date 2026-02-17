import React from 'react';
import { Link } from 'react-router-dom';
import { FileQuestion } from 'lucide-react';

export default function NotFound({ messages }) {
  return (
    <section className="bg-gradient-to-br from-slate-50 to-slate-200/70 flex-grow flex items-center justify-center">
      <div className="max-w-6xl mx-auto px-6 py-16 text-center">
        <div className="bg-white border border-slate-200 text-slate-900 p-6 md:p-8 rounded-2xl shadow-xl w-full max-w-2xl">
          <div className="flex justify-center mb-6">
            <div className="bg-sky-50 p-4 rounded-full">
              <FileQuestion className="w-12 h-12 text-sky-600" />
            </div>
          </div>
          <h1 className="text-3xl md:text-4xl font-extrabold mb-4 text-slate-800">
            {messages.not_found_title}
          </h1>
          <p className="text-lg md:text-xl mb-8 leading-relaxed text-slate-600">
            {messages.not_found_message}
          </p>
          <Link
            to="/meetings"
            className="inline-flex items-center gap-2 px-6 md:px-10 py-3 md:py-4 rounded-full bg-sky-600 text-white font-bold shadow-lg hover:bg-sky-700 hover:shadow-xl hover:-translate-y-0.5 transition-all"
          >
            <span>{messages.not_found_go_calendar}</span>
          </Link>
        </div>
      </div>
    </section>
  );
}
