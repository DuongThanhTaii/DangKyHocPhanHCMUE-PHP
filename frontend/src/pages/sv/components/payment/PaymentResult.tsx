import { useEffect, useState } from "react";
import { useSearchParams, useNavigate } from "react-router-dom";
import { usePaymentStatus } from "../../../../features/sv/hooks";
import "./PaymentResult.css";

// Provider info mapping
const providerInfo: Record<string, { name: string; color: string }> = {
    momo: { name: "MoMo", color: "#a50064" },
    vnpay: { name: "VNPay", color: "#0066b3" },
    zalopay: { name: "ZaloPay", color: "#0068ff" },
};

// SVG Icons
const SuccessIcon = () => (
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="12" cy="12" r="10" fill="#16a34a"/>
        <path d="M8 12l2.5 2.5L16 9" stroke="#fff" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
    </svg>
);

const ErrorIcon = () => (
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="12" cy="12" r="10" fill="#dc2626"/>
        <path d="M15 9l-6 6M9 9l6 6" stroke="#fff" strokeWidth="2" strokeLinecap="round"/>
    </svg>
);

const PendingIcon = () => (
    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" className="pending-icon">
        <circle cx="12" cy="12" r="10" stroke="#ea580c" strokeWidth="2" fill="none"/>
        <path d="M12 6v6l4 2" stroke="#ea580c" strokeWidth="2" strokeLinecap="round"/>
    </svg>
);

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(amount);

export default function PaymentResult() {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();

    // Extract orderId from different providers
    const orderId =
        searchParams.get("orderId") ||      // MoMo
        searchParams.get("vnp_TxnRef") ||   // VNPay
        searchParams.get("apptransid") ||   // ZaloPay
        "";

    const provider = searchParams.get("provider") || "momo";
    const amount = parseFloat(searchParams.get("amount") || "0");
    const currentProvider = providerInfo[provider] || providerInfo.momo;

    // Use IPN polling hook
    const { status, loading } = usePaymentStatus(orderId, 30, 1500, 3000);

    const [showDetails, setShowDetails] = useState(false);

    const isSuccess = status?.status === "success";
    const isPending = status?.status === "pending" || loading;
    const isFailed = status?.status === "failed" || status?.status === "cancelled";

    const handleBackToHome = () => {
        navigate("/sv/thanh-toan-hoc-phi");
    };

    // Auto redirect on success after 5 seconds
    useEffect(() => {
        if (isSuccess) {
            const timer = setTimeout(() => {
                navigate("/sv/thanh-toan-hoc-phi");
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [isSuccess, navigate]);

    // No orderId
    if (!orderId) {
        return (
            <section className="payment-result">
                <div className="payment-result-card">
                    <div className="payment-result-icon error"><ErrorIcon /></div>
                    <h2 className="payment-result-title error">Không tìm thấy mã đơn hàng</h2>
                    <p className="payment-result-message">
                        Vui lòng thử lại từ trang thanh toán học phí
                    </p>
                    <button className="payment-result-btn" onClick={handleBackToHome}>
                        Quay lại
                    </button>
                </div>
            </section>
        );
    }

    return (
        <section className="payment-result">
            {/* Payment Card */}
            <div className="payment-result-card">
                {/* Provider Header */}
                <div 
                    className="payment-provider-header"
                    style={{ background: currentProvider.color }}
                >
                    <span className="provider-name">{currentProvider.name}</span>
                </div>

                {/* Status Icon */}
                <div className={`payment-result-icon ${isSuccess ? 'success' : isFailed ? 'error' : 'pending'}`}>
                    {isSuccess && <SuccessIcon />}
                    {isPending && <PendingIcon />}
                    {isFailed && <ErrorIcon />}
                </div>

                {/* Title */}
                <h2 className={`payment-result-title ${isSuccess ? 'success' : isFailed ? 'error' : 'pending'}`}>
                    {isSuccess && "Thanh toán thành công!"}
                    {isPending && "Đang xử lý thanh toán..."}
                    {isFailed && "Thanh toán thất bại"}
                </h2>

                {/* Message */}
                <p className="payment-result-message">
                    {isSuccess && "Học phí của bạn đã được thanh toán thành công"}
                    {isPending && "Vui lòng đợi trong giây lát, hệ thống đang xác nhận giao dịch"}
                    {isFailed && "Giao dịch không thành công. Vui lòng thử lại"}
                </p>

                {/* Payment Details */}
                <div className="payment-details">
                    <div className="payment-detail-row">
                        <span className="detail-label">Mã giao dịch:</span>
                        <span className="detail-value mono">{orderId}</span>
                    </div>
                    {(amount > 0 || status?.amount) && (
                        <div className="payment-detail-row">
                            <span className="detail-label">Số tiền:</span>
                            <span className="detail-value amount">
                                {formatCurrency(status?.amount || amount)}
                            </span>
                        </div>
                    )}
                    <div className="payment-detail-row">
                        <span className="detail-label">Trạng thái:</span>
                        <span className={`detail-value status ${isSuccess ? 'success' : isFailed ? 'error' : 'pending'}`}>
                            {isSuccess && "Thành công"}
                            {isPending && "Đang xử lý"}
                            {isFailed && "Thất bại"}
                        </span>
                    </div>
                </div>

                {/* Loading indicator for pending */}
                {isPending && (
                    <div className="payment-loading">
                        <div className="loading-spinner"></div>
                        <p>Đang chờ xác nhận từ cổng thanh toán...</p>
                    </div>
                )}

                {/* Toggle Details */}
                <button 
                    className="toggle-details-btn"
                    onClick={() => setShowDetails(!showDetails)}
                >
                    {showDetails ? "Ẩn chi tiết ▲" : "Xem chi tiết ▼"}
                </button>

                {showDetails && status && (
                    <div className="payment-extra-details">
                        <div className="extra-detail-row">
                            <strong>Order ID:</strong> {status.orderId}
                        </div>
                        <div className="extra-detail-row">
                            <strong>Thời gian:</strong> {status.createdAt ? new Date(status.createdAt).toLocaleString("vi-VN") : "-"}
                        </div>
                    </div>
                )}

                {/* Auto redirect message */}
                {isSuccess && (
                    <p className="auto-redirect-msg">
                        Tự động chuyển về trang học phí sau 5 giây...
                    </p>
                )}

                {/* Action Button */}
                <button className="payment-result-btn" onClick={handleBackToHome}>
                    {isSuccess ? "Quay lại trang học phí" : isFailed ? "Thử lại" : "Quay lại"}
                </button>
            </div>
        </section>
    );
}
