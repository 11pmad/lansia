export default function ApplicationLogo({ className = 'h-12 w-12', ...props }) {
    return (
        <div className={`inline-flex items-center justify-center rounded-xl bg-teal-700 text-white shadow-sm ${className}`} {...props}>
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
                className="h-3/5 w-3/5"
            >
                {/* Healthcare plus inside a heart badge */}
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
                <path d="M12 7v6" />
                <path d="M9 10h6" />
            </svg>
        </div>
    );
}
