import React from "react";

function BackgroundGallery({ backgroundImages, onDelete }) {
    return (
        <div className="row mt-5">
            {backgroundImages.map((image) => (
                <div key={image.id} className="col-md-2 mb-3 position-relative">
                    <img
                        src={`/storage/${image.path}`}
                        alt="Background"
                        className="img-fluid w-100"
                    />
                    <button
                        onClick={() => onDelete(image.id)}
                        className="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 me-4"
                    >
                        X - {image.id}
                    </button>
                </div>
            ))}
        </div>
    );
}

export default BackgroundGallery;
