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

function FeedbackIndex({ data }) {
    const [feedbacks, setFeedbacks] = useState(data);
    const [deviceId, setDeviceId] = useState("");
    const [platform, setPlatform] = useState("");
    const [feedback, setFeedback] = useState("");
    const [note, setNote] = useState("");
    const [status, setStatus] = useState(false);
    const [show, setShow] = useState(false);

    // New states for description editing modal
    const [editFeedback, setEditFeedback] = useState("");
    const [editFeedbackId, setEditFeedbackId] = useState(null);
    const [showFeedbackModal, setShowFeedbackModal] = useState(false);

    const handleClose = () => setShow(false);
    const handleShow = () => setShow(true);

    const resetCreate = () => {
        setDeviceId("");
        setPlatform("");
        setFeedback("");
        setNote("");
        setStatus(false);
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
        {
            field: "device_id",
            headerName: "Device ID",
            width: 200,
            editable: true,
        },
        {
            field: "platform",
            headerName: "Platform",
            width: 200,
            editable: true,
        },
        {
            field: "feedback",
            headerName: "Feedback",
            width: 200,
            renderCell: (params) => (
                <Typography
                    onClick={() =>
                        handleFeedbackEdit(params.row.id, params.value)
                    }
                    dangerouslySetInnerHTML={{ __html: params.value }}
                    variant="body2"
                    style={{ cursor: "pointer", color: "blue" }}
                />
            ),
        },
        { field: "note", headerName: "Note", width: 200, editable: true },
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
                            .put(`/feedback/${params.row.id}`, {
                                status: checked,
                            })
                            .then((res) => {
                                if (res.data.check === true) {
                                    toast.success(
                                        "Status updated successfully!",
                                        { position: "top-right" }
                                    );
                                    setFeedbacks(res.data.data);
                                }
                            });
                    }}
                />
            ),
            editable: true,
        },
    ];

    const handleFeedbackEdit = (id, currentFeedback) => {
        setEditFeedback(currentFeedback);
        setEditFeedbackId(id);
        setShowFeedbackModal(true);
    };

    const submitFeedbackEdit = () => {
        axios
            .put(`/feedback/${editFeedbackId}`, { feedback: editFeedback })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Feedback updated successfully!", {
                        position: "top-right",
                    });
                    setFeedbacks(res.data.data);
                    setShowFeedbackModal(false);
                } else {
                    toast.error("Failed to update feedback.", {
                        position: "top-right",
                    });
                }
            });
    };

    const submitFeedback = () => {
        if (!deviceId) {
            showAlert("error", "Device ID is required");
        } else if (!feedback) {
            showAlert("error", "Feedback is required");
        } else {
            axios
                .post("/feedback", {
                    device_id: deviceId,
                    platform,
                    feedback,
                    note,
                    status,
                })
                .then((res) => {
                    if (res.data.check === true) {
                        setFeedbacks(res.data.data);
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

    const handleCellEditStop = (id, field, value) => {
        if (field === "device_id" && value === "") {
            Swal.fire({
                icon: "question",
                text: "Bạn muốn xóa feedback này?",
                showDenyButton: true,
                confirmButtonText: "Đúng",
                denyButtonText: "Không",
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/feedback/${id}`).then((res) => {
                        if (res.data.check) {
                            toast.success("Đã xóa thành công !", {
                                position: "top-right",
                            });
                            setFeedbacks(res.data.data);
                        } else {
                            toast.error(res.data.msg, {
                                position: "top-right",
                            });
                        }
                    });
                }
            });
        } else {
            axios.put(`/feedback/${id}`, { [field]: value }).then((res) => {
                if (res.data.check) {
                    toast.success("Chỉnh sửa thành công !", {
                        position: "top-right",
                    });
                    setFeedbacks(res.data.data);
                } else {
                    toast.error(res.data.msg, { position: "top-right" });
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
                            <Modal.Title>Add Feedback</Modal.Title>
                        </Modal.Header>
                        <Modal.Body>
                            <label>Device ID</label>
                            <input
                                type="text"
                                value={deviceId}
                                className="form-control mb-3"
                                onChange={(e) => setDeviceId(e.target.value)}
                            />
                            <label>Platform</label>
                            <input
                                type="text"
                                value={platform}
                                className="form-control mb-3"
                                onChange={(e) => setPlatform(e.target.value)}
                            />
                            <label>Feedback</label>
                            <JoditEditor
                                value={feedback}
                                config={{ readonly: false, height: 400 }}
                                tabIndex={1}
                                onBlur={(newContent) => setFeedback(newContent)}
                            />
                            <label>Note</label>
                            <input
                                type="text"
                                value={note}
                                className="form-control mb-3"
                                onChange={(e) => setNote(e.target.value)}
                            />
                        </Modal.Body>
                        <Modal.Footer>
                            <Button variant="secondary" onClick={handleClose}>
                                Close
                            </Button>
                            <Button variant="primary" onClick={submitFeedback}>
                                Save
                            </Button>
                        </Modal.Footer>
                    </Modal>

                    <Modal
                        show={showFeedbackModal}
                        onHide={() => setShowFeedbackModal(false)}
                    >
                        <Modal.Header closeButton>
                            <Modal.Title>Edit Feedback</Modal.Title>
                        </Modal.Header>
                        <Modal.Body>
                            <JoditEditor
                                value={editFeedback}
                                config={{ readonly: false, height: 400 }}
                                tabIndex={1}
                                onBlur={(newContent) =>
                                    setEditFeedback(newContent)
                                }
                            />
                        </Modal.Body>
                        <Modal.Footer>
                            <Button
                                variant="secondary"
                                onClick={() => setShowFeedbackModal(false)}
                            >
                                Close
                            </Button>
                            <Button
                                variant="primary"
                                onClick={submitFeedbackEdit}
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
                                rows={feedbacks}
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

export default FeedbackIndex;
