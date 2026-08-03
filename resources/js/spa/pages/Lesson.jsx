import React, { useEffect, useRef, useState } from 'react';
import { useParams, Navigate, Link } from 'react-router-dom';
import { api } from '../lib/api.js';
import SectionRenderer from '../components/lesson/SectionRenderer.jsx';

function StarRating({ hashId, currentRating, onRated }) {
  const [hovered, setHovered] = useState(0);
  const [saving, setSaving] = useState(false);
  const active = hovered || currentRating || 0;

  const rate = async (stars) => {
    if (saving) return;
    setSaving(true);
    try {
      const result = await api.rateLesson(hashId, stars);
      onRated(stars, result.avg_rating, result.ratings_count);
    } catch {
      // keep existing rating on error
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="border-t border-slate-200 pt-6 mt-6">
      <p className="text-sm font-semibold text-slate-700 mb-3 text-center">
        {currentRating ? 'Update your rating' : 'Rate this lesson'}
      </p>
      <div className="flex items-center justify-center gap-1">
        {[1, 2, 3, 4, 5].map(star => (
          <button
            key={star}
            disabled={saving}
            onClick={() => rate(star)}
            onMouseEnter={() => setHovered(star)}
            onMouseLeave={() => setHovered(0)}
            className="transition-transform hover:scale-110 disabled:opacity-50"
            aria-label={`Rate ${star} stars`}
          >
            <svg className={`w-8 h-8 transition-colors ${star <= active ? 'text-amber-400' : 'text-slate-200'}`} fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
            </svg>
          </button>
        ))}
      </div>
    </div>
  );
}

export default function Lesson({ auth }) {
  const { hashId } = useParams();
  const [lesson, setLesson] = useState(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);
  const [activeSection, setActiveSection] = useState(0);
  const [userRating, setUserRating] = useState(null);
  const [avgRating, setAvgRating] = useState(0);
  const [ratingsCount, setRatingsCount] = useState(0);
  const tabsRef = useRef(null);

  useEffect(() => {
    setLoading(true);
    setNotFound(false);
    api.fetchLesson(hashId)
      .then(data => {
        setLesson(data);
        setUserRating(data.user_rating ?? null);
        setAvgRating(data.avg_rating ?? 0);
        setRatingsCount(data.ratings_count ?? 0);
      })
      .catch(err => {
        if (err.status === 404) setNotFound(true);
      })
      .finally(() => setLoading(false));
  }, [hashId]);

  const handleRated = (stars, newAvg, newCount) => {
    setUserRating(stars);
    if (newAvg !== undefined) setAvgRating(newAvg);
    if (newCount !== undefined) setRatingsCount(newCount);
  };

  // Scroll active tab into view
  useEffect(() => {
    if (!tabsRef.current) return;
    const active = tabsRef.current.querySelector('[data-active="true"]');
    active?.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
  }, [activeSection]);

  if (auth === null || (auth === false && loading)) return (
    <div className="flex-grow flex items-center justify-center">
      <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-white opacity-70" />
    </div>
  );
  if (auth === false) return <Navigate to="/" replace />;
  if (loading) return (
    <div className="flex-grow flex items-center justify-center">
      <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-white opacity-70" />
    </div>
  );
  if (notFound) return (
    <div className="flex-grow flex flex-col items-center justify-center text-white gap-4 px-4">
      <p className="text-xl font-semibold">Lesson not found</p>
      <Link to="/lessons" className="text-sm underline opacity-70 hover:opacity-100">Back to lessons</Link>
    </div>
  );

  const sections = lesson.content?.sections ?? [];
  const isLastSection = activeSection === sections.length - 1;

  return (
    <div className="flex-grow bg-white">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-4 py-4">
        <div className="max-w-3xl mx-auto">
          <div className="flex items-start justify-between gap-4">
            <div className="min-w-0">
              <h1 className="text-lg font-bold text-slate-900 leading-snug">{lesson.title}</h1>
              <div className="flex items-center gap-3 mt-1 text-xs text-slate-400">
                <span>{lesson.views_count} views</span>
                {avgRating > 0 && (
                  <span className="flex items-center gap-1">
                    <svg className="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    {avgRating}
                    <span className="text-slate-300">({ratingsCount})</span>
                  </span>
                )}
              </div>
            </div>
            <Link to="/lessons" className="shrink-0 text-sm text-sky-600 hover:text-sky-700 transition-colors">
              ← Lessons
            </Link>
          </div>
        </div>
      </div>

      {/* Section tabs */}
      <div className="border-b border-slate-200 bg-white sticky top-[61px] z-10">
        <div
          ref={tabsRef}
          className="max-w-3xl mx-auto flex gap-1 overflow-x-auto px-4 py-2 scrollbar-hide"
          style={{ scrollbarWidth: 'none' }}
        >
          {sections.map((section, i) => (
            <button
              key={i}
              data-active={activeSection === i ? 'true' : 'false'}
              onClick={() => setActiveSection(i)}
              className={`shrink-0 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors whitespace-nowrap ${
                activeSection === i
                  ? 'bg-sky-600 text-white'
                  : 'text-slate-600 hover:bg-slate-100'
              }`}
            >
              {section.title}
            </button>
          ))}
        </div>
      </div>

      {/* Section content */}
      <div className="max-w-3xl mx-auto px-4 py-6">
        {sections[activeSection] && (
          <SectionRenderer section={sections[activeSection]} />
        )}

        {/* Navigation */}
        <div className="flex items-center justify-between mt-8 pt-6 border-t border-slate-100">
          <button
            onClick={() => setActiveSection(i => Math.max(0, i - 1))}
            disabled={activeSection === 0}
            className="flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
            </svg>
            Previous
          </button>
          <span className="text-xs text-slate-400">{activeSection + 1} / {sections.length}</span>
          <button
            onClick={() => setActiveSection(i => Math.min(sections.length - 1, i + 1))}
            disabled={isLastSection}
            className="flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
          >
            Next
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        {isLastSection && (
          <>
            <StarRating
              hashId={hashId}
              currentRating={userRating}
              onRated={handleRated}
            />
            <div className="mt-4 flex items-center justify-center gap-2 text-xs text-slate-400">
              <span>{lesson.views_count} views</span>
              {avgRating > 0 && (
                <>
                  <span>·</span>
                  <span className="flex items-center gap-1">
                    <svg className="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    {avgRating} ({ratingsCount} {ratingsCount === 1 ? 'rating' : 'ratings'})
                  </span>
                </>
              )}
            </div>
          </>
        )}
      </div>
    </div>
  );
}
