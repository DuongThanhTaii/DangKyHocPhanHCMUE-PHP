// src/hook/LoadingContext.tsx
import React, { createContext, useContext, useState, useCallback } from "react";

type LoadingContextType = {
    isLoading: boolean;
    startLoading: () => void;
    stopLoading: () => void;
    loadingCount: number;
};

const LoadingContext = createContext<LoadingContextType | null>(null);

/**
 * LoadingProvider - Quản lý trạng thái loading toàn cục
 * Sử dụng counter để hỗ trợ nhiều API call đồng thời
 */
export const LoadingProvider: React.FC<React.PropsWithChildren> = ({ children }) => {
    const [loadingCount, setLoadingCount] = useState(0);

    const startLoading = useCallback(() => {
        setLoadingCount((prev) => prev + 1);
    }, []);

    const stopLoading = useCallback(() => {
        setLoadingCount((prev) => Math.max(0, prev - 1));
    }, []);

    const isLoading = loadingCount > 0;

    return (
        <LoadingContext.Provider value={{ isLoading, startLoading, stopLoading, loadingCount }}>
            {children}
        </LoadingContext.Provider>
    );
};

/**
 * Hook để sử dụng loading state
 */
export const useLoading = () => {
    const ctx = useContext(LoadingContext);
    if (!ctx) throw new Error("useLoading must be used within LoadingProvider");
    return ctx;
};

/**
 * Hook wrapper cho async function với loading
 */
export const useLoadingCallback = <T extends any[], R>(
    callback: (...args: T) => Promise<R>
) => {
    const { startLoading, stopLoading } = useLoading();

    return useCallback(
        async (...args: T): Promise<R> => {
            startLoading();
            try {
                return await callback(...args);
            } finally {
                stopLoading();
            }
        },
        [callback, startLoading, stopLoading]
    );
};
