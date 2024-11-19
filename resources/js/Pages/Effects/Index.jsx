import Layout from "../../Components/Layout";
import { Box } from "@mui/material";
import { DataGrid } from "@mui/x-data-grid";
import axios from "axios";
import Modal from "react-bootstrap/Modal";
import Button from "react-bootstrap/Button";
import { useState } from "react";
import { ToastContainer, toast } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import Swal from "sweetalert2";

function Index({ effects }) {
    const [data, setData] = useState(effects);
    const [name, setName] = useState("");
    const [image, setImage] = useState(null);
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showImageModal, setShowImageModal] = useState(false);
    const [selectedRow, setSelectedRow] = useState(null);
    const formatCreatedAt = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleString();
    };
    const resetCreate = () => {
        setName(null);
        setImage(null);
        setShowCreateModal(true);
    };

    const submitCreate = () => {
        const formData = new FormData();
        formData.append("name", name);
        if (image) {
            formData.append("image", image);
        }
        axios
            .post("/effects", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Create effect successfully", {
                        position: "top-right",
                    });
                    setData((prevData) => [...prevData, res.data.data]);
                    setShowCreateModal(false);
                } else {
                    toast.error(res.data.msg, {
                        position: "top-right",
                    });
                }
            });
    };

    const submitImage = () => {
        const formData = new FormData();
        formData.append("image", image);
        axios
            .post(`/effects-update-image/${selectedRow}`, formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Update effect's image successfully", {
                        position: "top-right",
                    });
                    setData(res.data.data);
                    setShowImageModal(false);
                } else {
                    toast.error(res.data.msg, {
                        position: "top-right",
                    });
                }
            });
    };

    const columns = [
        { field: "id", headerName: "ID", flex: 1 },
        { field: "name", headerName: "Name", editable: true, flex: 1 },
        { field: "slug", headerName: "Slug", editable: true, flex: 1 },
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
                            .put(`/effects/${params.row.id}`, {
                                status: checked,
                            })
                            .then((res) => {
                                if (res.data.check == true) {
                                    toast.success(
                                        "Update effect's status successfully",
                                        {
                                            position: "top-right",
                                        }
                                    );
                                    setData(res.data.data);
                                }
                            });
                    }}
                />
            ),
            editable: true,
        },
        {
            field: "image",
            headerName: "Image",
            width: 100,
            renderCell: (params) => (
                <img
                    src={params.value ? params.value : "/default-image.jpg"}
                    alt="Feature"
                    style={{
                        width: "50px",
                        height: "50px",
                        objectFit: "cover",
                        cursor: "pointer",
                    }}
                    onClick={() => {
                        setShowImageModal(true);
                        setSelectedRow(params.row.id);
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
            field: "updated_at",
            headerName: "Updated at",
            width: 200,
            valueGetter: (params) => formatCreatedAt(params),
        },
    ];

    const handleOnCellEditStop = (id, field, value) => {
        if (field === "name" && value == "") {
            Swal.fire({
                icon: "Warning",
                text: "Do you want to delete this effect",
                showDenyButton: true,
                confirmButtonText: "Yes",
                denyButtonText: "No",
            }).then((res) => {
                if (res.isConfirmed) {
                    axios.delete(`/effects/${id}`).then((res) => {
                        if (res.data.check) {
                            toast.success("Delete effect successfully", {
                                position: "top-right",
                            });
                            setData(res.data.data);
                        } else {
                            toast.error(res.data.msg, {
                                position: "top-right",
                            });
                        }
                    });
                }
            });
        } else {
            axios.put(`/effects/${id}`, { [field]: value }).then((res) => {
                if (res.data.check) {
                    toast.success("Update effect successfully", {
                        position: "top-right",
                    });
                    setData(res.data.data);
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
            <ToastContainer />
            <Modal
                show={showCreateModal}
                onHide={() => setShowCreateModal(false)}
            >
                <Modal.Header closeButton>
                    <Modal.Title>Create New Effect</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <input
                        type="text"
                        className="form-control"
                        placeholder="Effect's name"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                    />
                    <input
                        type="file"
                        className="form-control mt-2"
                        placeholder="Upload effect's image"
                        accept="image/*"
                        onChange={(e) => setImage(e.target.files[0])}
                    />
                </Modal.Body>
                <Modal.Footer>
                    <Button
                        variant="secondary"
                        onClick={() => setShowCreateModal(false)}
                    >
                        Close
                    </Button>
                    <Button
                        variant="primary text-light"
                        disabled={!name}
                        onClick={submitCreate}
                    >
                        Create
                    </Button>
                </Modal.Footer>
            </Modal>
            <Modal
                show={showImageModal}
                onHide={() => setShowImageModal(false)}
            >
                <Modal.Header closeButton>
                    <Modal.Title>Update Effect Image</Modal.Title>
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
                            Create New Effect
                        </a>
                    </div>
                </div>
            </nav>

            <div className="row">
                <div className="col mx-auto">
                    {effects && effects.length > 0 && (
                        <div className="card border-0 shadow">
                            <div className="card-body">
                                <Box sx={{ height: 400, width: "100%" }}>
                                    <DataGrid
                                        rows={data}
                                        columns={columns}
                                        pageSizeOptions={[5, 10, 20]} // Define available page sizes
                                        paginationModel={{
                                            pageSize: 5,
                                            page: 0,
                                        }} // Set initial page size and page
                                        pagination // Enable pagination
                                        checkboxSelection
                                        disableRowSelectionOnClick
                                        onCellEditStop={(params, e) =>
                                            handleOnCellEditStop(
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
        </Layout>
    );
}

export default Index;
