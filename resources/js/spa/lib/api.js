function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
}

function headers(extra = {}) {
  return {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrfToken(),
    ...extra,
  };
}

async function request(method, url, body) {
  const res = await fetch(url, {
    method,
    credentials: 'same-origin',
    headers: headers(),
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  if (!res.ok) {
    const err = await res.json().catch(() => ({ message: 'Request failed' }));
    const error = new Error(err.message || 'Request failed');
    error.status = res.status;
    error.data = err;
    throw error;
  }

  return res.json();
}

export const api = {
  fetchLesson:    (hashId)              => request('GET',    `/api/lessons/${hashId}`),
  createLesson:   (data)                => request('POST',   '/api/lessons', data),
  editLesson:     (hashId, editPrompt)  => request('PATCH',  `/api/lessons/${hashId}`, { edit_prompt: editPrompt }),
  deleteLesson:   (hashId)              => request('DELETE', `/api/lessons/${hashId}`),
  rateLesson:     (hashId, rating)      => request('POST',   `/api/lessons/${hashId}/rate`, { rating }),
  fetchMyLessons: ()                    => request('GET',    '/api/lessons'),
  fetchTopLessons:(page = 1)            => request('GET',    `/api/lessons/top?page=${page}`),
};
