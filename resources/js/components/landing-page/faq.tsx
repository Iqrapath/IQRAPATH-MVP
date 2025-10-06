import React, { useState } from 'react';

interface FAQItem {
  id: number;
  title: string;
  content: string;
  status: string;
  order_index: number;
}

interface FAQItemProps {
  id: number;
  question: string;
  answer: string;
  isOpen: boolean;
  toggleOpen: (id: number) => void;
}

interface FAQProps {
  faqs: FAQItem[];
}

const FAQItem: React.FC<FAQItemProps> = ({ id, question, answer, isOpen, toggleOpen }) => {
  return (
    <div className={`bg-[#FFF8E7] rounded-lg overflow-hidden mb-4 ${isOpen ? '' : ''}`}>
      <button
        className="w-full text-left px-6 py-5 flex items-center justify-between focus:outline-none"
        onClick={() => toggleOpen(id)}
      >
        <div className="flex items-center">
          <span className="text-[#2F8D8C] font-medium mr-4">{String(id).padStart(2, '0')}</span>
          <h3 className="text-[#2F8D8C] font-medium">{question}</h3>
        </div>
        <div className={`w-6 h-6 rounded-full flex items-center justify-center ${isOpen ? 'bg-[#2F8D8C] text-white' : 'border border-[#2F8D8C] text-[#2F8D8C]'}`}>
          {isOpen ? (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 12H6" />
            </svg>
          ) : (
            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
          )}
        </div>
      </button>
      {isOpen && (
        <div className="px-6 pb-5 pt-0 text-gray-600">
          <p>{answer}</p>
        </div>
      )}
    </div>
  );
};

export default function FAQ({ faqs }: FAQProps) {
  const [openItem, setOpenItem] = useState<number | null>(faqs.length > 0 ? faqs[0].id : null);

  const toggleOpen = (id: number) => {
    setOpenItem(openItem === id ? null : id);
  };

  return (
    <section className="py-16 md:py-24 bg-[#F8F9FA] overflow-hidden">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row items-start justify-between mb-12">
          <div className="mb-6 md:mb-0">
            <h2 className="text-4xl font-bold text-[#2F8D8C]">
              Have<br />
              Questions?
            </h2>
          </div>
          <div className="md:ml-8">
            <p className="text-xl text-[#2F8D8C] font-medium">
              We've Got Answers!
            </p>
          </div>
        </div>

        <div className="space-y-4">
          {faqs.map((item) => (
            <FAQItem
              key={item.id}
              id={item.id}
              question={item.title}
              answer={item.content}
              isOpen={openItem === item.id}
              toggleOpen={toggleOpen}
            />
          ))}
        </div>
      </div>
    </section>
  );
}