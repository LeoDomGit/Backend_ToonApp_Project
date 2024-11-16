import React, { useState } from "react";
import axios from "axios"; // Đảm bảo bạn đã import axios

function BackgroundGallery({
    backgroundImages,
    onDelete,
    onAddToGroup,
    allGroups, // Nhận allGroups từ props
}) {
    const [selectedImages, setSelectedImages] = useState([]);

    // Hàm xử lý chọn ảnh
    const handleSelectImage = (id) => {
        if (selectedImages.includes(id)) {
            setSelectedImages(
                selectedImages.filter((imageId) => imageId !== id)
            );
        } else {
            setSelectedImages([...selectedImages, id]);
        }
    };

    // Hàm xử lý thay đổi nhóm
    const handleGroupChange = (groupId) => {
        if (!groupId) {
            alert("Please select a group!");
            return;
        }
        if (selectedImages.length === 0) {
            alert("Please select at least one image!");
            return;
        }

        // Gọi hàm để thêm ảnh vào nhóm
        onAddToGroup(selectedImages, groupId).then((updatedImages) => {
            // Cập nhật lại backgroundImages để hiển thị ảnh đã được thêm vào nhóm
            // Giả sử 'updatedImages' là dữ liệu ảnh đã được cập nhật từ API
            // Bạn có thể điều chỉnh 'onAddToGroup' để trả về danh sách ảnh đã được cập nhật
            setSelectedImages([]); // Reset lại ảnh đã chọn
        });
    };

    return (
        <div className="row mt-5">
            {backgroundImages.map((image) => (
                <div key={image.id} className="col-md-2 mb-3 position-relative">
                    <img
                        src={`/storage/${image.path}`}
                        alt="Background"
                        className="img-fluid w-100"
                        style={{
                            border: selectedImages.includes(image.id)
                                ? "3px solid green"
                                : "none",
                        }}
                        onClick={() => handleSelectImage(image.id)}
                    />
                    <button
                        onClick={() => onDelete(image.id)}
                        className="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 me-4"
                    >
                        X - {image.id}
                    </button>
                    {image.group && (
                        <div className="badge bg-primary position-absolute bottom-0 start-0 m-2">
                            {image.group.name} {/* Hiển thị tên nhóm nếu có */}
                        </div>
                    )}
                </div>
            ))}

            <div className="col-12 mt-3">
                <label htmlFor="groupSelect">
                    Add selected images to group:
                </label>
                <select
                    id="groupSelect"
                    className="form-control mb-3"
                    onChange={(e) => handleGroupChange(e.target.value)}
                >
                    <option value="">Select a group</option>
                    {allGroups.map((group) => (
                        <option key={group.id} value={group.id}>
                            {group.name}
                        </option>
                    ))}
                </select>
            </div>
        </div>
    );
}

export default BackgroundGallery;
