import React, { useEffect, useState, useRef } from 'react';
import { Routes, Route, Link, useLocation } from 'react-router-dom';
import Home from './pages/Home.jsx';
import Meetings from './pages/Meetings.jsx';
import MyMeetings from './pages/MyMeetings.jsx';
import NotFound from './pages/NotFound.jsx';

function Navbar({ messages, user, userImage, onLanguageChange, onOpenUploadModal }) {
  const location = useLocation();
  const currentLang = user?.lang || 'ru';
  const [profileOpen, setProfileOpen] = useState(false);
  const profileRef = useRef(null);

  useEffect(() => {
    if (!profileOpen) return;
    const handleClickOutside = (e) => {
      if (profileRef.current && !profileRef.current.contains(e.target)) {
        setProfileOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [profileOpen]);

  return (
    <nav className="sticky top-0 z-50 bg-white/95 backdrop-blur shadow">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">
        <Link to="/" className="flex items-center gap-2 text-xl font-bold text-sky-600">
          <img src="/img/logo.jpeg" alt="OlimChat.me Logo" className="w-10 h-10 rounded-full" />
          <span className="hidden sm:inline">OlimChat.me</span>
        </Link>
        <div className="flex items-center gap-3 sm:gap-6">
          <ul className="hidden sm:flex items-center gap-6 text-gray-700 font-medium">
            {location.pathname === '/' && (
              <li><a href="#how-it-works" className="hover:text-sky-600 transition-colors">{messages?.how_it_works || 'How it works'}</a></li>
            )}
            <li><Link to="/meetings" className="hover:text-sky-600 transition-colors">{messages?.calendar_title}</Link></li>
            <li><Link to="/my-meetings" className="hover:text-sky-600 transition-colors">{messages?.my_meetings_title}</Link></li>
          </ul>
          {user && (
            <div className="flex gap-2 text-sm font-medium">
              <button
                onClick={() => onLanguageChange('en')}
                className={`${currentLang === 'en' ? 'text-sky-600 font-bold' : 'text-gray-500 hover:text-sky-600'}`}
              >
                EN
              </button>
              <span className="text-gray-300">|</span>
              <button
                onClick={() => onLanguageChange('ru')}
                className={`${currentLang === 'ru' ? 'text-sky-600 font-bold' : 'text-gray-500 hover:text-sky-600'}`}
              >
                RU
              </button>
            </div>
          )}
          {user && (
            <div className="relative" ref={profileRef}>
              <button
                onClick={() => setProfileOpen(prev => !prev)}
                className="flex items-center gap-2 hover:opacity-80 transition-opacity"
              >
                <img
                  src={userImage || '/img/logo.jpeg'}
                  alt={user.name}
                  className="w-9 h-9 rounded-full object-cover border-2 border-slate-200"
                />
                <span className="hidden md:inline text-sm font-medium text-slate-700 max-w-[120px] truncate">
                  {user.name}
                </span>
                <svg
                  className={`w-4 h-4 text-slate-400 transition-transform duration-200 ${profileOpen ? 'rotate-180' : ''}`}
                  fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              {profileOpen && (
                <div className="absolute right-0 top-full mt-2 w-56 bg-white border border-slate-200 rounded-xl shadow-lg z-50 py-2">
                  <div className="px-4 py-2 border-b border-slate-100">
                    <p className="text-sm font-semibold text-slate-800 truncate">{user.name}</p>
                  </div>
                  <div className="sm:hidden py-1 border-b border-slate-100">
                    <Link
                      to="/meetings"
                      onClick={() => setProfileOpen(false)}
                      className="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                    >
                      {messages?.calendar_title}
                    </Link>
                    <Link
                      to="/my-meetings"
                      onClick={() => setProfileOpen(false)}
                      className="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors"
                    >
                      {messages?.my_meetings_title}
                    </Link>
                  </div>
                  <button
                    onClick={() => { setProfileOpen(false); onOpenUploadModal(); }}
                    className="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-2"
                  >
                    <svg className="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {messages?.update_photo || 'Update photo'}
                  </button>
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </nav>
  );
}

export default function App() {
  const [messages, setMessages] = useState({});
  const [auth, setAuth] = useState(false);
  const [user, setUser] = useState(null);
  const [userImage, setUserImage] = useState(null);
  const [showUploadModal, setShowUploadModal] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [uploadedPreview, setUploadedPreview] = useState(null);
  const fileInputRef = useRef(null);

  const fetchConfig = () => {
    fetch('/api/config', { credentials: 'same-origin' })
      .then(r => r.json())
      .then((data) => {
        setMessages(data.messages || {});
        setAuth(Boolean(data.auth));
        setUser(data.user || null);
        setUserImage(data.user_image || null);
      })
      .catch(() => {});
  };

  useEffect(() => {
    fetchConfig();
  }, []);

  const handleLanguageChange = (lang) => {
    if (user?.lang === lang) return;

    fetch('/api/user/language', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify({ lang }),
      credentials: 'same-origin'
    })
      .then(r => {
        if (!r.ok) throw new Error('Failed to update language');
        return r.json();
      })
      .then(() => {
        fetchConfig();
      })
      .catch(err => console.error(err));
  };

  const handleOpenUploadModal = () => {
    setUploadedPreview(null);
    setShowUploadModal(true);
  };

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

  return (
    <div className="min-h-screen flex flex-col bg-gradient-to-br from-indigo-500 to-purple-600 text-gray-800">
      <Navbar messages={messages} user={user} userImage={userImage} onLanguageChange={handleLanguageChange} onOpenUploadModal={handleOpenUploadModal} />
      <main className="flex-grow flex flex-col">
        <Routes>
          <Route path="/" element={<Home messages={messages} />} />
          <Route path="/meetings" element={<Meetings messages={messages} auth={auth} user={user} userImage={userImage} onOpenUploadModal={handleOpenUploadModal} />} />
          <Route path="/my-meetings" element={<MyMeetings messages={messages} auth={auth} user={user} />} />
          <Route path="*" element={<NotFound messages={messages} />} />
        </Routes>
      </main>
      <footer className="bg-neutral-900 text-white">
        <div className="max-w-6xl mx-auto px-6 pb-6">
          <div className="border-t border-neutral-700 pt-6 text-neutral-400 text-center">
            <p>&copy; 2026 OlimChat.me.</p>
          </div>
        </div>
      </footer>

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
                    {messages.upload_photo_done}
                  </button>
                  <button
                    onClick={() => { setUploadedPreview(null); fileInputRef.current?.click(); }}
                    className="flex-1 px-4 py-3 bg-white text-sky-600 border border-sky-200 rounded-xl font-bold hover:bg-sky-50 transition-colors shadow-sm"
                  >
                    {messages.upload_photo_another}
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
    </div>
  );
}
