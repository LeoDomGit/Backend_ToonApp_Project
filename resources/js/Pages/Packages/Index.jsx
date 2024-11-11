import React, { useState } from "react";
import Layout from "../../Components/Layout";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { Notyf } from "notyf";
import { Box } from "@mui/material";
import { DataGrid } from "@mui/x-data-grid";
import "notyf/notyf.min.css";
import axios from "axios";
import Swal from "sweetalert2";
import JoditEditor from "jodit-react"; // Import JoditEditor

function Index({ subcriptionPackage }) {
    const [data, setData] = useState(subcriptionPackage);
    const [name, setName] = useState(""); // Ensure initial value is an empty string
    const [price, setPrice] = useState(""); // Initialize price with an empty string or a default value
    const [description, setDescription] = useState(""); // Initialize description with an empty string
    const [show, setShow] = useState(false);

    const notyf = new Notyf({
        duration: 2000,
        position: { x: "right", y: "top" },
        types: [
            { type: "success", background: "green" },
            { type: "error", background: "indianred" },
        ],
    });

    const formatCreatedAt = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleString();
    };

    const columns = [
        { field: "id", headerName: "#", width: 100 },
        { field: "name", headerName: "Name", width: 200, editable: true },
        { field: "price", headerName: "Price", width: 200, editable: true },
        {
            field: "description",
            headerName: "Description",
            width: 250,
            editable: true,
        },
        {
            field: "created_at",
            headerName: "Created at",
            width: 200,
            valueGetter: (params) => formatCreatedAt(params),
        },
        {
            field: "upload_at",
            headerName: "Upload at",
            width: 200,
            valueGetter: (params) => formatCreatedAt(params),
        },
    ];

    // Hàm xử lý khi gửi form
    const handleSubmit = async () => {
        // Kiểm tra xem các trường có bị trống không
        if (!name || !price || !description) {
            notyf.error("Please fill out all the fields.");
            return; // Nếu có trường trống thì không gửi
        }

        const formData = new FormData();
        formData.append("name", name);
        formData.append("price", price);
        formData.append("description", description); // Gửi description lên server

        try {
            const res = await axios.post("/packages", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            if (res.data.check) {
                notyf.success("Package added successfully.");

                // Update data state with the newly created package
                setData((prevData) => [...prevData, res.data.data]);

                // Reset the form and close the modal
                setName("");
                setPrice("");
                setDescription("");
                setShow(false);
            } else {
                notyf.error(res.data.msg);
            }
        } catch (err) {
            notyf.error("An error occurred. Please try again.");
        }
    };

    const handleEdit = (id, field, value) => {
        if (field === "name" && value === "") {
            // Hiển thị hộp thoại xác nhận nếu trường bị trống và cần xóa
            Swal.fire({
                icon: "warning",
                text: "Do you want to delete this feature?",
                showCancelButton: true,
                confirmButtonText: "Có",
                cancelButtonText: "Không",
            }).then((result) => {
                if (result.isConfirmed) {
                    // Gửi request xóa nếu xác nhận
                    axios.delete(`/packages/${id}`).then((res) => {
                        if (res.data.check) {
                            notyf.success("Package deleted successfully.");

                            // Remove the deleted item from the state
                            setData((prev) =>
                                prev.filter((item) => item.id !== id)
                            );
                        } else {
                            notyf.error(res.data.msg);
                        }
                    });
                }
            });
        } else {
            // Gửi request cập nhật nếu trường không bị trống
            axios.put(`/packages/${id}`, { [field]: value }).then((res) => {
                if (res.data.check) {
                    notyf.success("Package updated successfully.");

                    // Update the specific field in the state
                    setData((prev) =>
                        prev.map((item) =>
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
            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Tạo gói mới</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <input
                        type="text"
                        className="form-control"
                        placeholder="Enter name..."
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                    />
                    <input
                        type="number"
                        className="form-control mt-2"
                        placeholder="Enter price..."
                        value={price}
                        onChange={(e) => setPrice(e.target.value)}
                    />
                    <JoditEditor
                        value={description}
                        onChange={(newDescription) =>
                            setDescription(newDescription)
                        }
                    />
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShow(false)}>
                        Đóng
                    </Button>
                    <Button
                        variant="primary"
                        onClick={handleSubmit}
                        disabled={!name || !price || !description}
                    >
                        Tạo
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
        </Layout>
    );
}

export default Index;
