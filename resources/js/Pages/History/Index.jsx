import React, { useEffect, useState } from "react";
import Layout from "../../Components/Layout";
import { ToastContainer, toast } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import axios from "axios";
import { Box } from "@mui/material";
import { DataGrid } from "@mui/x-data-grid";
import Swal from "sweetalert2";

function HistoryIndex({ data }) {
    const [historyData, setHistoryData] = useState(data);

    // States for editing customerId and imageResult
    const [editCustomerId, setEditCustomerId] = useState("");
    const [editImageResult, setEditImageResult] = useState("");
    const [editHistoryId, setEditHistoryId] = useState(null);
    const [showEditModal, setShowEditModal] = useState(false);

    const showAlert = (status, msg) => {
        if (status === "error") {
            toast.error(msg, { position: "top-right" });
        } else {
            toast.success(msg, { position: "top-right" });
        }
    };
    const formatCreatedAt = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleString();
    };

    const columns = [
        { field: "id", headerName: "#", width: 100 },
        {
            field: "customer_id",
            headerName: "Customer ID",
            width: 200,
            editable: true,
        },
        {
            field: "image_result",
            headerName: "Image Result",
            width: 1000,
            editable: false,
            renderCell: (params) => {
                return (
                    <a
                        href={params.value}
                        target="_blank"
                        rel="noopener noreferrer"
                        style={{ color: "blue", textDecoration: "underline" }}
                    >
                        {params.value}
                    </a>
                );
            },
        },
        {   
            field: "created_at",
            headerName: "Created at",
            width: 200,
            valueGetter: (params) => formatCreatedAt(params),
        },
    ];

    const handleEdit = (id, customerId, imageResult) => {
        setEditCustomerId(customerId);
        setEditImageResult(imageResult);
        setEditHistoryId(id);
        setShowEditModal(true);
    };

    const submitHistoryEdit = () => {
        if (!editCustomerId || !editImageResult) {
            showAlert("error", "Customer ID and Image Result are required!");
        } else {
            axios
                .put(`/history/${editHistoryId}`, {
                    customer_id: editCustomerId,
                    image_result: editImageResult,
                })
                .then((res) => {
                    if (res.data.check) {
                        toast.success("History updated successfully!", {
                            position: "top-right",
                        });
                        setHistoryData(res.data.data);
                        setShowEditModal(false);
                    } else {
                        toast.error("Failed to update history.", {
                            position: "top-right",
                        });
                    }
                })
                .catch((error) => {
                    toast.error("An error occurred. Please try again.", {
                        position: "top-right",
                    });
                    console.error(error);
                });
        }
    };

    const handleCellEditStop = (id, field, value) => {
        if (field === "customer_id" || field === "image_result") {
            if (value === "") {
                Swal.fire({
                    icon: "question",
                    text: "Bạn muốn xóa lịch sử này?",
                    showDenyButton: true,
                    confirmButtonText: "Đúng",
                    denyButtonText: "Không",
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.delete(`/history/${id}`).then((res) => {
                            if (res.data.check) {
                                toast.success("Lịch sử đã xóa thành công!", {
                                    position: "top-right",
                                });
                                setHistoryData(res.data.data);
                            } else {
                                toast.error(res.data.msg, {
                                    position: "top-right",
                                });
                            }
                        });
                    }
                });
            } else {
                axios.put(`/history/${id}`, { [field]: value }).then((res) => {
                    if (res.data.check) {
                        toast.success("Chỉnh sửa thành công!", {
                            position: "top-right",
                        });
                        setHistoryData(res.data.data);
                    } else {
                        toast.error(res.data.msg, { position: "top-right" });
                    }
                });
            }
        }
    };

    return (
        <Layout>
            <ToastContainer />
            <div className="row">
                <div className="col-md">
                    {/* Removed Create History Button */}
                </div>

                {/* Removed Modal for creating history */}
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
                            rows={historyData}
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
        </Layout>
    );
}

export default HistoryIndex;
