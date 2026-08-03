import React, { useEffect, useState } from 'react';
import { Navigate, Link } from 'react-router-dom';
import { api } from '../lib/api.js';

function Stars({ value }) {
  return (
    <span className="flex items-center gap-0.5">
      {[1, 2, 3, 4, 5].map(s => (
        <svg key={s} className={`w-3.5 h-3.5 ${s <= Math.round(value) ? 'text-amber-400' : 'text-slate-200'}`} fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
        </svg>
      ))}
    </span>
  );
}

function EditModal({ lesson, onClose, onSaved }) {
  const [prompt, setPrompt] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      const updated = await api.editLesson(lesson.hash_id, prompt);
      onSaved(updated);
    } catch (err) {
      setError(err.data?.message || 'Failed to apply changes. Please try again.');
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" onClick={onClose}>
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" onClick={e => e.stopPropagation()}>
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-bold text-slate-900">Edit lesson</h3>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600 transition-colors">
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <p className="text-sm text-slate-500 mb-4">Describe what to change in <span className="font-medium text-slate-700">"{lesson.title}"</span>. The AI will apply your instruction on top of the existing lesson.</p>
        <form onSubmit={handleSubmit} className="space-y-4">
          <textarea
            value={prompt}
            onChange={e => setPrompt(e.target.value)}
            maxLength={500}
            required
            disabled={loading}
            rows={3}
            autoFocus
            placeholder="e.g. Replace the discussion topic with climate change, add 5 more vocabulary words"
            className="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 resize-none disabled:opacity-60"
          />
          {error && <p className="text-sm text-red-600">{error}</p>}
          <button
            type="submit"
            disabled={loading || !prompt.trim()}
            className="w-full flex items-center justify-center gap-2 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {loading ? (
              <><div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white" /><span>Applying…</span></>
            ) : 'Apply changes'}
          </button>
        </form>
      </div>
    </div>
  );
}

function DeleteConfirm({ lesson, onClose, onDeleted }) {
  const [loading, setLoading] = useState(false);

  const handleDelete = async () => {
    setLoading(true);
    try {
      await api.deleteLesson(lesson.hash_id);
      onDeleted(lesson.hash_id);
    } catch {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" onClick={onClose}>
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6" onClick={e => e.stopPropagation()}>
        <h3 className="font-bold text-slate-900 mb-2">Delete lesson?</h3>
        <p className="text-sm text-slate-500 mb-6">
          "<span className="font-medium text-slate-700">{lesson.title}</span>" will be permanently deleted.
        </p>
        <div className="flex gap-3">
          <button onClick={onClose} disabled={loading} className="flex-1 py-2.5 border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl hover:bg-slate-50 transition-colors disabled:opacity-50">
            Cancel
          </button>
          <button onClick={handleDelete} disabled={loading} className="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
            {loading ? <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white" /> : 'Delete'}
          </button>
        </div>
      </div>
    </div>
  );
}

export default function MyLessons({ auth }) {
  const [lessons, setLessons] = useState(null);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(null);
  const [deleting, setDeleting] = useState(null);
  const [copied, setCopied] = useState(null);

  useEffect(() => {
    if (auth === false) { setLoading(false); return; }
    api.fetchMyLessons()
      .then(data => setLessons(data.data ?? data))
      .catch(() => setLessons([]))
      .finally(() => setLoading(false));
  }, [auth]);

  if (auth === false) return <Navigate to="/403" replace />;

  const copyLink = (hashId) => {
    navigator.clipboard.writeText(`${window.location.origin}/lesson/${hashId}`);
    setCopied(hashId);
    setTimeout(() => setCopied(null), 2000);
  };

  const handleSaved = (updated) => {
    setLessons(ls => ls.map(l => l.hash_id === updated.hash_id ? updated : l));
    setEditing(null);
  };

  const handleDeleted = (hashId) => {
    setLessons(ls => ls.filter(l => l.hash_id !== hashId));
    setDeleting(null);
  };

  if (loading) return (
    <section className="flex-grow bg-gradient-to-br from-slate-50 to-slate-200/70 flex items-center justify-center">
      <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-sky-600" />
    </section>
  );

  return (
    <section className="flex-grow bg-gradient-to-br from-slate-50 to-slate-200/70 py-10 px-4">
      <div className="max-w-3xl mx-auto">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl font-extrabold text-slate-900">My Lessons</h1>
          <Link
            to="/lessons/create"
            className="flex items-center gap-1.5 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v16m8-8H4" />
            </svg>
            New lesson
          </Link>
        </div>

        {!lessons || lessons.length === 0 ? (
          <div className="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <p className="text-slate-500 mb-4">You haven't created any lessons yet.</p>
            <Link to="/lessons/create" className="text-sky-600 hover:text-sky-700 font-semibold text-sm transition-colors">
              Create your first lesson →
            </Link>
          </div>
        ) : (
          <div className="space-y-3">
            {lessons.map(lesson => (
              <div key={lesson.hash_id} className="bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-4 flex items-start gap-4">
                <div className="flex-1 min-w-0">
                  <Link
                    to={`/lesson/${lesson.hash_id}`}
                    className="font-semibold text-slate-900 hover:text-sky-600 transition-colors line-clamp-2 leading-snug"
                  >
                    {lesson.title}
                  </Link>
                  <div className="flex items-center gap-3 mt-1.5 flex-wrap">
                    <Stars value={lesson.avg_rating} />
                    {lesson.avg_rating > 0 && (
                      <span className="text-xs text-slate-400">{lesson.avg_rating}</span>
                    )}
                    <span className="text-xs text-slate-400">{lesson.views_count} views</span>
                    <span className="text-xs text-slate-400">
                      {new Date(lesson.created_at).toLocaleDateString()}
                    </span>
                  </div>
                </div>
                <div className="flex items-center gap-1 shrink-0">
                  <button
                    onClick={() => copyLink(lesson.hash_id)}
                    title="Copy link"
                    className="p-2 text-slate-400 hover:text-sky-600 rounded-lg hover:bg-slate-50 transition-colors"
                  >
                    {copied === lesson.hash_id ? (
                      <svg className="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                      </svg>
                    ) : (
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                      </svg>
                    )}
                  </button>
                  <button
                    onClick={() => setEditing(lesson)}
                    title="Edit"
                    className="p-2 text-slate-400 hover:text-sky-600 rounded-lg hover:bg-slate-50 transition-colors"
                  >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    onClick={() => setDeleting(lesson)}
                    title="Delete"
                    className="p-2 text-slate-400 hover:text-red-500 rounded-lg hover:bg-slate-50 transition-colors"
                  >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {editing && <EditModal lesson={editing} onClose={() => setEditing(null)} onSaved={handleSaved} />}
      {deleting && <DeleteConfirm lesson={deleting} onClose={() => setDeleting(null)} onDeleted={handleDeleted} />}
    </section>
  );
}
