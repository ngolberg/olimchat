import React, { useEffect, useState, useRef, useMemo } from 'react';

export default function Meetings({ messages, auth, user, userImage, onOpenUploadModal }) {
  const [days, setDays] = useState(null);
  const [loading, setLoading] = useState(true);
  const [openDays, setOpenDays] = useState({});
  const [previewImage, setPreviewImage] = useState(null);
  const [loadingSlot, setLoadingSlot] = useState(null);
  const [selectedUserIds, setSelectedUserIds] = useState(null);
  const [filterOpen, setFilterOpen] = useState(false);
  const filterRef = useRef(null);

  useEffect(() => {
    if (!auth) {
      setLoading(false);
      return;
    }
    setLoading(true);
    fetch('/api/meetings', { credentials: 'same-origin' })
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

  // Auto-show upload modal after 5 seconds if user has no image
  useEffect(() => {
    if (!auth || userImage) return;
    const timer = setTimeout(() => {
      onOpenUploadModal();
    }, 5000);
    return () => clearTimeout(timer);
  }, [auth, userImage]);

  const allUsers = useMemo(() => {
    if (!days || days.length === 0) return [];
    const map = new Map();
    for (const day of days) {
      for (const slots of Object.values(day.times)) {
        for (const slot of slots) {
          if (!map.has(slot.user.id)) {
            map.set(slot.user.id, slot.user);
          }
        }
      }
    }
    return Array.from(map.values()).sort((a, b) => a.first_name.localeCompare(b.first_name));
  }, [days]);

  useEffect(() => {
    if (allUsers.length > 0 && selectedUserIds === null) {
      setSelectedUserIds(new Set(allUsers.map(u => u.id)));
    }
  }, [allUsers]);

  useEffect(() => {
    if (!filterOpen) return;
    const handleClickOutside = (e) => {
      if (filterRef.current && !filterRef.current.contains(e.target)) {
        setFilterOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [filterOpen]);

  const toggleUserFilter = (userId) => {
    setSelectedUserIds(prev => {
      const next = new Set(prev);
      if (next.has(userId)) {
        next.delete(userId);
      } else {
        next.add(userId);
      }
      return next;
    });
  };

  const toggleSelectAll = () => {
    const allSelected = selectedUserIds && selectedUserIds.size === allUsers.length;
    if (allSelected) {
      setSelectedUserIds(new Set());
    } else {
      setSelectedUserIds(new Set(allUsers.map(u => u.id)));
    }
  };

  const filteredDays = useMemo(() => {
    if (!days || !selectedUserIds) return days;
    return days.map(day => {
      const filteredTimes = {};
      for (const [time, slots] of Object.entries(day.times)) {
        const filtered = slots.filter(slot => selectedUserIds.has(slot.user.id));
        if (filtered.length > 0) {
          filteredTimes[time] = filtered;
        }
      }
      return { ...day, times: filteredTimes };
    }).filter(day => Object.keys(day.times).length > 0);
  }, [days, selectedUserIds]);

  const toggleDay = (idx) => {
    setOpenDays(prev => ({
      ...prev,
      [idx]: !prev[idx]
    }));
  };

  const handleScheduleMeeting = (slot, day) => {
    if (!slot?.user?.id) return;

    const slotKey = `schedule-${day.date}-${slot.time}-${slot.user.id}`;
    setLoadingSlot(slotKey);

    fetch('/api/meeting/create', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify({
        user_id: slot.user.id,
        date: day.date,
        time: slot.time
      }),
      credentials: 'same-origin'
    })
      .then(r => {
        if (!r.ok) throw new Error('Failed to schedule');
        return r.json();
      })
      .then(() => {
        fetch('/api/meetings', { credentials: 'same-origin' })
          .then(r => r.json())
          .then(data => setDays(data.days || []));
      })
      .catch(err => console.error(err))
      .finally(() => setLoadingSlot(null));
  };

  const handleMeetingAction = (meetingId, action) => {
    setLoadingSlot(`${action}-${meetingId}`);

    fetch(`/api/meeting/${action}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify({ meeting_id: meetingId }),
      credentials: 'same-origin'
    })
      .then(r => {
        if (!r.ok) throw new Error(`Failed to ${action}`);
        return r.json();
      })
      .then(() => {
        fetch('/api/meetings', { credentials: 'same-origin' })
          .then(r => r.json())
          .then(data => setDays(data.days || []));
      })
      .catch(err => console.error(err))
      .finally(() => setLoadingSlot(null));
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
        <div className="text-center mb-8">
          <h1 className="text-4xl font-extrabold text-slate-900 mb-4">{messages?.calendar_title || 'Calendar'}</h1>
          <p className="text-lg text-slate-600">{messages?.calendar_welcome?.replace('{name}', user?.name || '')}</p>
        </div>

        {allUsers.length > 1 && (
          <div className="relative mb-6" ref={filterRef}>
            <button
              onClick={() => setFilterOpen(prev => !prev)}
              className="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-700 hover:border-sky-300 hover:text-sky-600 transition-colors shadow-sm"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
              </svg>
              <span>{messages?.filter_users || 'Filter users'}</span>
              {selectedUserIds && selectedUserIds.size < allUsers.length && (
                <span className="bg-sky-100 text-sky-700 text-xs font-bold px-1.5 py-0.5 rounded-full">
                  {selectedUserIds.size}/{allUsers.length}
                </span>
              )}
              <svg
                className={`w-4 h-4 text-slate-400 transition-transform duration-200 ${filterOpen ? 'rotate-180' : ''}`}
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
              >
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            {filterOpen && (
              <div className="absolute left-0 top-full mt-2 w-72 bg-white border border-slate-200 rounded-xl shadow-lg z-30 py-2 max-h-72 overflow-y-auto">
                <label className="flex items-center gap-3 px-4 py-2 hover:bg-slate-50 cursor-pointer border-b border-slate-100">
                  <input
                    type="checkbox"
                    checked={selectedUserIds && selectedUserIds.size === allUsers.length}
                    onChange={toggleSelectAll}
                    className="w-4 h-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                  />
                  <span className="text-sm font-semibold text-slate-700">{messages?.filter_select_all || 'Select all'}</span>
                </label>
                {allUsers.map(u => (
                  <label key={u.id} className="flex items-center gap-3 px-4 py-2 hover:bg-slate-50 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={selectedUserIds?.has(u.id) || false}
                      onChange={() => toggleUserFilter(u.id)}
                      className="w-4 h-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                    />
                    <img src={u.image} alt={u.first_name} className="w-7 h-7 rounded-full object-cover border border-slate-100" />
                    <span className="text-sm text-slate-700 truncate">{u.first_name} {u.last_name}</span>
                  </label>
                ))}
              </div>
            )}
          </div>
        )}

        {filteredDays && filteredDays.length === 0 ? (
          <div className="bg-white rounded-2xl shadow-sm p-12 text-center border border-slate-200">
            <p className="text-xl text-slate-600">{messages?.no_matches_found || 'No matches found'}</p>
          </div>
        ) : (
        <div className="space-y-6">
          {filteredDays.map((day, idx) => (
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
                            <img
                              src={slot.user.image}
                              alt={slot.user.first_name}
                              className="w-12 h-12 rounded-full object-cover border border-slate-100 cursor-pointer hover:ring-2 hover:ring-sky-300 transition-all"
                              onClick={() => setPreviewImage({ src: slot.user.image, name: slot.user.first_name })}
                            />
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
                              {slot.meeting ? (
                                <div className="mt-1">
                                  <div className="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">
                                    Status: <span className="text-sky-600">{slot.meeting.status}</span>
                                  </div>
                                  {slot.meeting.status === 'waiting' && (
                                    <div className="flex gap-2">
                                      {slot.meeting.role === 'inviter' ? (
                                        <button
                                          onClick={() => handleMeetingAction(slot.meeting.id, 'cancel')}
                                          disabled={loadingSlot === `cancel-${slot.meeting.id}`}
                                          className="px-2 py-1 bg-red-600 text-white rounded-lg text-xs font-bold hover:bg-red-700 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                                        >
                                          {loadingSlot === `cancel-${slot.meeting.id}` ? (
                                            <div className="animate-spin rounded-full h-3 w-3 border-b-2 border-white"></div>
                                          ) : (
                                            'cancel invitation'
                                          )}
                                        </button>
                                      ) : (
                                        <>
                                          <button
                                            onClick={() => handleMeetingAction(slot.meeting.id, 'accept')}
                                            className="px-2 py-1 bg-green-600 text-white rounded-lg text-xs font-bold hover:bg-green-700 transition-colors shadow-sm"
                                          >
                                            Accept
                                          </button>
                                          <button
                                            onClick={() => handleMeetingAction(slot.meeting.id, 'decline')}
                                            className="px-2 py-1 bg-white text-red-600 border border-red-200 rounded-lg text-xs font-bold hover:bg-red-50 transition-colors shadow-sm"
                                          >
                                            Decline
                                          </button>
                                        </>
                                      )}
                                    </div>
                                  )}
                                </div>
                              ) : (
                                <button
                                  onClick={() => handleScheduleMeeting(slot, day)}
                                  disabled={loadingSlot === `schedule-${day.date}-${slot.time}-${slot.user.id}`}
                                  className="mt-2 w-full sm:w-auto px-3 py-1.5 bg-sky-600 text-white rounded-lg text-xs font-bold hover:bg-sky-700 transition-all shadow-sm flex items-center justify-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                  {loadingSlot === `schedule-${day.date}-${slot.time}-${slot.user.id}` ? (
                                    <div className="animate-spin rounded-full h-3 w-3 border-b-2 border-white"></div>
                                  ) : (
                                    <>
                                      <span>{messages?.schedule_meeting}</span>
                                      <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7"></path>
                                      </svg>
                                    </>
                                  )}
                                </button>
                              )}
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
        )}
      </div>

      {/* Image Preview Modal */}
      {previewImage && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" onClick={() => setPreviewImage(null)}>
          <div className="relative" onClick={e => e.stopPropagation()}>
            <button
              onClick={() => setPreviewImage(null)}
              className="absolute -top-3 -right-3 bg-white rounded-full p-1 shadow-lg text-slate-400 hover:text-slate-600 transition-colors z-10"
            >
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            <img
              src={previewImage.src}
              alt={previewImage.name}
              className="max-w-[80vw] max-h-[80vh] rounded-2xl object-contain shadow-2xl"
            />
          </div>
        </div>
      )}
    </section>
  );
}
