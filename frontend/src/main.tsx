import React from "react";
import ReactDOM from "react-dom/client";
import { RouterProvider } from "react-router-dom";
import { Provider } from "react-redux";
import { PersistGate } from "redux-persist/integration/react";
import { store, persistor } from "./app/store";
import { SidebarProvider } from "./app/hooks/useSidebar";
import { router } from "./router";
import { ModalProvider } from "./hook/ModalContext";
import ToastContainer from "./components/toast/ToastContainer";
import "./components/toast/toast.css";

import ConfirmRoot from "./components/confirm/ConfirmRoot";
import "./components/confirm/confirm.css";

import ChatbotWidget from "./components/chatbot/ChatbotWidget";
import "./components/chatbot/chatbot.css";

// ✅ Import PageLoader cho PersistGate loading
import PageLoader from "./components/loader/PageLoader";

ReactDOM.createRoot(document.getElementById("root")!).render(
  <React.StrictMode>
    <Provider store={store}>
      {/* ✅ Sử dụng PageLoader thay vì text đơn giản */}
      <PersistGate loading={<PageLoader text="Đang khởi động" />} persistor={persistor}>
        <SidebarProvider>
          <ModalProvider>
            <RouterProvider router={router} />
            <ChatbotWidget />
            <ToastContainer />
            <ConfirmRoot />
          </ModalProvider>
        </SidebarProvider>
      </PersistGate>
    </Provider>
  </React.StrictMode>
);
