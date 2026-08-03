import React from 'react';

// English defaults shown until /api/config loads the localized messages.
const HERO_BULLETS = [
  'Sign up via Telegram bot',
  'Select your level (א/ב/ג/ד)',
  'Choose convenient time-slots',
  'Get a free speaking partner',
];

const STEPS = [
  'Start the flow',
  'Choose the language',
  'Choose your levels',
  'Select convenient time slots',
  'See the matches',
  'Schedule a meeting',
];

export default function Home({ messages, botUrl }) {
  return (
    <>
      <section className="text-white py-6">
        <div className="max-w-6xl mx-auto px-6 py-16bullet">
          <div className="grid md:grid-cols-2 gap-12 items-center">
            <div className="order-2 md:order-1">
              <h1 className="text-4xl md:text-6xl font-extrabold leading-tight mb-6">{messages?.hero_title || 'Practice Hebrew one-on-one'}</h1>
              <ul className="text-lg md:text-xl opacity-95 space-y-2 text-left inline-block">
                {HERO_BULLETS.map((fallback, i) => (
                  <li key={i} className="flex items-center gap-2">
                    <span className="w-1.5 h-1.5 rounded-full bg-white/80 flex-shrink-0"></span>
                    {messages?.[`hero_bullet_${i + 1}`] || fallback}
                  </li>
                ))}
              </ul>
              <div className="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <a href={botUrl} className="inline-block text-center px-8 py-3 rounded-full bg-white text-indigo-600 font-semibold shadow hover:shadow-lg hover:-translate-y-0.5 transition">{messages?.start_now || 'Start now'}</a>
              </div>
            </div>
            <div className="order-1 md:order-2 flex justify-center">
              <img src="/img/logo.jpeg" alt="OlimChat.me" className="w-full max-w-xs md:max-w-sm rounded-2xl shadow-hero" />
            </div>
          </div>
        </div>
      </section>

      <section id="how-it-works" className="bg-gradient-to-br from-slate-50 to-slate-200/70">
        <div className="max-w-6xl mx-auto px-6 py-16">
          <h2 className="text-3xl md:text-4xl font-bold text-center text-gray-800">{messages?.how_it_works || 'How it works'}</h2>
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 mt-12">
            {[1,6,5,4,3,2].map((n, i) => (
              <figure key={i} className="bg-white rounded-xl shadow overflow-hidden group">
                <img src={`/img/${n}.jpeg`} alt="Step" className="w-full h-auto object-contain" />
                <figcaption className="p-4 text-center text-gray-700 font-medium">{messages?.[`step_${i+1}`] || STEPS[i]}</figcaption>
              </figure>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
