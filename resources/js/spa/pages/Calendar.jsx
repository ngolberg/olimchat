import React, { useEffect, useState } from 'react';

export default function Calendar({ messages, auth, user }) {
  const [days, setDays] = useState(null);
  const [loading, setLoading] = useState(true);
  const [openDays, setOpenDays] = useState({});

  useEffect(() => {
    if (!auth) {
      setLoading(false);
      return;
    }
    setLoading(true);
    fetch('/api/calendar', { credentials: 'same-origin' })
      .then(r => {
        if (!r.ok) throw new Error('Failed');
        return r.json();
      })
      .then((data) => {
        const fetchedDays = data.days || [];
        setDays(fetchedDays);
        // Open the first day by default
        if (Object.keys(fetchedDays).length > 0) {
          setOpenDays({ 0: true });
        }
      })
      .catch(() => setDays([]))
      .finally(() => setLoading(false));
  }, [auth]);

  const toggleDay = (idx) => {
    setOpenDays(prev => ({
      ...prev,
      [idx]: !prev[idx]
    }));
  };

  const handleScheduleMeeting = (slot) => {
    if (!window.confirm(messages?.confirm_schedule || 'Do you want to schedule a meeting?')) {
      return;
    }

    fetch('/api/meeting/create', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify({
        user_id: slot.user.id, // though not used by backend yet
        time: slot.time
      }),
      credentials: 'same-origin'
    })
      .then(r => {
        if (!r.ok) throw new Error('Failed to schedule');
        return r.json();
      })
      .then(() => {
        alert(messages?.meeting_scheduled || 'Meeting scheduled successfully! Check your Telegram bot.');
      })
      .catch(err => {
        console.error(err);
        alert(messages?.error_scheduling || 'Failed to schedule meeting.');
      });
  };

  if (loading) {
    return (
      <section className="bg-gradient-to-br from-slate-50 to-slate-200/70 flex-grow py-12 flex items-center justify-center">
        <div className="flex flex-col items-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-sky-600"></div>
          <p className="mt-4 text-slate-600 font-medium">Loading calendar...</p>
        </div>
      </section>
    );
  }

  if (!auth) {
    return (
      <section className="bg-gradient-to-br from-slate-50 to-slate-200/70 flex-grow py-12">
        <div className="max-w-6xl mx-auto px-6">
          <div className="bg-white rounded-2xl shadow-sm p-12 text-center border border-slate-200">
            <p className="text-xl text-slate-600">Please sign in via Telegram bot to see your calendar.</p>
            <a href="https://t.me/HebrewPeer2Peer_bot" className="mt-4 inline-block px-6 py-2 bg-sky-600 text-white rounded-lg">Open Bot</a>
          </div>
        </div>
      </section>
    );
  }

  if (!days || days.length === 0) {
    return (
      <section className="bg-gradient-to-br from-slate-50 to-slate-200/70 flex-grow py-12">
        <div className="max-w-6xl mx-auto px-6">
          <div className="bg-white rounded-2xl shadow-sm p-12 text-center border border-slate-200">
            <p className="text-xl text-slate-600">{messages?.no_matches_found || 'No matches found'}</p>
          </div>
        </div>
      </section>
    );
  }

  return (
    <section className="bg-gradient-to-br from-slate-50 to-slate-200/70 flex-grow py-12">
      <div className="max-w-6xl mx-auto px-6">
        <div className="text-center mb-12">
          <h1 className="text-4xl font-extrabold text-slate-900 mb-4">{messages?.calendar_title || 'Calendar'}</h1>
          <p className="text-lg text-slate-600">{messages?.calendar_welcome?.replace('{name}', user?.name || '')}</p>
        </div>

        <div className="space-y-6">
          {days.map((day, idx) => (
            <div className="relative" key={idx}>
              <div
                className="sticky top-20 z-10 bg-slate-50/90 backdrop-blur-md py-3 mb-2 cursor-pointer group rounded-xl"
                onClick={() => toggleDay(idx)}
              >
                <div className="flex items-center justify-between">
                  <h2 className="text-2xl font-bold text-slate-800 flex items-center gap-3">
                    <span className={`w-2 h-8 bg-sky-500 rounded-full transition-all ${openDays[idx] ? 'scale-y-110' : ''} group-hover:scale-y-110`}></span>
                    {day.label}
                  </h2>
                  <svg
                    className={`w-6 h-6 text-slate-400 transition-transform duration-300 mr-2 ${openDays[idx] ? 'rotate-180' : ''}`}
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                </div>
              </div>

              {openDays[idx] && (
                <div className="space-y-4 mt-4 transition-all duration-200">
                  {Object.entries(day.times).map(([time, slots]) => (
                    <div className="grid grid-cols-1 md:grid-cols-[100px_1fr] gap-4 items-start" key={time}>
                      <div className="pt-4">
                        <span className="text-lg font-semibold text-sky-600 bg-sky-50 px-3 py-1 rounded-lg">
                          {time}
                        </span>
                      </div>
                      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {slots.map((slot, i) => (
                          <div key={i} className="bg-white p-3 rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition-shadow flex items-center gap-3">
                            <img src={slot.user.image} alt={slot.user.first_name} className="w-12 h-12 rounded-full object-cover border border-slate-100" />
                            <div className="flex-grow min-w-0">
                              <h3 className="font-bold text-slate-800 text-sm truncate">
                                {slot.user.username ? (
                                  <a href={`https://t.me/${slot.user.username}`} target="_blank" className="text-sky-600 hover:underline" rel="noreferrer">
                                    {slot.user.first_name} {slot.user.last_name}
                                  </a>
                                ) : (
                                  `${slot.user.first_name} ${slot.user.last_name}`
                                )}
                              </h3>
                              <button
                                onClick={() => handleScheduleMeeting(slot)}
                                className="mt-1 text-sky-600 hover:text-sky-700 font-semibold text-xs transition-colors flex items-center gap-1"
                              >
                                <span>{messages?.schedule_meeting || 'Schedule meeting'}</span>
                                <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                              </button>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
