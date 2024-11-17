import React, { useState } from "react";
import { Tooltip } from "react-tooltip"; // Import Tooltip from react-tooltip

function BackgroundGallery({
    backgroundImages,
    onDelete,
    onAddToGroup,
    allGroups,
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

        // Add images to the group and update the state
        onAddToGroup(selectedImages, groupId).then((updatedImages) => {
            // Update the group name in the images
            const updatedBackgroundImages = backgroundImages.map((image) => {
                if (selectedImages.includes(image.id)) {
                    return {
                        ...image,
                        group: allGroups.find((group) => group.id === groupId),
                    };
                }
                return image;
            });
            // Reset the selected images
            setSelectedImages([]);
            // Optionally, you may want to update the backgroundImages state here
            // setBackgroundImages(updatedBackgroundImages); // If you want to update state directly
        });
    };

    return (
        <div className="row mt-5">
            {backgroundImages.map((image) => (
                <div key={image.id} className="col-md-2 mb-3 position-relative">
                    <div
                        style={{
                            position: "relative",
                            width: "100px",
                            height: "100px",
                        }}
                        data-tooltip-id={`tooltip-${image.id}`}
                        data-tooltip-content={
                            image.group
                                ? `Group: ${image.group.name}`
                                : "No Group"
                        } // Nội dung tooltip
                    >
                        <img
                            src={`/storage/${image.path}`}
                            alt="Background"
                            className="img-fluid"
                            style={{
                                width: "100%",
                                height: "100%",
                                objectFit: "cover",
                                border: selectedImages.includes(image.id)
                                    ? "3px solid green"
                                    : "none",
                            }}
                            onClick={() => handleSelectImage(image.id)}
                        />
                        <button
                            onClick={() => onDelete(image.id)}
                            className="btn btn-danger btn-sm position-absolute top-0 end-0"
                            style={{
                                transform: "translate(50%, -50%)",
                                zIndex: 2,
                            }}
                        >
                            X - {image.id}
                        </button>
                        {/* Tooltip */}
                        <Tooltip id={`tooltip-${image.id}`} />
                    </div>
                    {image.group && (
                        <div className="badge bg-primary position-absolute bottom-0 start-0 m-2">
                            {image.group.name}
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
