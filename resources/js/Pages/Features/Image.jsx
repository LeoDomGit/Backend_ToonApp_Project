import React, { useState } from "react";
import Layout from "../../Components/Layout";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { Notyf } from "notyf";
import { Box } from "@mui/material";
import { DataGrid } from "@mui/x-data-grid";
import "notyf/notyf.min.css";
import axios from "axios";

function Image({ datafeatures }) {
    const [images, setImages] = useState([]);
    const [apiRoute, setApiRoute] = useState("");
    const [data, setData] = useState(datafeatures);
    const [showImageModal, setShowImageModal] = useState(false);
    const closeImageModal = () => setShowImageModal(false);
    const [show, setShow] = useState(false);
    const [selectedRowId, setSelectedRowId] = useState(null);
    const formatCreatedAt = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleString();
    };

    const openImageModal = (id) => {
        setSelectedRowId(id);
        setShowImageModal(true);
    };
    const handleClose = () => setShow(false);
    const handleShow = () => setShow(true);

    const notyf = new Notyf({
        duration: 1000,
        position: { x: "right", y: "top" },
        types: [
            { type: "error", background: "indianred", duration: 2000 },
            { type: "success", background: "green", duration: 2000 },
        ],
    });

    const columns = [
        { field: "id", headerName: "#", width: 100 },
        { field: "api_route", headerName: "API Route", editable: true, width: 200 },
        {
            field: "path",
            headerName: "Image",
            width: 100,
            renderCell: (params) => (
                <img
                    src={
                        params.value
                            ? `/storage/${params.value}`
                            : "/default-image.jpg"
                    }
                    alt="Feature"
                    style={{
                        width: "50px",
                        height: "50px",
                        objectFit: "cover",
                        cursor: "pointer",
                    }}
                    onClick={() => openImageModal(params.row.id)} // Open modal when image is clicked
                />
            ),
        },
        {
            field: "created_at",
            headerName: "Created at",
            width: 200,
            valueGetter: (params) => formatCreatedAt(params),
        },
    ];
    const [image, setImage] = useState(null);

    const handleFileChange = (e) => {
        setImages([...e.target.files]); // Set multiple images
    };
    
    const updateImage = () => {
        const formData = new FormData();
        formData.append("image", image);

        axios
            .post(`/api-features-update-image/${selectedRowId}`, formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((res) => {
                if (res.data.check) {
                    notyf.success("Ảnh đã được cập nhật thành công");
                    setData(res.data.data);
                    closeImageModal();
                } else {
                    notyf.error(res.data.msg);
                }
            });
    };
    const submitFeatureImages = () => {
        const formData = new FormData();
        formData.append("api_route", apiRoute);
        images.forEach((image, index) => {
            formData.append(`images[${index}]`, image); // Append each image
        });

        axios
            .post("/api_images", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((res) => {
                if (res.data.check) {
                    notyf.success(res.data.msg);
                    setData(res.data.data);
                    resetForm();
                    setShow(false);
                } else {
                    notyf.error(res.data.msg);
                }
            })
            .catch((error) => {
                notyf.error("An error occurred while uploading.");
            });
    };

    const resetForm = () => {
        setImages([]);
        setApiRoute("");
    };
    const handleCellEditStop = (id, field, value) => {
        if (field === "name" && value === "") {
            Swal.fire({
                icon: "question",
                text: "Bạn muốn xóa feature này?",
                showDenyButton: true,
                confirmButtonText: "Đúng",
                denyButtonText: "Không",
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/api_images/${id}`).then((res) => {
                        if (res.data.check) {
                            notyf.success("Đã xóa thành công");
                            setData((prevData) =>
                                prevData.filter((item) => item.id !== id)
                            );
                        } else {
                            notyf.error(res.data.msg);
                        }
                    });
                }
            });
        } else {
            axios.put(`/api_images/${id}`, { [field]: value }).then((res) => {
                if (res.data.check) {
                    notyf.success("Chỉnh sửa thành công");
                    setData((prevData) =>
                        prevData.map((item) =>
                            item.id === id ? { ...item, [field]: value } : item
                        )
                    );
                } else {
                    notyf.error(res.data.msg);
                }
            });
        }
    };
    return (
        <Layout>
            <>
            <Modal show={showImageModal} onHide={closeImageModal}>
                    <Modal.Header closeButton>
                        <Modal.Title>Thay đổi hình ảnh</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <input
                            type="file"
                            className="form-control"
                            accept="image/*"
                            onChange={(e) => setImage(e.target.files[0])}
                        />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={closeImageModal}>
                            Đóng
                        </Button>
                        <Button
                            variant="primary"
                            onClick={updateImage}
                            disabled={!image}
                        >
                            Cập nhật ảnh
                        </Button>
                    </Modal.Footer>
                </Modal>

                <Modal show={show} onHide={handleClose}>
                    <Modal.Header closeButton>
                        <Modal.Title>Upload Feature Images</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <input
                            type="text"
                            className="form-control mt-2"
                            placeholder="API Route"
                            value={apiRoute}
                            onChange={(e) => setApiRoute(e.target.value)}
                        />
                        <input
                            type="file"
                            className="form-control mt-2"
                            accept="image/*"
                            multiple
                            onChange={handleFileChange}
                        />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={handleClose}>
                            Close
                        </Button>
                        <Button
                            variant="primary"
                            onClick={submitFeatureImages}
                            disabled={!apiRoute || images.length === 0}
                        >
                            Upload Images
                        </Button>
                    </Modal.Footer>
                </Modal>

                {/* Navbar for Adding New Image */}
                <nav className="navbar navbar-expand-lg navbar-light bg-light">
                    <div className="container-fluid">
                        <button
                            className="navbar-toggler"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#navbarSupportedContent"
                            aria-controls="navbarSupportedContent"
                            aria-expanded="false"
                            aria-label="Toggle navigation"
                        >
                            <span className="navbar-toggler-icon" />
                        </button>
                        <div className="collapse navbar-collapse" id="navbarSupportedContent">
                            <Button className="btn btn-primary" onClick={handleShow}>
                                Upload New Images
                            </Button>
                        </div>
                    </div>
                </nav>

                {/* Data Grid Displaying Feature Images */}
                <div className="row">
                    <div className="col-md-9">
                        {data && data.length > 0 && (
                            <div className="card border-0 shadow">
                                <div className="card-body">
                                    <Box sx={{ height: 400, width: "100%" }}>
                                        <DataGrid
                                            rows={data}
                                            columns={columns}
                                            pageSizeOptions={[5]}
                                            checkboxSelection
                                            disableRowSelectionOnClick
                                            onCellEditStop={(params, e) =>
                                                handleCellEditStop(
                                                    params.row.id,
                                                    params.field,
                                                    e.target.value
                                                )
                                            }
                                        />
                                    </Box>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </>
        </Layout>
    );
}

export default Image;
