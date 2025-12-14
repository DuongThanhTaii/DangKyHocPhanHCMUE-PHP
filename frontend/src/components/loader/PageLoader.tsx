// src/components/loader/PageLoader.tsx
import React from "react";
import "./loader.css";

type PageLoaderProps = {
    text?: string;
    showProgress?: boolean;
};

/**
 * Full-screen page loading component
 * Use with React.Suspense for lazy-loaded routes
 */
const PageLoader: React.FC<PageLoaderProps> = ({
    text = "Đang tải",
    showProgress = true,
}) => {
    return (
        <div className="page-loader">
            {/* Spinner */}
            <div className="loader-spinner" />

            {/* Loading text with animated dots */}
            <div className="loader-text">
                {text}
                <span className="loader-dots">
                    <span />
                    <span />
                    <span />
                </span>
            </div>

            {/* Progress bar (optional) */}
            {showProgress && (
                <div className="loader-progress">
                    <div className="loader-progress-bar" />
                </div>
            )}
        </div>
    );
};

export default PageLoader;
