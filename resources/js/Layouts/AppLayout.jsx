import Navbar from '@/Components/Layout/Navbar';
import Footer from '@/Components/Layout/Footer';
import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';

export default function AppLayout({ children }) {
    const { flash } = usePage().props;

    return (
        <div className="min-h-screen flex flex-col bg-surface-50">
            <Navbar />

            {flash?.success && (
                <div className="bg-emerald-50 border-b border-emerald-200 text-emerald-700 text-sm text-center py-2 px-4 animate-fade-in">
                    {flash.success}
                </div>
            )}
            {flash?.error && (
                <div className="bg-red-50 border-b border-red-200 text-red-600 text-sm text-center py-2 px-4 animate-fade-in">
                    {flash.error}
                </div>
            )}

            <main className="flex-1">{children}</main>

            <Footer />
        </div>
    );
}
