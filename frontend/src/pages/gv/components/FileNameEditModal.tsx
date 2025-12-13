import { useState, useEffect } from "react";
import "../../../styles/tailieu-preview.css";


interface FileWithName {
  file: File;
  displayName: string;
}

interface Props {
  files: File[];
  onConfirm: (filesWithNames: { file: File; name: string }[]) => void;
  onCancel: () => void;
}

export default function FileNameEditModal({
  files,
  onConfirm,
  onCancel,
}: Props) {
  const [filesWithNames, setFilesWithNames] = useState<FileWithName[]>([]);

  useEffect(() => {
    // ✅ Init with original file names (without extension)
    const init = files.map((file) => ({
      file,
      displayName: file.name.replace(/\.[^/.]+$/, ""), // Remove extension
    }));
    setFilesWithNames(init);
  }, [files]);

  const handleNameChange = (index: number, newName: string) => {
    setFilesWithNames((prev) =>
      prev.map((item, i) =>
        i === index ? { ...item, displayName: newName } : item
      )
    );
  };

  const handleConfirm = () => {
    const result = filesWithNames.map((item) => ({
      file: item.file,
      name: item.displayName.trim() || item.file.name, // Fallback to original name
    }));
    onConfirm(result);
  };

  const getFileIcon = (fileName: string) => {
    const ext = fileName.split(".").pop()?.toLowerCase();
    const icons: Record<string, string> = {
      pdf: "📄",
      docx: "📝",
      pptx: "📊",
      txt: "📃",
      mp4: "🎥",
      jpg: "🖼️",
      jpeg: "🖼️",
      png: "🖼️",
      zip: "📦",
    };
    return icons[ext || ""] || "📎";
  };

  return (
    <div className="preview-overlay" onClick={onCancel}>
      <div
        className="preview-container"
        style={{ maxWidth: "700px", maxHeight: "70vh" }}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="preview-header">
          <h3 style={{color:"#172b4d"}} className="df_center gap_10"><svg style={{ width: "24px", height: "24px",}} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="#172b4d" d="M128.1 0c-35.3 0-64 28.7-64 64l0 384c0 35.3 28.7 64 64 64l146.2 0 10.9-54.5c4.3-21.7 15-41.6 30.6-57.2l132.2-132.2 0-97.5c0-17-6.7-33.3-18.7-45.3L322.8 18.7C310.8 6.7 294.5 0 277.6 0L128.1 0zM389.6 176l-93.5 0c-13.3 0-24-10.7-24-24l0-93.5 117.5 117.5zM332.3 466.9l-11.9 59.6c-.2 .9-.3 1.9-.3 2.9 0 8 6.5 14.6 14.6 14.6 1 0 1.9-.1 2.9-.3l59.6-11.9c12.4-2.5 23.8-8.6 32.7-17.5l118.9-118.9-80-80-118.9 118.9c-8.9 8.9-15 20.3-17.5 32.7zm267.8-123c22.1-22.1 22.1-57.9 0-80s-57.9-22.1-80 0l-28.8 28.8 80 80 28.8-28.8z" /></svg> Đặt tên cho {files.length} file</h3>
          <button className="preview-close" onClick={onCancel}>
            ✕
          </button>
        </div>

        <div
          className="preview-body"
          style={{ padding: "20px", overflow: "auto" }}
        >
          {filesWithNames.map((item, index) => (
            <div
              key={index}
              style={{
                marginBottom: "16px",
                padding: "12px",
                border: "1px solid #e5e7eb",
                borderRadius: "8px",
                backgroundColor: "#f9fafb",
              }}
            >
              <div
                style={{
                  display: "flex",
                  alignItems: "center",
                  gap: "12px",
                  marginBottom: "8px",
                }}
              >
                <span style={{ fontSize: "24px" }}>
                  {getFileIcon(item.file.name)}
                </span>
                <div style={{ flex: 1 }}>
                  <div
                    style={{
                      fontSize: "12px",
                      color: "#6b7280",
                      marginBottom: "4px",
                    }}
                  >
                    File gốc: {item.file.name}
                  </div>
                  <input
                    type="text"
                    value={item.displayName}
                    onChange={(e) => handleNameChange(index, e.target.value)}
                    placeholder="Nhập tên hiển thị..."
                    style={{
                      width: "100%",
                      padding: "8px 12px",
                      border: "1px solid #d1d5db",
                      borderRadius: "6px",
                      fontSize: "14px",
                    }}
                  />
                </div>
              </div>
            </div>
          ))}
        </div>

        <div
          className="preview-footer"
          style={{
            display: "flex",
            justifyContent: "flex-end",
            gap: "12px",
            padding: "16px 24px",
          }}
        >
          <button
            className="btn-cancel"
            onClick={onCancel}

          >
            Hủy
          </button>
          <button className="btn__chung" onClick={handleConfirm}>
            Xác nhận upload
          </button>
        </div>
      </div>
    </div>
  );
}
