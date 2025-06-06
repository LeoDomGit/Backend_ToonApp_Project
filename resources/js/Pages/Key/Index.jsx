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

    function Index({ datakeys }) {
        const [data, setData] = useState(datakeys);
        const [api, setApi] = useState("");
        const [key, setKey] = useState("");
        const [show, setShow] = useState(false);
        const [gmail, setGmail] = useState("");
        const formatCreatedAt = (dateString) => {
            const date = new Date(dateString);
            return date.toLocaleString();
        };

        const columns = [
             {
        field: "rowIndex",
        headerName: "#",
        width: 100,
        valueGetter: (params) => params.api.getRowIndex(params.id) + 1,
    },
            { field: "api", headerName: "Api", width: 200, editable: true },
            { field: "key", headerName: "Key", width: 200, editable: true },
            { field: "gmail", headerName: "Gmail", width: 250, editable: true },
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
            formData.append("api", api);
            formData.append("key", key);
            formData.append("gmail", gmail);

            axios
                .post("/keys", formData, {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                })
                .then((res) => {
                    if (res.data.check) {
                        toast.success("Đã thêm thành công");

                        setData((prevData) => [...prevData, res.data.data]);

                        setApi("");
                        setKey("");
                        setGmail("");
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
            if (field === "api" && value === "") {
                Swal.fire({
                    icon: "warning",
                    text: "Bạn muốn xóa feature này?",
                    showCancelButton: true,
                    confirmButtonText: "Có",
                    cancelButtonText: "Không",
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.delete(`/keys/${id}`).then((res) => {
                            if (res.data.check) {
                                toast.success("Xóa thành công");

                                setData((prev) =>
                                    prev.filter((item) => item.id !== id)
                                );
                            } else {
                                toast.error(res.data.msg);
                            }
                        });
                    }
                });
            } else {
                axios.put(`/keys/${id}`, { [field]: value }).then((res) => {
                    if (res.data.check) {
                        toast.success("Chỉnh sửa thành công");
                        setData((prevData) =>
                            prevData.map((item) =>
                                item.id === id ? { ...item, [field]: value } : item
                            )
                        );
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
                        <Modal.Title>Tạo Key</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <input
                            type="text"
                            className="form-control"
                            placeholder="Nhập API..."
                            value={api}
                            onChange={(e) => setApi(e.target.value)}
                        />
                        <textarea
                            className="form-control mt-2"
                            rows={3}
                            placeholder="Nhập key..."
                            value={key}
                            onChange={(e) => setKey(e.target.value)}
                        />
                        <input
                            type="email"
                            className="form-control mt-2"
                            placeholder="Nhập Gmail..."
                            value={gmail}
                            onChange={(e) => setGmail(e.target.value)}
                        />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={() => setShow(false)}>
                            Đóng
                        </Button>
                        <Button
                            variant="primary"
                            onClick={handleSubmit}
                            disabled={!api || !key}
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
                    <div className="col-md-11">
                        <div className="card border-0 shadow">
                            <div className="card-body">
                                <Box sx={{ height: 400, width: "100%" }}>
                                    <DataGrid
                                        rows={data}
                                        columns={columns}
                                        pageSizeOptions={[5]}
                                        checkboxSelection
                                        initialState={{
                                            pagination: {
                                              paginationModel: {
                                                pageSize: 5,
                                              },
                                            },
                                          }}
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
