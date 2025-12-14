// src/components/loader/NavigationLoader.tsx
import { useEffect, useState } from "react";
import { useNavigation } from "react-router-dom";
import PageLoader from "./PageLoader";
import "./loader.css";

/**
 * NavigationLoader - Shows loading animation during route transitions
 * Place this component inside RouterProvider
 */
const NavigationLoader: React.FC = () => {
    const navigation = useNavigation();
    const [showLoader, setShowLoader] = useState(false);

    useEffect(() => {
        // Show loader when navigation is in progress
        if (navigation.state === "loading") {
            setShowLoader(true);
        } else {
            // Add small delay before hiding for smoother transition
            const timeout = setTimeout(() => setShowLoader(false), 200);
            return () => clearTimeout(timeout);
        }
    }, [navigation.state]);

    if (!showLoader) return null;

    return <PageLoader text="Đang xử lý" />;
};

export default NavigationLoader;
