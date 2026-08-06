import { useState } from 'react';
import { chatsApi } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import { useModal } from '../../context/ModalContext';

type Props = {
  receiverId: number;
  receiverName: string;
  title: string;
  onSent: (threadId: number) => void;
};

export function ContactUserModalContent({ receiverId, receiverName, title, onSent }: Props) {
  const { lbl } = useSite();
  const { closeModal } = useModal();
  const [message, setMessage] = useState('');
  const [sending, setSending] = useState(false);
  const [error, setError] = useState('');

  const submit = async () => {
    const text = message.trim();
    if (!text) {
      setError(lbl('LBL_PLEASE_ENTER_MESSAGE', 'Please enter a message.'));
      return;
    }
    setSending(true);
    setError('');
    try {
      const res = await chatsApi.privateThread(receiverId, text);
      const threadId = res.data.data.thread_id;
      if (threadId) {
        closeModal();
        onSent(threadId);
      }
    } catch {
      setError(lbl('LBL_Something_went_wrong', 'Something went wrong.'));
    } finally {
      setSending(false);
    }
  };

  return (
    <>
      <div className="modal-header">
        <h5>{title}</h5>
        <button type="button" className="btn-close w3mentorsmodalJs" aria-label="Close" onClick={closeModal} />
      </div>
      <div className="modal-body">
        <div className="form-group">
          <label className="form-label">
            {lbl('LBL_MESSAGE', 'Message')} {receiverName}
          </label>
          <textarea
            className="form-control"
            rows={5}
            value={message}
            onChange={(e) => setMessage(e.target.value)}
          />
          {error && <p className="text-danger small mt-2">{error}</p>}
        </div>
      </div>
      <div className="modal-footer">
        <button type="button" className="btn btn--bordered" onClick={closeModal}>
          {lbl('LBL_Cancel', 'Cancel')}
        </button>
        <button
          type="button"
          className="btn btn--primary color-white"
          disabled={sending}
          onClick={submit}
        >
          {sending ? lbl('LBL_Loading', 'Loading...') : lbl('LBL_SEND', 'Send')}
        </button>
      </div>
    </>
  );
}
