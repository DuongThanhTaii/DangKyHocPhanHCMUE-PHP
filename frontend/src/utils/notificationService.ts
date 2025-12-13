// src/utils/notificationService.ts
// Global notification service - can be called from anywhere (even outside React)

import type { ToastPayload } from "../hook/ModalContext";

type ToastListener = (payload: ToastPayload) => void;

// Singleton notification service
class NotificationService {
    private listeners = new Set<ToastListener>();

    subscribe(listener: ToastListener): () => void {
        this.listeners.add(listener);
        return () => this.listeners.delete(listener);
    }

    notify(payload: ToastPayload): void {
        const merged: ToastPayload = {
            duration: 3000,
            type: "info",
            ...payload,
        };

        // If no listeners yet (React not mounted), fallback to alert
        if (this.listeners.size === 0) {
            alert(payload.message);
            return;
        }

        this.listeners.forEach((fn) => fn(merged));
    }

    // Convenience methods
    success(message: string, title?: string): void {
        this.notify({ message, title, type: "success" });
    }

    error(message: string, title?: string): void {
        this.notify({ message, title, type: "error" });
    }

    warning(message: string, title?: string): void {
        this.notify({ message, title, type: "warning" });
    }

    info(message: string, title?: string): void {
        this.notify({ message, title, type: "info" });
    }
}

// Export singleton instance
export const notificationService = new NotificationService();
