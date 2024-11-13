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
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { Select, MenuItem, OutlinedInput, InputLabel, FormControl, Checkbox, ListItemText } from '@mui/material';
function Index({ datafeatures, datasizes }) {
    const [image, setImage] = useState(null);
    const [feature, setFeature] = useState("");
    const [description, setDescription] = useState("");
    const [apiEndpoint, setApiEndpoint] = useState("");
    const [data, setData] = useState(datafeatures);
    const [show, setShow] = useState(false);
    const [showImageModal, setShowImageModal] = useState(false);
    const [selectedRowId, setSelectedRowId] = useState(null);
    const [modelId, setModelId] = useState("");
    const [prompt, setPrompt] = useState("");
    const [presetStyle, setPresetStyle] = useState("");
    const [initImageId, setInitImageId] = useState("");
    const [preprocessorId, setPreprocessorId] = useState("");
    const [strengthType, setStrengthType] = useState("");
    const handleClose = () => setShow(false);
    const handleShow = () => setShow(true);
    const closeImageModal = () => setShowImageModal(false);
    const handleSizeChange = (e) => {
        const selectedValues = e.target.value;  // This will be an array of selected sizes
        setSizes(selectedValues);
      };
    const [sizes,setSizes]= useState([]);
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
        { field: "slug", headerName: "Slug", width: 200 },
        {
            field: "model_id",
            headerName: "Model ID",
            width: 200,
            editable: true,
        },
        { field: "prompt", headerName: "Prompt", width: 200, editable: true },
        {
            field: "presetStyle",
            headerName: "Preset Style",
            width: 200,
            editable: true,
        },
        {
            field: "initImageId",
            headerName: "Init Image ID",
            width: 200,
            editable: true,
        },
        {
            field: "preprocessorId",
            headerName: "Preprocessor ID",
            width: 200,
            editable: true,
        },
        {
            field: "strengthType",
            headerName: "Strength Type",
            width: 200,
            editable: true,
        }
        ,
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
                        axios.put(`/features/${params.row.id}`, {
                            status: checked,
                        }).then((res) => {
                            if (res.data.check == true) {
                                toast.success("Đã sửa thành công !", {
                                    position: "top-right"
                                });
                                setData(res.data.data);
                            }
                        })
                    }}
                />
            ),
            editable: true,
        },
        {
            field: "remove_bg",
            headerName: "Remove Background",
            width: 200,
            renderCell: (params) => (
                <input
                    key={params.row.id}
                    type="checkbox"
                    className="text-center"
                    checked={params.value}
                    onChange={(event) => {
                        const checked = event.target.checked;
                        axios.put(`/features/${params.row.id}`, {
                            remove_bg: checked,
                        }).then((res) => {
                            if (res.data.check == true) {
                                toast.success("Đã sửa thành công !", {
                                    position: "top-right"
                                });
                                setData(res.data.data);
                            }
                        })
                    }}
                />
            ),
            editable: true,
        },{
            field: "is_pro",
            headerName: "Pro Feature",
            width: 200,
            renderCell: (params) => (
                <input
                    key={params.row.id}
                    type="checkbox"
                    className="text-center"
                    checked={params.value}
                    onChange={(event) => {
                        const checked = event.target.checked;
                        axios.put(`/features/${params.row.id}`, {
                            is_pro: checked,
                        }).then((res) => {
                            if (res.data.check == true) {
                                toast.success("Đã sửa thành công !", {
                                    position: "top-right"
                                });
                                setData(res.data.data);
                            }
                        })
                    }}
                />
            ),
            editable: true,

        }
        , {
            field: "detech_face",
            headerName: "Detech Face",
            width: 200,
            renderCell: (params) => (
                <input
                    key={params.row.id}
                    type="checkbox"
                    className="text-center"
                    checked={params.value}
                    onChange={(event) => {
                        const checked = event.target.checked;
                        axios.put(`/features/${params.row.id}`, {
                            detech_face: checked,
                        }).then((res) => {
                            if (res.data.check == true) {
                                toast.success("Đã sửa thành công !", {
                                    position: "top-right"
                                });
                                setData(res.data.data);
                            }
                        })
                    }}
                />
            ),
            editable: true,
        },
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
                    onClick={() => openImageModal(params.row.id)}
                />
            ),
        },
        {
            field: "sizes",
            headerName: "Sizes",
            width: 200,
            renderCell: (params) => {
                // Convert the sizes from params.row.sizes to an array of selected sizes
                const selectedSizes = params.row.sizes ? params.row.sizes.map(size => size.id) : [];

                return (
                    <Select
                        value={selectedSizes}  // Set the value to the selected sizes array
                        onChange={handleSizeChange}
                        defaultValue={params.row.sizes ? params.row.sizes.map(size => size.id) : []}
                        onBlur={() => {
                            const featureId = params.row.id;
                            var formData = new FormData();
                            sizes.forEach(size => {
                                formData.append('size_id[]', size);
                            });

                            axios.post(`/updated_size/${featureId}`, formData)
                                .then((res) => {
                                    if (res.data.check) {
                                        toast.success("Sizes updated successfully!", {
                                            position: "top-right"
                                        });
                                        setData(res.data.data);
                                    } else {
                                        toast.error(res.data.msg, {
                                            position: "top-right"
                                        });
                                    }
                                })
                                .catch((error) => {
                                    console.error("There was an error updating sizes:", error);
                                    toast.error("Failed to update sizes.", {
                                        position: "top-right"
                                    });
                                });
                        }}
                        multiple={true}
                        fullWidth
                    >
                        {datasizes.map((size) => (
                            <MenuItem key={size.id} value={size.id}>
                                {size.size}
                            </MenuItem>
                        ))}
                    </Select>
                );
            },
            editable: true,
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

        formData.append("model_id", modelId);
        formData.append("prompt", prompt);
        formData.append("presetStyle", presetStyle);
        formData.append("initImageId", initImageId);
        formData.append("preprocessorId", preprocessorId);
        formData.append("strengthType", strengthType);

        axios
            .post("/features", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Đã thêm thành công !", {
                        position: "top-right"
                    });

                    setData((prevData) => [...prevData, res.data.data]);

                    resetCreate();
                    setShow(false);
                } else {
                    toast.error(res.data.msg, {
                        position: "top-right"
                    });
                }
            })
            .catch((err) => {
                notyf.error("Có lỗi xảy ra. Vui lòng thử lại.");
            });
    };

    const resetCreate = () => {
        setFeature("");
        setDescription("");
        setApiEndpoint("");
        setModelId("");
        setPrompt("");
        setPresetStyle("");
        setInitImageId("");
        setPreprocessorId("");
        setStrengthType("");
        setImage(null);
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
                            toast.success("Đã xóa thành công !", {
                                position: "top-right"
                            });
                            setData((prevData) =>
                                prevData.filter((item) => item.id !== id)
                            );
                        } else {
                            toast.error(res.data.msg, {
                                position: "top-right"
                            });
                        }
                    });
                }
            });
        } else {
            axios.put(`/features/${id}`, { [field]: value }).then((res) => {
                if (res.data.check) {
                    toast.success("Chỉnh sửa thành công !", {
                        position: "top-right"
                    });
                    setData(res.data.data);
                } else {
                    toast.error(res.data.msg, {
                        position: "top-right"
                    });
                }
            });
        }
    };

    const openImageModal = (id) => {
        setSelectedRowId(id);
        setShowImageModal(true);
    };

    const updateImage = () => {
        const formData = new FormData();
        formData.append("image", image);

        axios
            .post(`/feature-update-image/${selectedRowId}`, formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Ảnh đã được cập nhật thành công !", {
                        position: "top-right"
                    });
                    setData(res.data.data);
                    closeImageModal();
                } else {
                    toast.error(res.data.msg, {
                        position: "top-right"
                    });
                }
            });
    };

    return (
        <Layout>
            <ToastContainer />

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

                {/* Existing modal and data grid setup */}
                <Modal show={show} onHide={handleClose}>
                    <Modal.Header closeButton>
                        <Modal.Title>Tạo Feature</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <input
                            type="text"
                            className="form-control"
                            placeholder="Hãy nhập feature . . . "
                            value={feature}
                            onChange={(e) => setFeature(e.target.value)}
                        />
                        <textarea
                            className="form-control mt-2"
                            rows={3}
                            placeholder="Hãy nhập mô tả cho feature . . ."
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
                            onChange={(e) => setImage(e.target.files[0])}
                        />

                        {/* New fields for the additional columns */}
                        <input
                            type="text"
                            className="form-control mt-2"
                            placeholder="Nhập Model ID . . ."
                            value={modelId}
                            onChange={(e) => setModelId(e.target.value)}
                        />
                        <input
                            type="text"
                            className="form-control mt-2"
                            placeholder="Nhập Prompt . . ."
                            value={prompt}
                            onChange={(e) => setPrompt(e.target.value)}
                        />
                        <input
                            type="text"
                            className="form-control mt-2"
                            placeholder="Nhập Preset Style . . ."
                            value={presetStyle}
                            onChange={(e) => setPresetStyle(e.target.value)}
                        />
                        <input
                            type="text"
                            className="form-control mt-2"
                            placeholder="Nhập Init Image ID . . ."
                            value={initImageId}
                            onChange={(e) => setInitImageId(e.target.value)}
                        />
                        <input
                            type="text"
                            className="form-control mt-2"
                            placeholder="Nhập Preprocessor ID . . ."
                            value={preprocessorId}
                            onChange={(e) => setPreprocessorId(e.target.value)}
                        />
                        <input
                            type="text"
                            className="form-control mt-2"
                            placeholder="Nhập Strength Type . . ."
                            value={strengthType}
                            onChange={(e) => setStrengthType(e.target.value)}
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

                {/* Navbar */}
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

                {/* Data Grid */}
                <div className="row">
                    <div className="col mx-auto">
                        {data && data.length > 0 && (
                            <div className="card border-0 shadow">
                                <div className="card-body">
                                    <Box sx={{ width: '100%', height: '80vh', overflowX: 'auto', overflowY: 'hidden' }}>
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
