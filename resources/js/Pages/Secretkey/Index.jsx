import React, { useState } from "react";
import Layout from "../../Components/Layout";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { toast, ToastContainer } from "react-toastify";
import { Box } from "@mui/material";
import { DataGrid } from "@mui/x-data-grid";
import "react-toastify/dist/ReactToastify.css";
import axios from "axios";
import Swal from "sweetalert2";

function Index({ datasecretkeys }) {
    const [data, setData] = useState(datasecretkeys);
    const [apiKey, setApiKey] = useState(""); // API Key
    const [secretKey, setSecretKey] = useState(""); // Secret Key
    const [isActive, setIsActive] = useState(true); // Active status
    const [show, setShow] = useState(false); // Modal visibility

    const formatCreatedAt = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleString();
    };

    const columns = [
        { field: "id", headerName: "#", width: 100 },
        { field: "api_key", headerName: "API Key", width: 200, editable: true },
        {
            field: "secret_key",
            headerName: "Secret Key",
            width: 200,
            editable: true,
        },
        {
            field: "is_active",
            headerName: "Is Active",
            width: 150,
            editable: true,
            type: "boolean",
        },
        {
            field: "created_at",
            headerName: "Created at",
            width: 200,
            valueGetter: (params) => formatCreatedAt(params),
        },
        {
            field: "updated_at",
            headerName: "Updated at",
            width: 200,
            valueGetter: (params) => formatCreatedAt(params),
        },
    ];

    const handleSubmit = () => {
        const formData = new FormData();
        formData.append("api_key", apiKey);
        formData.append("secret_key", secretKey);
        formData.append("is_active", isActive ? 1 : 0);

        axios
            .post("/secretkeys", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Đã thêm thành công");

                    // Ensure that the previous data is an array before updating it
                    setData((prevData) =>
                        Array.isArray(prevData)
                            ? [...prevData, res.data.data]
                            : [res.data.data]
                    );

                    // Reset fields
                    setApiKey("");
                    setSecretKey("");
                    setIsActive(true);
                    setShow(false);
                } else {
                    toast.error(res.data.msg);
                }
            })
            .catch((err) => {
                toast.error("Có lỗi xảy ra. Vui lòng thử lại.");
                console.error(err); // Log error to console for debugging
            });
    };

    const handleEdit = (id, field, value) => {
        if (field === "api_key" && value === "") {
            // Confirm delete if the API key is empty
            Swal.fire({
                icon: "warning",
                text: "Bạn muốn xóa feature này?",
                showCancelButton: true,
                confirmButtonText: "Có",
                cancelButtonText: "Không",
            }).then((result) => {
                if (result.isConfirmed) {
                    // Axios delete request
                    axios
                        .delete(`/secretkeys/${id}`)
                        .then((res) => {
                            if (res.data.check) {
                                toast.success("Xóa thành công");

                                // Remove the deleted row from the state immediately
                                setData(res.data.data);
                            } else {
                                toast.error(res.data.msg);
                            }
                        })
                        .catch((err) => {
                            toast.error("Có lỗi xảy ra khi xóa.");
                            console.error(err); // Log the error for debugging
                        });
                }
            });
        } else if (field === "is_active") {
            // Convert 'on' to 1 and 'off' to 0
            const statusValue = !value;

            // Send the updated status to the server
            axios
                .put(`/secretkeys/${id}`, { [field]: statusValue })
                .then((res) => {
                    if (res.data.check) {
                        toast.success("Chỉnh sửa thành công");

                        // Update the specific field in the state with the correct value
                        setData((prevData) =>
                            prevData.map((item) =>
                                item.id === id
                                    ? { ...item, [field]: statusValue }
                                    : item
                            )
                        );
                    } else {
                        toast.error(res.data.msg);
                    }
                })
                .catch((err) => {
                    toast.error("Có lỗi xảy ra khi cập nhật.");
                    console.error(err); // Log the error for debugging
                });
        } else {
            axios
                .put(`/secretkeys/${id}`, { [field]: value })
                .then((res) => {
                    if (res.data.check) {
                        toast.success("Chỉnh sửa thành công");

                        // Update the specific field in the state
                        setData((prevData) =>
                            prevData.map((item) =>
                                item.id === id
                                    ? { ...item, [field]: value }
                                    : item
                            )
                        );
                    } else {
                        toast.error(res.data.msg);
                    }
                })
                .catch((err) => {
                    toast.error("Có lỗi xảy ra khi cập nhật.");
                    console.error(err); // Log the error for debugging
                });
        }
    };

    return (
        <Layout>
            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Tạo Secret Key</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <input
                        type="text"
                        className="form-control"
                        placeholder="Nhập API Key..."
                        value={apiKey}
                        onChange={(e) => setApiKey(e.target.value)}
                    />
                    <textarea
                        className="form-control mt-2"
                        rows={3}
                        placeholder="Nhập Secret Key..."
                        value={secretKey}
                        onChange={(e) => setSecretKey(e.target.value)}
                    />
                    <div className="form-check mt-2">
                        <input
                            type="checkbox"
                            className="form-check-input"
                            checked={isActive}
                            onChange={() => setIsActive(!isActive)}
                        />
                        <label className="form-check-label">Kích hoạt</label>
                    </div>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShow(false)}>
                        Đóng
                    </Button>
                    <Button
                        variant="primary"
                        onClick={handleSubmit}
                        disabled={!apiKey || !secretKey}
                    >
                        Tạo mới
                    </Button>
                </Modal.Footer>
            </Modal>

            <nav className="navbar navbar-expand-lg navbar-light bg-light">
                <div className="container-fluid">
                    <button
                        className="btn btn-primary text-light"
                        onClick={() => setShow(true)}
                    >
                        Tạo mới
                    </button>
                </div>
            </nav>

            <div className="row">
                <div className="col-md-9">
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
                                        handleEdit(
                                            params.row.id,
                                            params.field,
                                            e.target.value
                                        )
                                    }
                                />
                            </Box>
                        </div>
                    </div>
                </div>
            </div>

            <ToastContainer />
        </Layout>
    );
}

export default Index;
