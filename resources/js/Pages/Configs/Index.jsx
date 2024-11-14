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

function Index({ dataConfigs }) {
    const [data, setData] = useState(dataConfigs);
    const [domain, setDomain] = useState("");
    const [policy, setPolicy] = useState("");
    const [term, setTerm] = useState("");
    const [support, setSupport] = useState("");
    const [packageName, setPackageName] = useState("");
    const [show, setShow] = useState(false);

    const formatCreatedAt = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleString();
    };

    const columns = [
        { field: "id", headerName: "#", width: 100 },
        { field: "domain", headerName: "Domain ", width: 200, editable: true },
        { field: "package_name", headerName: "Package Name", width: 200, editable: true },
        { field: "policy", headerName: "Policy", width: 200, editable: true },
        { field: "term", headerName: "Term", width: 200, editable: true },
        { field: "support", headerName: "Support", width: 200, editable: true },
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
                        axios.put(`/configs/${params.row.id}`, {
                            status: checked,
                        }).then((res) => {
                            if (res.data.check == true) {
                    toast.success("Đã chỉnh sửa thành công");

                                setData(res.data.data);
                            }
                        })
                    }}
                />
            ),
            editable: true,
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
        formData.append("domain", domain);
        formData.append("package_name", packageName);
        formData.append("policy", policy);
        formData.append("term", term);
        formData.append("support", support);
        axios
            .post("/configs", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Đã thêm thành công");

                    setData(res.data.data);

                    setPackageName("");
                    setDomain("");
                    setShow(false);
                } else {
                    toast.error(res.data.msg);
                }
            })
            .catch((err) => {
                toast.error("Có lỗi xảy ra. Vui lòng thử lại.");
            });
    };

    const handleEdit = (id, field, value) => {
        if (field === "domain" && value === "") {
            Swal.fire({
                icon: "warning",
                text: "Bạn muốn config này?",
                showCancelButton: true,
                confirmButtonText: "Có",
                cancelButtonText: "Không",
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/configs/${id}`).then((res) => {
                        if (res.data.check) {
                            toast.success("Xóa thành công");

                            setData(res.data.data)
                        } else {
                            toast.error(res.data.msg);
                        }
                    });
                }
            });
        } else {
            axios.put(`/configs/${id}`, { [field]: value }).then((res) => {
                if (res.data.check) {
                    toast.success("Chỉnh sửa thành công");
                    setData(res.data.data)

                } else {
                    toast.error(res.data.msg);
                }
            });
        }
    };

    return (
        <Layout>
            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Tạo config</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <input
                        type="text"
                        className="form-control"
                        placeholder="Nhập Domain..."
                        value={domain}
                        onChange={(e) => setDomain(e.target.value)}
                    />
                    <input
                        type="text"
                        className="form-control"
                        placeholder="Nhập policy..."
                        value={domain}
                        onChange={(e) => setPolicy(e.target.value)}
                    />
                    <input
                        type="text"
                        className="form-control"
                        placeholder="Nhập term..."
                        value={domain}
                        onChange={(e) => setTerm(e.target.value)}
                    />
                    <input
                        type="text"
                        className="form-control"
                        placeholder="Nhập support..."
                        value={domain}
                        onChange={(e) => setSupport(e.target.value)}
                    />
                    <textarea
                        className="form-control mt-2"
                        rows={3}
                        placeholder="Nhập Package name..."
                        value={packageName}
                        onChange={(e) => setPackageName(e.target.value)}
                    />
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShow(false)}>
                        Đóng
                    </Button>
                    <Button
                        variant="primary"
                        onClick={handleSubmit}
                        disabled={!packageName || !domain}
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

            {/* ToastContainer for displaying toast notifications */}
            <ToastContainer />
        </Layout>
    );
}

export default Index;
