import React, { useEffect, useState } from 'react';
import { Routes, Route, Link, useLocation } from 'react-router-dom';
import Home from './pages/Home.jsx';
import Calendar from './pages/Calendar.jsx';

function Navbar({ messages }) {
  const location = useLocation();
  return (
    <nav className="sticky top-0 z-50 bg-white/95 backdrop-blur shadow">
      <div className="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <Link to="/" className="flex items-center gap-2 text-xl font-bold text-sky-600">
          <img src="/img/logo.jpeg" alt="OlimChat.me Logo" className="w-10 h-10 rounded-full" />
          <span>OlimChat.me</span>
        </Link>
        <ul className="hidden sm:flex items-center gap-6 text-gray-700 font-medium">
          {location.pathname === '/' && (
            <li><a href="#how-it-works" className="hover:text-sky-600 transition-colors">{messages?.how_it_works || 'How it works'}</a></li>
          )}
        </ul>
      </div>
    </nav>
  );
}

export default function App() {
  const [messages, setMessages] = useState({});
  const [auth, setAuth] = useState(false);
  const [user, setUser] = useState(null);

  useEffect(() => {
    fetch('/api/config', { credentials: 'same-origin' })
      .then(r => r.json())
      .then((data) => {
        setMessages(data.messages || {});
        setAuth(Boolean(data.auth));
        setUser(data.user || null);
      })
      .catch(() => {});
  }, []);

  return (
    <div className="min-h-screen flex flex-col bg-gradient-to-br from-indigo-500 to-purple-600 text-gray-800">
      <Navbar messages={messages} />
      <main className="flex-grow flex flex-col">
        <Routes>
          <Route path="/" element={<Home messages={messages} />} />
          <Route path="/calendar" element={<Calendar messages={messages} auth={auth} user={user} />} />
        </Routes>
      </main>
      <footer className="bg-neutral-900 text-white">
        <div className="max-w-6xl mx-auto px-6 pb-6">
          <div className="border-t border-neutral-700 pt-6 text-neutral-400 text-center">
            <p>&copy; 2026 OlimChat.me.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
