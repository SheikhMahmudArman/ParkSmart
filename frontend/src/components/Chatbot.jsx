import { useEffect, useRef, useState } from 'react';
import './Chatbot.css';
import { api } from '../api';

const quickQuestions = [
  'What parking spaces are available?',
  'How do I reserve a space?',
  'How is the price calculated?',
  'Can I cancel a booking?',
  'What should I do when I arrive?',
];

const welcomeMessage = {
  id: 1,
  sender: 'bot',
  text: 'Hi! I’m the ParkSmart assistant. Ask me about reservations, prices, arrival, payments, cancellations, or overstay fines.',
};

function answerFor(question, lots = []) {
  const text = question.toLowerCase().trim();

  if (!text) {
    return 'Please enter a question and I’ll point you to the right ParkSmart guidance.';
  }

  if (/available|availability|free|open|vacant/.test(text) && /space|spot|parking|lot/.test(text)) {
    if (lots.length) {
      const availability = lots.map((lot) => {
        const features = Array.isArray(lot.features) && lot.features.length
          ? `; features: ${lot.features.join(', ')}`
          : '';
        return `• ${lot.name} (${lot.location}): ${lot.available_spaces} free of ${lot.total_spaces} spaces, ${lot.type}, ৳${lot.hourly_rate}/hour${features}`;
      });
      return `Current parking availability from the database:\n${availability.join('\n')}\n\nAvailability can change quickly; check Search Parking before booking.`;
    }

    return 'To see available parking, open Search Parking. Each lot shows its current “Free now” spaces, total spaces, location, parking type, features, and hourly rate. Availability can change quickly, so choose View and reserve to check the lot before submitting your booking.';
  }

  if (/reserve|booking|book|space/.test(text)) {
    return 'To reserve a space, sign in as a driver, add your vehicle in Profile, choose a parking lot, then select a future date, start time, end time, and vehicle. The start and end must be on the same date, with the end after the start.';
  }

  if (/price|cost|rate|bill|charge|hour/.test(text)) {
    return 'Prices are shown in BDT. ParkSmart bills each started hour using the lot’s hourly rate. For example, 1 hour 10 minutes is billed as 2 hours. Leaving late can increase the final amount and may create an overstay fine.';
  }

  if (/cancel|cancellation|refund/.test(text)) {
    return 'Unpaid Pending or Confirmed bookings can be cancelled from My Reservations. Paid bookings cannot be cancelled through the normal driver flow; ask the parking operator about a refund or adjustment.';
  }

  if (/arrive|entry|entrance|check in|park/.test(text)) {
    return 'Bring the vehicle whose plate number is attached to your reservation. Staff verify the reservation, vehicle, lot, and booked time before recording entry. Do not use a different space unless staff direct you.';
  }

  if (/exit|leave|checkout|check out/.test(text)) {
    return 'Ask parking staff to record your exit before leaving. The session duration and final amount are recorded then, the space is released, and any overstay charge appears in your account.';
  }

  if (/pay|payment|receipt|card|money/.test(text)) {
    return 'ParkSmart shows payment history with the amount, date, method, status, and reference. The current application uses demo payments for coursework and testing, so no real card or bank payment is processed.';
  }

  if (/fine|overstay|late|violation/.test(text)) {
    return 'An overstay or another recorded parking violation can create a fine. Open Payment to review the reason and status. If you think it is incorrect, contact on-site staff and keep your reservation details.';
  }

  if (/vehicle|car|plate|profile/.test(text)) {
    return 'Open Profile to add or manage vehicles. A plate number is required before booking, and the selected vehicle must belong to your driver account.';
  }

  if (/time|timezone|dhaka|date/.test(text)) {
    return 'ParkSmart displays reservation times in Asia/Dhaka. Select a future date and make sure the start and end times are on the same date.';
  }

  if (/help|support|problem|wrong|emergency/.test(text)) {
    return 'For an immediate lot issue, speak to on-site parking staff. Provide the lot name, reservation number, vehicle plate, date and time, and a short description. For emergencies, follow posted site instructions and contact local emergency services.';
  }

  if (/hello|hi|hey|thank/.test(text)) {
    return 'You’re welcome! I can help with reservations, pricing, arrival, payments, cancellations, vehicles, and overstay fines.';
  }

  return 'I can help with reservations, pricing, arrival and exit, payments, cancellations, vehicles, or overstay fines. Try asking “How do I reserve a space?”';
}

function fallbackNotice(reason) {
  if (reason === 'no_api_credits') {
    return 'OpenRouter provider credits are exhausted. Check the provider account or choose another model.';
  }
  if (reason === 'knowledge_not_indexed') {
    return 'The ParkSmart knowledge base has not been indexed yet. Please contact the administrator.';
  }
  if (reason === 'embedding_unavailable') {
    return 'The local knowledge-search model is unavailable right now. Try again shortly.';
  }
  if (reason === 'rate_limited') {
    return 'The AI service is rate-limited right now. Try again shortly.';
  }
  if (reason === 'missing_api_key') {
    return 'Configure OPENROUTER_API_KEY to enable AI-generated replies.';
  }
  if (reason === 'service_unavailable') {
    return 'The AI service could not be reached; showing local/database guidance.';
  }
  return 'The AI service could not answer; showing local/database guidance.';
}

export default function Chatbot() {
  const [open, setOpen] = useState(false);
  const [messages, setMessages] = useState([welcomeMessage]);
  const [draft, setDraft] = useState('');
  const [loading, setLoading] = useState(false);
  const endRef = useRef(null);

  useEffect(() => {
    if (open) endRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, open]);

  async function sendMessage(message = draft) {
    const question = message.trim();
    if (!question || loading) return;

    setMessages((current) => [...current, { id: Date.now(), sender: 'user', text: question }]);
    setDraft('');
    setLoading(true);

    try {
      const response = await api('/chat', { method: 'POST', body: { message: question } });
      const text = response.fallback
        ? `${answerFor(question, response.lots)}\n\n(${fallbackNotice(response.fallback_reason)})`
        : response.answer;
      setMessages((current) => [...current, { id: Date.now(), sender: 'bot', text }]);
    } catch {
      setMessages((current) => [
        ...current,
        { id: Date.now(), sender: 'bot', text: `${answerFor(question)}\n\n(Showing local guidance because the chat service could not be reached.)` },
      ]);
    } finally {
      setLoading(false);
    }
  }

  function handleSubmit(event) {
    event.preventDefault();
    sendMessage();
  }

  return (
    <div className="parksmart-chatbot">
      {open && (
        <section className="chatbot-panel" aria-label="ParkSmart assistant">
          <header className="chatbot-header">
            <div>
              <strong>ParkSmart Assistant</strong>
              <span>Parking guide · instant answers</span>
            </div>
            <button type="button" className="chatbot-close" onClick={() => setOpen(false)} aria-label="Close assistant">
              ×
            </button>
          </header>

          <div className="chatbot-messages" aria-live="polite">
            {messages.map((message) => (
              <div key={message.id} className={`chatbot-message ${message.sender}`}>
                {message.text}
              </div>
            ))}
            <div ref={endRef} />
          </div>

          {messages.length === 1 && (
            <div className="chatbot-quick-actions">
              {quickQuestions.map((question) => (
                <button type="button" key={question} onClick={() => sendMessage(question)}>
                  {question}
                </button>
              ))}
            </div>
          )}

          <form className="chatbot-form" onSubmit={handleSubmit}>
            <input
              value={draft}
              onChange={(event) => setDraft(event.target.value)}
              placeholder="Ask about parking..."
              aria-label="Ask ParkSmart Assistant"
            />
            <button type="submit" aria-label="Send question" disabled={loading}>{loading ? '...' : 'Send'}</button>
          </form>
          <p className="chatbot-disclaimer">This assistant provides general guidance and cannot view or change your booking.</p>
        </section>
      )}

      <button type="button" className="chatbot-launcher" onClick={() => setOpen((current) => !current)} aria-expanded={open} aria-label={open ? 'Close ParkSmart Assistant' : 'Open ParkSmart Assistant'}>
        <span className="chatbot-launcher-icon" aria-hidden="true">?</span>
        <span>{open ? 'Close' : 'Help'}</span>
      </button>
    </div>
  );
}
