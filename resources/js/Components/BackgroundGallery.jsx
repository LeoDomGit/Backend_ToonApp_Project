import React, { useState } from "react";

function BackgroundGallery({
    backgroundImages,
    onDelete,
    onAddToGroup,
    groupBackgrounds,
}) {
    const [selectedImages, setSelectedImages] = useState([]);

    const handleSelectImage = (id) => {
        if (selectedImages.includes(id)) {
            setSelectedImages(
                selectedImages.filter((imageId) => imageId !== id)
            );
        } else {
            setSelectedImages([...selectedImages, id]);
        }
    };

    const handleAddToGroup = (group) => {
        onAddToGroup(selectedImages, group);
        setSelectedImages([]); // Clear selection after adding to a group
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
                </div>
            ))}

            <div className="col-12 mt-3">
                <label htmlFor="groupSelect">
                    Add selected images to group:
                </label>
                <select
                    id="groupSelect"
                    className="form-control mb-3"
                    onChange={(e) => handleAddToGroup(e.target.value)}
                >
                    <option value="">Select a group</option>
                    {groupBackgrounds.map((group, index) => (
                        <option key={index} value={group}>
                            {group}
                        </option>
                    ))}
                </select>
            </div>
        </div>
    );
}

export default BackgroundGallery;
