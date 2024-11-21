import React, { useEffect, useState } from "react";
import Layout from "../../Components/Layout";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import JoditEditor from "jodit-react";
import { ToastContainer, toast } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import axios from "axios";
import { Box, Typography } from "@mui/material";
import { DataGrid } from "@mui/x-data-grid";
import Swal from "sweetalert2";

function Index({ data }) {
    const [packages, setPackages] = useState(data);
    const [name, setName] = useState("");
    const [price, setPrice] = useState(null);
    const [duration, setDuration] = useState(null);
    const [description, setDescription] = useState("");
    const [image, setImage] = useState(null);
    const [selectedRow, setSelectedRow] = useState(null);
    const [show, setShow] = useState(false);
    const [showImageModal, setShowImageModal] = useState(false);

    // New states for description editing modal
    const [editDescription, setEditDescription] = useState("");
    const [editDescriptionId, setEditDescriptionId] = useState(null);
    const [showDescriptionModal, setShowDescriptionModal] = useState(false);

    const handleClose = () => setShow(false);
    const handleShow = () => setShow(true);

    const resetCreate = () => {
        setName("");
        setPrice(null);
        setDuration(null);
        setDescription("");
        setImage(null);
        setShow(true);
    };
    
    const showAlert = (status, msg) => {
        if (status === "error") {
            toast.error(msg, { position: "top-right" });
        } else {
            toast.success(msg, { position: "top-right" });
        }
    };

    const columns = [
        { field: "id", headerName: "#", width: 100 },
        { field: "name", headerName: "Features", width: 200, editable: true },
        { field: "price", headerName: "Price", width: 200, editable: true },
        {
            field: "duration",
            headerName: "Duration",
            width: 200,
            editable: true,
        },
        {
            field: "description",
            headerName: "Description",
            width: 200,
            renderCell: (params) => (
                <Typography
                    onClick={() =>
                        handleDescriptionEdit(params.row.id, params.value)
                    }
                    dangerouslySetInnerHTML={{ __html: params.value }}
                    variant="body2"
                    style={{ cursor: "pointer", color: "blue" }}
                />
            ),
        },
        {
            field: "image",
            headerName: "Image",
            width: 100,
            renderCell: (params) => (
                <img
                    src={params.value ? params.value : "/default-image.jpg"}
                    alt="package image"
                    style={{
                        width: "50px",
                        height: "50px",
                        objectFit: "cover",
                        cursor: "pointer",
                    }}
                    onClick={() => {
                        setSelectedRow(params.row.id);
                        setShowImageModal(true);
                    }}
                />
            ),
        },
        {
            field: "product_id_and",
            headerName: "Product ID on Google Play",
            width: 200,
            editable: true,
        },
        {
            field: "product_id_ios",
            headerName: "Product ID on App Store",
            width: 200,
            editable: true,
        },
        {
            field: "status",
            headerName: "Status",
            width: 200,
            renderCell: (params) => (
                <input
                    key={params.row.id}
                    type="checkbox"
                    className="text-center"
                    checked={params.value}
                    onChange={(event) => {
                        const checked = event.target.checked;
                        axios
                            .put(`/packages/${params.row.id}`, {
                                status: checked,
                            })
                            .then((res) => {
                                if (res.data.check === true) {
                                    toast.success(
                                        "Status updated successfully!",
                                        {
                                            position: "top-right",
                                        }
                                    );
                                    setPackages(res.data.data);
                                }
                            });
                    }}
                />
            ),
            editable: true,
        },
        {
            field: "payment_method",
            headerName: "Payment Method",
            width: 200,
            editable: true,
        },
    ];

    const handleDescriptionEdit = (id, currentDescription) => {
        setEditDescription(currentDescription);
        setEditDescriptionId(id);
        setShowDescriptionModal(true);
    };

    const submitDescriptionEdit = () => {
        axios
            .put(`/packages/${editDescriptionId}`, {
                description: editDescription,
            })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Description updated successfully!", {
                        position: "top-right",
                    });
                    setPackages(res.data.data);
                    setShowDescriptionModal(false);
                } else {
                    toast.error("Failed to update description.", {
                        position: "top-right",
                    });
                }
            });
    };

    const submitPackage = () => {
        if (!name) {
            showAlert("error", "Name is required");
        } else if (!price) {
            showAlert("error", "Price is required");
        } else if (!duration) {
            showAlert("error", "Duration is required");
        } else if (!description) {
            showAlert("error", "Description is required");
        } else {
            axios
                .post(
                    "/packages",
                    {
                        name,
                        price,
                        duration,
                        description,
                        image,
                    },
                    {
                        headers: {
                            "Content-Type": "multipart/form-data",
                        },
                    }
                )
                .then((res) => {
                    if (res.data.check == true) {
                        setPackages(res.data.data);
                        showAlert("success", res.data.msg);
                        resetCreate();
                        handleClose();
                    }
                })
                .catch((error) => {
                    showAlert("error", "An error occurred. Please try again.");
                    console.error(error);
                });
        }
    };

    const submitImage = () => {
        const formData = new FormData();
        formData.append("image", image);
        axios
            .post(`/packages-update-image/${selectedRow}`, formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Update effect's image successfully", {
                        position: "top-right",
                    });
                    setPackages(res.data.data);
                    setImage(null);
                    setShowImageModal(false);
                } else {
                    toast.error(res.data.msg, {
                        position: "top-right",
                    });
                }
            });
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
                    axios.delete(`/packages/${id}`).then((res) => {
                        if (res.data.check) {
                            toast.success("Đã xóa thành công !", {
                                position: "top-right",
                            });
                            setPackages(res.data.data);
                        } else {
                            toast.error(res.data.msg, {
                                position: "top-right",
                            });
                        }
                    });
                }
            });
        } else {
            axios.put(`/packages/${id}`, { [field]: value }).then((res) => {
                if (res.data.check) {
                    toast.success("Chỉnh sửa thành công !", {
                        position: "top-right",
                    });
                    setPackages(res.data.data);
                } else {
                    toast.error(res.data.msg, {
                        position: "top-right",
                    });
                }
            });
        }
    };
    return (
        <Layout>
            <>
                <ToastContainer />
                <div className="row">
                    <div className="col-md">
                        <button
                            className="btn btn-outline-primary mb-3"
                            onClick={resetCreate}
                        >
                            Create
                        </button>
                    </div>
                    <Modal show={show} onHide={handleClose}>
                        <Modal.Header closeButton>
                            <Modal.Title>Add Package</Modal.Title>
                        </Modal.Header>
                        <Modal.Body>
                            <label>Name</label>
                            <input
                                type="text"
                                placeholder="Package's Name"
                                value={name}
                                className="form-control mb-3"
                                onChange={(e) => setName(e.target.value)}
                            />
                            <label>Price</label>
                            <input
                                type="number"
                                placeholder="Package's Price"
                                value={price}
                                className="form-control mb-3"
                                onChange={(e) => setPrice(e.target.value)}
                            />
                            <label>Duration</label>
                            <input
                                type="number"
                                placeholder="Package's Duration"
                                value={duration}
                                className="form-control mb-3"
                                onChange={(e) => setDuration(e.target.value)}
                            />
                            <input
                                type="file"
                                className="form-control mb-3"
                                placeholder="Upload effect's image"
                                accept="image/*"
                                onChange={(e) => setImage(e.target.files[0])}
                            />
                            {/* <JoditEditor
                                value={description}
                                config={{ readonly: false, height: 400 }}
                                tabIndex={1}
                                onBlur={(newContent) =>
                                    setDescription(newContent)
                                }
                            /> */}
                            <textarea
                                type="text"
                                className="form-control"
                                value={description}
                                placeholder="Package's description"
                                onChange={(e) => setDescription(e.target.value)}
                            />
                        </Modal.Body>
                        <Modal.Footer>
                            <Button variant="secondary" onClick={handleClose}>
                                Close
                            </Button>
                            <Button variant="primary" onClick={submitPackage}>
                                Save
                            </Button>
                        </Modal.Footer>
                    </Modal>

                    <Modal
                        show={showDescriptionModal}
                        onHide={() => setShowDescriptionModal(false)}
                    >
                        <Modal.Header closeButton>
                            <Modal.Title>Edit Description</Modal.Title>
                        </Modal.Header>
                        <Modal.Body>
                            {
                                /* <JoditEditor
                                value={editDescription}
                                config={{ readonly: false, height: 400 }}
                                tabIndex={1}
                                onBlur={(newContent) =>
                                    setEditDescription(newContent)
                                }
                            /> */
                                <textarea
                                    type="text"
                                    className="form-control"
                                    value={editDescription}
                                    placeholder="Package's description"
                                    onChange={(e) =>
                                        setEditDescription(e.target.value)
                                    }
                                />
                            }
                        </Modal.Body>
                        <Modal.Footer>
                            <Button
                                variant="secondary"
                                onClick={() => setShowDescriptionModal(false)}
                            >
                                Close
                            </Button>
                            <Button
                                variant="primary"
                                onClick={submitDescriptionEdit}
                            >
                                Update
                            </Button>
                        </Modal.Footer>
                    </Modal>
                    <Modal
                        show={showImageModal}
                        onHide={() => setShowImageModal(false)}
                    >
                        <Modal.Header closeButton>
                            <Modal.Title>Update Package Image</Modal.Title>
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
                            <Button
                                variant="secondary"
                                onClick={() => setShowImageModal(false)}
                            >
                                Close
                            </Button>
                            <Button
                                variant="primary"
                                disabled={!image}
                                onClick={submitImage}
                            >
                                Update
                            </Button>
                        </Modal.Footer>
                    </Modal>
                </div>
                <div className="row">
                    <div className="col-md">
                        <Box
                            sx={{
                                width: "100%",
                                height: 400,
                                overflowX: "auto",
                                overflowY: "hidden",
                            }}
                        >
                            <DataGrid
                                rows={packages}
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
            </>
        </Layout>
    );
}

export default Index;
