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

export default function TopLessons({ auth }) {
  const [lessons, setLessons] = useState(null);
  const [meta, setMeta] = useState(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (auth === false) { setLoading(false); return; }
    setLoading(true);
    api.fetchTopLessons(page)
      .then(data => {
        setLessons(data.data ?? []);
        setMeta(data.meta ?? null);
      })
      .catch(() => setLessons([]))
      .finally(() => setLoading(false));
  }, [auth, page]);

  if (auth === false) return <Navigate to="/403" replace />;

  if (loading) return (
    <section className="flex-grow bg-gradient-to-br from-slate-50 to-slate-200/70 flex items-center justify-center">
      <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-sky-600" />
    </section>
  );

  return (
    <section className="flex-grow bg-gradient-to-br from-slate-50 to-slate-200/70 py-10 px-4">
      <div className="max-w-3xl mx-auto">
        <div className="mb-6">
          <h1 className="text-2xl font-extrabold text-slate-900">Top Lessons</h1>
          <p className="text-slate-500 text-sm mt-1">Highest-rated lessons from the community.</p>
        </div>

        {!lessons || lessons.length === 0 ? (
          <div className="bg-white rounded-2xl border border-slate-200 p-12 text-center shadow-sm">
            <p className="text-slate-500 mb-4">No rated lessons yet.</p>
            <Link to="/lessons/create" className="text-sky-600 hover:text-sky-700 font-semibold text-sm transition-colors">
              Be the first to create one →
            </Link>
          </div>
        ) : (
          <>
            <div className="space-y-3">
              {lessons.map((lesson, idx) => (
                <Link
                  key={lesson.hash_id}
                  to={`/lesson/${lesson.hash_id}`}
                  className="bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-4 flex items-center gap-4 hover:border-sky-300 hover:shadow-md transition-all group block"
                >
                  <span className="text-2xl font-extrabold text-slate-200 group-hover:text-sky-200 transition-colors w-8 shrink-0 tabular-nums">
                    {(page - 1) * 20 + idx + 1}
                  </span>
                  <div className="flex-1 min-w-0">
                    <p className="font-semibold text-slate-900 group-hover:text-sky-600 transition-colors line-clamp-2 leading-snug">
                      {lesson.title}
                    </p>
                    <div className="flex items-center gap-3 mt-1.5 flex-wrap">
                      <Stars value={lesson.avg_rating} />
                      <span className="text-xs text-slate-400">{lesson.avg_rating}</span>
                      <span className="text-xs text-slate-400">{lesson.views_count} views</span>
                      <span className="text-xs text-slate-400">by {lesson.author?.name}</span>
                    </div>
                  </div>
                  <svg className="w-4 h-4 text-slate-300 group-hover:text-sky-400 shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
              ))}
            </div>

            {meta && meta.last_page > 1 && (
              <div className="flex items-center justify-center gap-3 mt-8">
                <button
                  onClick={() => setPage(p => Math.max(1, p - 1))}
                  disabled={page === 1}
                  className="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                  ← Previous
                </button>
                <span className="text-sm text-slate-500">{page} / {meta.last_page}</span>
                <button
                  onClick={() => setPage(p => Math.min(meta.last_page, p + 1))}
                  disabled={page === meta.last_page}
                  className="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                  Next →
                </button>
              </div>
            )}
          </>
        )}
      </div>
    </section>
  );
}
