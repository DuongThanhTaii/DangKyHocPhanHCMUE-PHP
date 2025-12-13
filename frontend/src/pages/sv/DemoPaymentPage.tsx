import { useEffect, useState } from "react";
import { useSearchParams, useNavigate } from "react-router-dom";
import "../../styles/reset.css";
import "../../styles/menu.css";

const API_BASE_URL = "http://localhost:8000/api";

const formatCurrency = (amount: number) =>
    new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(
        amount
    );

export default function DemoPaymentPage() {
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();

    const orderId = searchParams.get("orderId") || "";
    const provider = searchParams.get("provider") || "momo";
    const amount = parseFloat(searchParams.get("amount") || "0");

    const [loading, setLoading] = useState(false);
    const [countdown, setCountdown] = useState(5);
    const [result, setResult] = useState<{
        success: boolean;
        message: string;
    } | null>(null);

    // Provider info mapping
    const providerInfo: Record<string, { name: string; logo: string; color: string }> = {
        momo: { name: "MoMo", logo: "💜", color: "#a50064" },
        vnpay: { name: "VNPay", logo: "🔵", color: "#0066b3" },
        zalopay: { name: "ZaloPay", logo: "💙", color: "#0068ff" },
    };

    const currentProvider = providerInfo[provider] || providerInfo.momo;

    // Auto countdown for demo
    useEffect(() => {
        if (result) return;

        const timer = setInterval(() => {
            setCountdown((prev) => {
                if (prev <= 1) {
                    clearInterval(timer);
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);

        return () => clearInterval(timer);
    }, [result]);

    const handlePayment = async (status: "success" | "failed") => {
        setLoading(true);
        try {
            const response = await fetch(`${API_BASE_URL}/payment/demo-complete`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    orderId,
                    status,
                }),
            });

            const data = await response.json();

            setResult({
                success: data.success && status === "success",
                message: data.message || (status === "success" ? "Thanh toán thành công!" : "Thanh toán thất bại"),
            });

            // Redirect after 3 seconds
            setTimeout(() => {
                navigate("/sv/thanh-toan-hoc-phi");
            }, 3000);
        } catch (error) {
            setResult({
                success: false,
                message: "Lỗi kết nối server",
            });
        } finally {
            setLoading(false);
        }
    };

    if (!orderId) {
        return (
            <section className="main__body">
                <div className="body__title">
                    <p className="body__title-text">THANH TOÁN DEMO</p>
                </div>
                <div className="body__inner" style={{ textAlign: "center", padding: 40 }}>
                    <p style={{ color: "red" }}>Không tìm thấy mã giao dịch</p>
                    <button
                        className="btn__chung"
                        onClick={() => navigate("/sv/thanh-toan-hoc-phi")}
                        style={{ marginTop: 20 }}
                    >
                        Quay lại
                    </button>
                </div>
            </section>
        );
    }

    if (result) {
        return (
            <section className="main__body">
                <div className="body__title">
                    <p className="body__title-text">KẾT QUẢ THANH TOÁN</p>
                </div>
                <div
                    className="body__inner"
                    style={{
                        textAlign: "center",
                        padding: 40,
                        maxWidth: 500,
                        margin: "0 auto",
                    }}
                >
                    <div
                        style={{
                            fontSize: 64,
                            marginBottom: 20,
                        }}
                    >
                        {result.success ? "✅" : "❌"}
                    </div>
                    <h2
                        style={{
                            color: result.success ? "#16a34a" : "#dc2626",
                            marginBottom: 16,
                        }}
                    >
                        {result.success ? "THANH TOÁN THÀNH CÔNG" : "THANH TOÁN THẤT BẠI"}
                    </h2>
                    <p style={{ color: "#6b7280", marginBottom: 24 }}>{result.message}</p>
                    <p style={{ color: "#6b7280", fontSize: 14 }}>
                        Đang chuyển về trang học phí...
                    </p>
                </div>
            </section>
        );
    }

    return (
        <section className="main__body">
            <div className="body__title">
                <p className="body__title-text">CỔNG THANH TOÁN DEMO</p>
            </div>

            <div className="body__inner">
                {/* Demo Warning */}
                <div
                    style={{
                        background: "#fef3c7",
                        border: "1px solid #f59e0b",
                        borderRadius: 8,
                        padding: 16,
                        marginBottom: 24,
                        textAlign: "center",
                    }}
                >
                    <strong style={{ color: "#92400e" }}>⚠️ CHẾ ĐỘ DEMO</strong>
                    <p style={{ color: "#92400e", fontSize: 14, marginTop: 4 }}>
                        Đây là trang thanh toán giả lập. Không có giao dịch thực sự được thực
                        hiện.
                    </p>
                </div>

                {/* Payment Info Card */}
                <div
                    style={{
                        background: "#fff",
                        border: "1px solid #e5e7eb",
                        borderRadius: 12,
                        padding: 32,
                        maxWidth: 450,
                        margin: "0 auto",
                        boxShadow: "0 4px 6px -1px rgba(0,0,0,0.1)",
                    }}
                >
                    {/* Provider Header */}
                    <div
                        style={{
                            display: "flex",
                            alignItems: "center",
                            justifyContent: "center",
                            gap: 12,
                            marginBottom: 24,
                            padding: 16,
                            background: currentProvider.color,
                            borderRadius: 8,
                            color: "#fff",
                        }}
                    >
                        <span style={{ fontSize: 32 }}>{currentProvider.logo}</span>
                        <span style={{ fontSize: 20, fontWeight: 700 }}>
                            {currentProvider.name}
                        </span>
                    </div>

                    {/* Payment Details */}
                    <div style={{ marginBottom: 24 }}>
                        <div
                            style={{
                                display: "flex",
                                justifyContent: "space-between",
                                padding: "12px 0",
                                borderBottom: "1px solid #e5e7eb",
                            }}
                        >
                            <span style={{ color: "#6b7280" }}>Mã giao dịch:</span>
                            <span style={{ fontWeight: 600, fontFamily: "monospace" }}>
                                {orderId}
                            </span>
                        </div>
                        <div
                            style={{
                                display: "flex",
                                justifyContent: "space-between",
                                padding: "12px 0",
                                borderBottom: "1px solid #e5e7eb",
                            }}
                        >
                            <span style={{ color: "#6b7280" }}>Số tiền:</span>
                            <span style={{ fontWeight: 700, color: "#dc2626", fontSize: 18 }}>
                                {formatCurrency(amount)}
                            </span>
                        </div>
                    </div>

                    {/* Countdown */}
                    {countdown > 0 && (
                        <div style={{ textAlign: "center", marginBottom: 16, color: "#6b7280" }}>
                            Tự động thanh toán sau: <strong>{countdown}s</strong>
                        </div>
                    )}

                    {/* Action Buttons */}
                    <div style={{ display: "flex", gap: 12 }}>
                        <button
                            onClick={() => handlePayment("success")}
                            disabled={loading}
                            style={{
                                flex: 1,
                                padding: "14px 24px",
                                fontSize: 16,
                                fontWeight: 600,
                                color: "#fff",
                                background: "#16a34a",
                                border: "none",
                                borderRadius: 8,
                                cursor: loading ? "not-allowed" : "pointer",
                                opacity: loading ? 0.6 : 1,
                            }}
                        >
                            {loading ? "Đang xử lý..." : "✓ Thanh toán thành công"}
                        </button>
                        <button
                            onClick={() => handlePayment("failed")}
                            disabled={loading}
                            style={{
                                flex: 1,
                                padding: "14px 24px",
                                fontSize: 16,
                                fontWeight: 600,
                                color: "#fff",
                                background: "#dc2626",
                                border: "none",
                                borderRadius: 8,
                                cursor: loading ? "not-allowed" : "pointer",
                                opacity: loading ? 0.6 : 1,
                            }}
                        >
                            ✗ Hủy thanh toán
                        </button>
                    </div>

                    {/* Auto complete hint */}
                    {countdown === 0 && !loading && (
                        <p
                            style={{
                                textAlign: "center",
                                marginTop: 16,
                                color: "#16a34a",
                                fontSize: 14,
                            }}
                        >
                            Nhấn nút để hoàn tất thanh toán
                        </p>
                    )}
                </div>

                {/* Back button */}
                <div style={{ textAlign: "center", marginTop: 24 }}>
                    <button
                        onClick={() => navigate("/sv/thanh-toan-hoc-phi")}
                        style={{
                            padding: "10px 24px",
                            fontSize: 14,
                            color: "#6b7280",
                            background: "transparent",
                            border: "1px solid #d1d5db",
                            borderRadius: 6,
                            cursor: "pointer",
                        }}
                    >
                        ← Quay lại trang học phí
                    </button>
                </div>
            </div>
        </section>
    );
}
