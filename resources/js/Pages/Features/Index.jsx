import React, { useEffect, useState } from "react";
import Layout from "../../Components/Layout";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { Notyf } from "notyf";
import { Box, Typography } from "@mui/material";
import { DataGrid } from "@mui/x-data-grid";
import "notyf/notyf.min.css";
import axios from "axios";
import Swal from "sweetalert2";

function Index({ datafeatures }) {
    const [image, setImage] = useState(null);
    const [feature, setFeature] = useState("");
    const [description, setDescription] = useState("");
    const [apiEndpoint, setApiEndpoint] = useState("");
    const [data, setData] = useState(datafeatures);
    const [show, setShow] = useState(false);
    const handleClose = () => setShow(false);
    const handleShow = () => setShow(true);
    const formatCreatedAt = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleString();
    };
    const notyf = new Notyf({
        duration: 1000,
        position: {
            x: "right",
            y: "top",
        },
        types: [
            {
                type: "warning",
                background: "orange",
                icon: {
                    className: "material-icons",
                    tagName: "i",
                    text: "warning",
                },
            },
            {
                type: "error",
                background: "indianred",
                duration: 2000,
                dismissible: true,
            },
            {
                type: "success",
                background: "green",
                color: "white",
                duration: 2000,
                dismissible: true,
            },
            {
                type: "info",
                background: "#24b3f0",
                color: "white",
                duration: 1500,
                dismissible: false,
                icon: '<i class="bi bi-bag-check"></i>',
            },
        ],
    });

    const columns = [
        { field: "id", headerName: "#", width: 100 },
        { field: "name", headerName: "Features", width: 200, editable: true },
        {
            field: "description",
            headerName: "Description",
            width: 200,
            editable: true,
        },
        {
            field: "image",
            headerName: "Image",
            width: 100,
            renderCell: (params) => (
                <img
                    src={
                        params.value
                            ? `/storage/${params.value}` // Đường dẫn đến hình ảnh đã lưu
                            : "/default-image.jpg" // Hình ảnh mặc định nếu không có
                    }
                    alt="Feature"
                    style={{
                        width: "50px",
                        height: "50px",
                        objectFit: "cover",
                    }}
                />
            ),
        },
        {
            field: "created_at",
            headerName: "Created at",
            width: 200,
            valueGetter: (params) => formatCreatedAt(params),
        },
        {
            field: "api_endpoint",
            headerName: "API Endpoint",
            width: 250,
            editable: true,
        },
    ];

    const submitRole = () => {
        const formData = new FormData();
        formData.append("name", feature);
        formData.append("description", description);
        formData.append("api_endpoint", apiEndpoint);
        if (image) {
            formData.append("image", image);
        }

        axios
            .post("/features", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((res) => {
                if (res.data.check) {
                    notyf.success("Đã thêm thành công");
                    setData((prevData) => [...prevData, res.data.data]); // Thêm dữ liệu mới vào bảng
                    resetCreate();
                    setShow(false);
                } else {
                    notyf.error(res.data.msg);
                }
            });
    };

    const resetCreate = () => {
        setFeature("");
        setDescription("");
        setApiEndpoint("");
        setShow(true);
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
                    axios.delete(`/features/${id}`).then((res) => {
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
            axios.put(`/features/${id}`, { [field]: value }).then((res) => {
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
                <Modal show={show} onHide={handleClose}>
                    <Modal.Header closeButton>
                        <Modal.Title>Tạo loại tài khoản</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <input
                            type="text"
                            className="form-control"
                            placeholder="Hãy nhập features . . . "
                            value={feature}
                            onChange={(e) => setFeature(e.target.value)}
                        />
                        <textarea
                            className="form-control mt-2"
                            rows={3}
                            placeholder="Hãy nhập mô tả . . ."
                            value={description}
                            onChange={(e) => setDescription(e.target.value)}
                        ></textarea>
                        <input
                            type="text"
                            className="form-control mt-2"
                            placeholder="Nhập API Endpoint . . ."
                            value={apiEndpoint}
                            onChange={(e) => setApiEndpoint(e.target.value)}
                        />
                        <input
                            type="file"
                            className="form-control mt-2"
                            accept="image/*"
                            onChange={(e) => setImage(e.target.files[0])} // Cập nhật file ảnh vào state
                        />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={handleClose}>
                            Đóng
                        </Button>
                        <Button
                            variant="primary text-light"
                            disabled={!feature}
                            onClick={submitRole}
                        >
                            Tạo mới
                        </Button>
                    </Modal.Footer>
                </Modal>
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
                        <div
                            className="collapse navbar-collapse"
                            id="navbarSupportedContent"
                        >
                            <a
                                className="btn btn-primary text-light"
                                onClick={resetCreate}
                                aria-current="page"
                                href="#"
                            >
                                Tạo mới
                            </a>
                        </div>
                    </div>
                </nav>
                <div className="row">
                    <div className="col-md-8">
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

export default Index;
