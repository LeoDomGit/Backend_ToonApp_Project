import React, { useEffect, useState } from "react";
import Layout from "../../Components/Layout";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { Notyf } from "notyf";
import { Box, MenuItem, Select, Typography } from "@mui/material";
import { DataGrid } from "@mui/x-data-grid";
import "notyf/notyf.min.css";
import axios from "axios";
import Swal from "sweetalert2";
function SubFeatures({ dataSubFeatures, dataFeatures }) {
    const [image, setImage] = useState(null);
    const [imageMap, setImageMap] = useState({});
    const [feature, setFeature] = useState("");
    const [description, setDescription] = useState("");
    const [featureId, setFeatureId] = useState(0);
    const [data, setData] = useState(dataSubFeatures);
    const [features, setFeatures] = useState(dataFeatures);
    const [show, setShow] = useState(false);
    const [showImageModal, setShowImageModal] = useState(false);
    const [selectedRowId, setSelectedRowId] = useState(null);
    const closeImageModal = () => setShowImageModal(false);

    const handleClose = () => setShow(false);
    const handleShow = () => setShow(true);
    const api = "http://localhost:8000/api/";
    const app = "http://localhost:8000/";
    const formatCreatedAt = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleString();
    };
    const handleParentChange1 = (id, value) => {
        if (value != null) {
            // Find the selected feature based on the ID
            const selectedFeature = features.find(
                (feature) => feature.id === value
            );

            axios
                .put(`/sub_feature/${id}`, { feature_id: value })
                .then((res) => {
                    if (res.data.check === true) {
                        notyf.open({
                            type: "success",
                            message: "Feature updated successfully",
                        });
                        setData(res.data.data); // Update the data state with the new data

                        // Update imageMap to reflect the new image for the selected feature
                        setImageMap((prevState) => ({
                            ...prevState,
                            [id]: selectedFeature
                                ? selectedFeature.image
                                : null,
                        }));
                    } else {
                        notyf.open({
                            type: "error",
                            message: res.data.msg,
                        });
                    }
                })
                .catch((error) => {
                    notyf.open({
                        type: "error",
                        message:
                            "An error occurred while updating the feature.",
                    });
                });
        } else {
            notyf.open({
                type: "warning",
                message: "Feature ID cannot be 0.",
            });
        }
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
    const openImageModal = (id) => {
        setSelectedRowId(id);
        setShowImageModal(true);
    };
    const columns = [
        {
            field: "id",
            headerName: "#",
            width: 100,
            renderCell: (params) => params.rowIndex,
        },
        { field: "name", headerName: "Features", width: 200, editable: true },
        {
            field: "description",
            headerName: "Description",
            width: 200,
            editable: true,
        },
        {
            field: "api_endpoint",
            headerName: "API Endpoint",
            width: 250,
            editable: true,
        },
        { field: "slug", headerName: "Slug", width: 200},
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
                        axios.put(`/sub_feature/${params.row.id}`, {
                            remove_bg: checked,
                        }).then((res)=>{
                            if(res.data.check==true){
                                setData(res.data.data); 
                            }
                        })
                    }}
                />
            ),
            editable: true,
        },
        {
            field: "feature_id",
            headerName: "Nhóm feature",
            width: 200,
            renderCell: (params) => (
                <Select
                    defaultValue={params.value}
                    className="w-100"
                    onChange={(e) => {
                        const selectedFeatureId = e.target.value;
                        handleParentChange1(params.id, selectedFeatureId);
                        // Optionally, you can force a refresh of the row or grid here
                    }}
                >
                    <MenuItem value={null}>None</MenuItem>
                    {features.map((parent) => (
                        <MenuItem key={parent.id} value={parent.id}>
                            {parent.name}
                        </MenuItem>
                    ))}
                </Select>
            ),
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
    const submitSubFeature = () => {
        const formData = new FormData();
        formData.append("name", feature);
        formData.append("description", description);
        formData.append("description", description);
        formData.append("feature_id", featureId);
        if (image) {
            formData.append("image", image);
        }
        axios
            .post("/sub_feature", formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            })
            .then((res) => {
                if (res.data.check == true) {
                    notyf.open({
                        type: "success",
                        message: "Đã thêm thành công",
                    });
                    setData(res.data.data);
                    resetCreate();
                    setShow(false);
                } else if (res.data.check == true) {
                    notyf.open({
                        type: "success",
                        message: res.data.msg,
                    });
                } else if (res.data.check == false) {
                    if (res.data.msg) {
                        notyf.open({
                            type: "error",
                            message: res.data.msg,
                        });
                    }
                }
            });
    };
    const resetCreate = () => {
        setFeature("");
        setDescription("");
        setShow(true);
    };
    const updateImage = () => {
        const formData = new FormData();
        formData.append("image", image);

        axios
            .post(`/sub-feature-update-image/${selectedRowId}`, formData, {
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
    const handleCellEditStop = (id, field, value) => {
        if (field == "name") {
            if (value == "") {
                Swal.fire({
                    icon: "question",
                    text: "Bạn muốn xóa feature này ?",
                    showDenyButton: true,
                    showCancelButton: false,
                    confirmButtonText: "Đúng",
                    denyButtonText: `Không`,
                }).then((result) => {
                    /* Read more about isConfirmed, isDenied below */
                    if (result.isConfirmed) {
                        axios.delete("/sub_feature/" + id).then((res) => {
                            if (res.data.check == true) {
                                notyf.success("Đã xóa thành công");
                                setData(res.data.data);
                            }
                        });
                    } else if (result.isDenied) {
                    }
                });
            } else {
                axios
                    .put(
                        `/sub_feature/${id}`,
                        {
                            name: value,
                        }
                        // {
                        //     headers: {
                        //         Authorization: `Bearer ${localStorage.getItem("token")}`,
                        //         Accept: "application/json",
                        //     },
                        // }
                    )
                    .then((res) => {
                        if (res.data.check == true) {
                            notyf.open({
                                type: "success",
                                message: "Chỉnh sửa loại tài khoản thành công",
                            });
                            setData(res.data.data);
                        } else if (res.data.check == false) {
                            notyf.open({
                                type: "error",
                                message: res.data.msg,
                            });
                        }
                    });
            }
        } else {
            axios
                .put(
                    `/sub_feature/${id}`,
                    {
                        [field]: value,
                    }
                    // {
                    //     headers: {
                    //         Authorization: `Bearer ${localStorage.getItem("token")}`,
                    //         Accept: "application/json",
                    //     },
                    // }
                )
                .then((res) => {
                    if (res.data.check == true) {
                        notyf.open({
                            type: "success",
                            message: "Chỉnh sửa loại tài khoản thành công",
                        });
                        setData(res.data.data);
                    } else if (res.data.check == false) {
                        notyf.open({
                            type: "error",
                            message: res.data.msg,
                        });
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
                        <Modal.Title>Tạo Sub Feature</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <input
                            type="text"
                            className="form-control"
                            placeholder={
                                feature == ""
                                    ? "Hãy nhập sub features . . . "
                                    : ""
                            }
                            onChange={(e) => setFeature(e.target.value)}
                        />
                        <select
                            name=""
                            defaultValue={featureId}
                            onChange={(e) => setFeatureId(e.target.value)}
                            className="form-control mt-2"
                            id=""
                        >
                            <option value={0} disabled>
                                Chọn Feature phụ thuộc
                            </option>
                            {features.length > 0 &&
                                features.map((item, index) => (
                                    <option value={item.id}>{item.name}</option>
                                ))}
                        </select>
                        <textarea
                            name=""
                            className="form-control mt-2"
                            rows={10}
                            onChange={(e) => setDescription(e.target.value)}
                            value={description}
                            id=""
                        ></textarea>
                         <input
                            type="file"
                            className="form-control mt-2"
                            accept="image/*"
                            onChange={(e) => setImage(e.target.files[0])}
                        />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={handleClose}>
                            Đóng
                        </Button>
                        <Button
                            variant="primary text-light"
                            disabled={feature == "" ? true : false}
                            onClick={(e) => submitSubFeature()}
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
                                onClick={(e) => resetCreate()}
                                aria-current="page"
                                href="#"
                            >
                                Tạo mới
                            </a>
                        </div>
                    </div>
                </nav>
                <div className="row">
                    <div className="col-md-9">
                        {data && data.length > 0 && (
                            <div class="card border-0 shadow">
                                <div class="card-body">
                                    <Box sx={{ height: 400, width: "100%" }}>
                                        <DataGrid
                                            rows={data}
                                            columns={columns}
                                            initialState={{
                                                pagination: {
                                                    paginationModel: {
                                                        pageSize: 5,
                                                    },
                                                },
                                            }}
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

export default SubFeatures;
