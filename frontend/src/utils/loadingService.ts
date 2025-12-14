// src/utils/loadingService.ts
// Global loading service - tracks API loading state

type LoadingListener = (isLoading: boolean) => void;

class LoadingService {
    private loadingCount = 0;
    private listeners = new Set<LoadingListener>();

    subscribe(listener: LoadingListener): () => void {
        this.listeners.add(listener);
        // Send current state immediately
        listener(this.loadingCount > 0);
        return () => this.listeners.delete(listener);
    }

    startLoading(): void {
        this.loadingCount++;
        this.notifyListeners();
    }

    stopLoading(): void {
        this.loadingCount = Math.max(0, this.loadingCount - 1);
        this.notifyListeners();
    }

    get isLoading(): boolean {
        return this.loadingCount > 0;
    }

    private notifyListeners(): void {
        const isLoading = this.loadingCount > 0;
        this.listeners.forEach((fn) => fn(isLoading));
    }
}

export const loadingService = new LoadingService();
