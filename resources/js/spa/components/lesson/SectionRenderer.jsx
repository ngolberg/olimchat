import React from 'react';
import SimpleText from './SimpleText.jsx';
import ListenableText from './ListenableText.jsx';
import WordList from './WordList.jsx';
import SentencesLL from './SentencesLL.jsx';
import SentencesUL from './SentencesUL.jsx';

const RENDERERS = {
  simple_text:     SimpleText,
  listenable_text: ListenableText,
  word_list:       WordList,
  sentences_ll:    SentencesLL,
  sentences_ul:    SentencesUL,
};

export default function SectionRenderer({ section }) {
  return (
    <div className="space-y-6">
      {section.components.map((component, i) => {
        const Component = RENDERERS[component.type];
        if (!Component) return null;
        return <Component key={i} data={component.data} />;
      })}
    </div>
  );
}
