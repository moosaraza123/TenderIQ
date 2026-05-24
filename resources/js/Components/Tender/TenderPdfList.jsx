import { Download, FileText } from 'lucide-react';
import LockedOverlay from '@/Components/UI/LockedOverlay';

export default function TenderPdfList({ pdfUrls, canDownload }) {
    if (!pdfUrls?.length) return null;

    return (
        <LockedOverlay
            isLocked={!canDownload}
            message="Upgrade to download tender documents"
            plan="basic"
        >
            <div className="flex flex-col gap-2">
                {pdfUrls.map((url, i) => (
                    <a
                        key={i}
                        href={url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700 hover:underline"
                    >
                        <FileText size={14} />
                        <span>Document {i + 1}</span>
                        <Download size={12} className="ml-auto text-surface-400" />
                    </a>
                ))}
            </div>
        </LockedOverlay>
    );
}
