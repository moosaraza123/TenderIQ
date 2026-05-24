import { Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';

export default function Footer() {
    return (
        <footer className="border-t border-surface-200 bg-white mt-16">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div className="flex flex-col md:flex-row justify-between items-start gap-8">
                    <div>
                        <Link href="/" className="flex items-center gap-2 mb-3">
                            <div className="w-7 h-7 bg-primary-500 rounded-lg flex items-center justify-center">
                                <FileText size={13} className="text-white" />
                            </div>
                            <span className="font-bold text-surface-900">TenderIQ</span>
                        </Link>
                        <p className="text-sm text-surface-400 max-w-xs">
                            AI-powered GCC &amp; Pakistan government tender aggregation platform.
                        </p>
                    </div>

                    <div className="flex gap-12 text-sm">
                        <div>
                            <p className="font-semibold text-surface-800 mb-3">Product</p>
                            <div className="flex flex-col gap-2 text-surface-400">
                                <Link href="/tenders" className="hover:text-primary-600">Browse Tenders</Link>
                                <Link href="/register" className="hover:text-primary-600">Get Started</Link>
                            </div>
                        </div>
                        <div>
                            <p className="font-semibold text-surface-800 mb-3">Legal</p>
                            <div className="flex flex-col gap-2 text-surface-400">
                                <Link href="/privacy" className="hover:text-primary-600">Privacy</Link>
                                <Link href="/terms" className="hover:text-primary-600">Terms</Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mt-8 pt-6 border-t border-surface-100 text-center text-xs text-surface-400">
                    © {new Date().getFullYear()} TenderIQ. Data sourced from PPRA, ADGPG, Etimad & more.
                </div>
            </div>
        </footer>
    );
}
