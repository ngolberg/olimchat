import React, { useEffect, useState } from 'react';

export default function MyMeetings({ messages, auth, user }) {
  const [meetings, setMeetings] = useState(null);
  const [loading, setLoading] = useState(true);
  const [loadingAction, setLoadingAction] = useState(null);

  const fetchMeetings = () => {
    fetch('/api/my-meetings', { credentials: 'same-origin' })
      .then(r => {
        if (!r.ok) throw new Error('Failed');
        return r.json();
      })
      .then(data => setMeetings(data.meetings || []))
      .catch(() => setMeetings([]))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    if (!auth) {
      setLoading(false);
      return;
    }
    setLoading(true);
    fetchMeetings();
  }, [auth]);

  const handleAction = (meetingId, action) => {
    const key = `${action}-${meetingId}`;
    setLoadingAction(key);

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
      .then(() => fetchMeetings())
      .catch(err => console.error(err))
      .finally(() => setLoadingAction(null));
  };

  if (loading) {
    return (
      <section className="bg-gradient-to-br from-slate-50 to-slate-200/70 flex-grow py-12 flex items-center justify-center">
        <div className="flex flex-col items-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-sky-600"></div>
        </div>
      </section>
    );
  }

  if (!auth) {
    return (
      <section className="bg-gradient-to-br from-slate-50 to-slate-200/70 flex-grow py-12">
        <div className="max-w-6xl mx-auto px-6">
          <div className="bg-white rounded-2xl shadow-sm p-12 text-center border border-slate-200">
            <p className="text-xl text-slate-600">Please sign in via Telegram bot to see your meetings.</p>
            <a href="https://t.me/HebrewPeer2Peer_bot" className="mt-4 inline-block px-6 py-2 bg-sky-600 text-white rounded-lg">Open Bot</a>
          </div>
        </div>
      </section>
    );
  }

  if (!meetings || meetings.length === 0) {
    return (
      <section className="bg-gradient-to-br from-slate-50 to-slate-200/70 flex-grow py-12">
        <div className="max-w-6xl mx-auto px-6">
          <div className="text-center mb-12">
            <h1 className="text-4xl font-extrabold text-slate-900 mb-4">{messages?.my_meetings_title || 'My Meetings'}</h1>
          </div>
          <div className="bg-white rounded-2xl shadow-sm p-12 text-center border border-slate-200">
            <p className="text-xl text-slate-600">{messages?.my_meetings_none || 'You have no upcoming meetings.'}</p>
          </div>
        </div>
      </section>
    );
  }

  const Spinner = () => (
    <div className="animate-spin rounded-full h-3 w-3 border-b-2 border-white"></div>
  );

  return (
    <section className="bg-gradient-to-br from-slate-50 to-slate-200/70 flex-grow py-12">
      <div className="max-w-6xl mx-auto px-6">
        <div className="text-center mb-12">
          <h1 className="text-4xl font-extrabold text-slate-900 mb-4">{messages?.my_meetings_title || 'My Meetings'}</h1>
          <p className="text-lg text-slate-600">{messages?.my_meetings_welcome?.replace('{name}', user?.name || '')}</p>
        </div>

        <div className="space-y-4">
          {meetings.map(meeting => {
            const statusLabel = meeting.status === 'accepted'
              ? (messages?.my_meetings_status_accepted || 'Confirmed')
              : (messages?.my_meetings_status_waiting || 'Waiting');
            const statusColor = meeting.status === 'accepted' ? 'text-green-600 bg-green-50' : 'text-amber-600 bg-amber-50';
            const roleLabel = meeting.role === 'invitee'
              ? (messages?.my_meetings_role_invited_you || 'invited you')
              : (messages?.my_meetings_role_you_invited || 'you invited');

            const showAcceptDecline = meeting.role === 'invitee' && meeting.status === 'waiting';
            const showCancel = (meeting.role === 'inviter' && meeting.status === 'waiting') || meeting.status === 'accepted';

            return (
              <div key={meeting.id} className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition-shadow flex items-center gap-4">
                <img
                  src={meeting.partner.image}
                  alt={meeting.partner.name}
                  className="w-14 h-14 rounded-full object-cover border border-slate-100"
                />
                <div className="flex-grow min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <h3 className="font-bold text-slate-800 text-sm">
                      {meeting.partner.username ? (
                        <a href={`https://t.me/${meeting.partner.username}`} target="_blank" className="text-sky-600 hover:underline" rel="noreferrer">
                          {meeting.partner.name}
                        </a>
                      ) : (
                        meeting.partner.name
                      )}
                    </h3>
                    <span className="text-xs text-slate-400">{roleLabel}</span>
                  </div>
                  <div className="text-sm text-slate-600 mt-0.5">
                    {meeting.day_label}, {meeting.time}
                  </div>
                  <span className={`inline-block mt-1 text-xs font-medium px-2 py-0.5 rounded-full ${statusColor}`}>
                    {statusLabel}
                  </span>
                </div>
                <div className="flex gap-2 flex-shrink-0">
                  {showAcceptDecline && (
                    <>
                      <button
                        onClick={() => handleAction(meeting.id, 'accept')}
                        disabled={loadingAction === `accept-${meeting.id}`}
                        className="px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-bold hover:bg-green-700 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                      >
                        {loadingAction === `accept-${meeting.id}` ? <Spinner /> : (messages?.my_meetings_btn_accept || 'Accept')}
                      </button>
                      <button
                        onClick={() => handleAction(meeting.id, 'decline')}
                        disabled={loadingAction === `decline-${meeting.id}`}
                        className="px-3 py-1.5 bg-white text-red-600 border border-red-200 rounded-lg text-xs font-bold hover:bg-red-50 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                      >
                        {loadingAction === `decline-${meeting.id}` ? <Spinner /> : (messages?.my_meetings_btn_decline || 'Decline')}
                      </button>
                    </>
                  )}
                  {showCancel && (
                    <button
                      onClick={() => handleAction(meeting.id, 'cancel')}
                      disabled={loadingAction === `cancel-${meeting.id}`}
                      className="px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs font-bold hover:bg-red-700 transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                    >
                      {loadingAction === `cancel-${meeting.id}` ? <Spinner /> : (messages?.my_meetings_btn_cancel || 'Cancel')}
                    </button>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
