export default function Card({ children, className = '', hover = false, ...props }) {
    const base  = 'bg-white border border-surface-200 rounded-card shadow-card';
    const hoverCls = hover
        ? 'hover:shadow-card-hover hover:border-primary-200 hover:-translate-y-px transition-all duration-200 cursor-pointer'
        : '';

    return (
        <div className={`${base} ${hoverCls} ${className}`} {...props}>
            {children}
        </div>
    );
}
