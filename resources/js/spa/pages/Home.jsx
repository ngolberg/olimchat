import React from 'react';

export default function Home({ messages }) {
  return (
    <>
      <section className="text-white">
        <div className="max-w-6xl mx-auto px-6 py-16 text-center">
          <div className="grid md:grid-cols-2 gap-12 items-center">
            <div className="order-2 md:order-1">
              <h1 className="text-4xl md:text-6xl font-extrabold leading-tight mb-4">{messages?.hero_title || 'Practice Hebrew with peers'}</h1>
              <p className="text-lg md:text-xl opacity-95">{messages?.hero_subtitle || ''}</p>
              <p className="text-lg md:text-xl opacity-95 mt-2">{messages?.hero_description || ''}</p>
              <div className="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="https://t.me/HebrewPeer2Peer_bot" className="inline-block px-8 py-3 rounded-full bg-white text-indigo-600 font-semibold shadow hover:shadow-lg hover:-translate-y-0.5 transition">{messages?.start_now || 'Start now'}</a>
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
                <figcaption className="p-4 text-center text-gray-700 font-medium">{messages?.[`step_${i+1}`] || `Step ${i+1}`}</figcaption>
              </figure>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
