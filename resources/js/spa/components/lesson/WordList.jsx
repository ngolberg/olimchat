import React from 'react';
import WordCard from './WordCard.jsx';

export default function WordList({ data }) {
  return (
    <div className="space-y-2">
      {data.items.map((item, i) => (
        <WordCard key={i} item={item} />
      ))}
    </div>
  );
}
