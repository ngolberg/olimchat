import React, { useEffect, useState, useRef } from 'react';

export default function Calendar({ messages, auth, user, userImage, setUserImage }) {
  const [days, setDays] = useState(null);
  const [loading, setLoading] = useState(true);
  const [openDays, setOpenDays] = useState({});
  const [showUploadModal, setShowUploadModal] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [uploadedPreview, setUploadedPreview] = useState(null);
  const [previewImage, setPreviewImage] = useState(null);
  const [loadingSlot, setLoadingSlot] = useState(null);
  const fileInputRef = useRef(null);

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

  // Auto-show upload modal after 5 seconds if user has no image
  useEffect(() => {
    if (!auth || userImage) return;
    const timer = setTimeout(() => {
      setShowUploadModal(true);
    }, 5000);
    return () => clearTimeout(timer);
  }, [auth, userImage]);

  const handlePhotoUpload = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setUploading(true);
    const formData = new FormData();
    formData.append('photo', file);

    fetch('/api/user/photo', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: formData,
      credentials: 'same-origin'
    })
      .then(r => {
        if (!r.ok) throw new Error('Upload failed');
        return r.json();
      })
      .then((data) => {
        if (data.image) {
          setUserImage(data.image);
          setUploadedPreview(data.image);
        }
      })
      .catch(err => {
        console.error(err);
        alert('Failed to upload photo.');
      })
      .finally(() => setUploading(false));
  };

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
        fetch('/api/calendar', { credentials: 'same-origin' })
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
        fetch('/api/calendar', { credentials: 'same-origin' })
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
      </div>

      {/* Photo Upload Modal */}
      {showUploadModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" onClick={() => { setShowUploadModal(false); setUploadedPreview(null); }}>
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-[600px] p-6" onClick={e => e.stopPropagation()}>
            <div className="flex justify-end mb-2">
              <button
                onClick={() => { setShowUploadModal(false); setUploadedPreview(null); }}
                className="text-slate-400 hover:text-slate-600 transition-colors"
              >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            {uploadedPreview ? (
              <div className="flex flex-col items-center">
                <img
                  src={uploadedPreview}
                  alt="Uploaded photo"
                  className="w-24 h-24 rounded-full object-cover border border-slate-100 mb-6"
                />
                <div className="flex flex-col sm:flex-row gap-3 w-full">
                  <button
                    onClick={() => { setShowUploadModal(false); setUploadedPreview(null); }}
                    className="flex-1 px-4 py-3 bg-sky-600 text-white rounded-xl font-bold hover:bg-sky-700 transition-colors shadow-sm"
                  >
                    {messages.upload_photo_another}
                  </button>
                  <button
                    onClick={() => { setUploadedPreview(null); fileInputRef.current?.click(); }}
                    className="flex-1 px-4 py-3 bg-white text-sky-600 border border-sky-200 rounded-xl font-bold hover:bg-sky-50 transition-colors shadow-sm"
                  >
                    {messages.upload_photo_done}
                  </button>
                </div>
              </div>
            ) : (
              <>
                <h3 className="text-xl font-bold text-slate-900 mb-3">
                  {messages.upload_photo_title}
                </h3>
                <p className="text-slate-600 mb-6">
                  {messages.upload_photo_message}
                </p>

                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  onChange={handlePhotoUpload}
                  className="hidden"
                />

                <button
                  onClick={() => fileInputRef.current?.click()}
                  disabled={uploading}
                  className="w-full px-4 py-3 bg-sky-600 text-white rounded-xl font-bold hover:bg-sky-700 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                  {uploading ? (
                    <>
                      <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                      <span>Uploading...</span>
                    </>
                  ) : (
                    <>
                      <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span>{messages.upload_photo_title}</span>
                    </>
                  )}
                </button>
              </>
            )}
          </div>
        </div>
      )}

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
