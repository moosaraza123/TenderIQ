import { useEffect } from 'react';
import { createPortal } from 'react-dom';
import { X } from 'lucide-react';

export default function Modal({ isOpen, onClose, title, children, maxWidth = 'max-w-lg' }) {
    useEffect(() => {
        if (!isOpen) return;
        const handler = (e) => e.key === 'Escape' && onClose();
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, [isOpen, onClose]);

    if (!isOpen) return null;

    return createPortal(
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div
                className="absolute inset-0 bg-black/40 backdrop-blur-sm"
                onClick={onClose}
            />
            <div className={`relative bg-white rounded-card shadow-xl w-full ${maxWidth} animate-slide-up`}>
                <div className="flex items-center justify-between px-6 py-4 border-b border-surface-200">
                    <h2 className="text-base font-semibold text-surface-900">{title}</h2>
                    <button onClick={onClose} className="text-surface-400 hover:text-surface-700 p-1 rounded">
                        <X size={18} />
                    </button>
                </div>
                <div className="p-6">{children}</div>
            </div>
        </div>,
        document.body,
    );
}
