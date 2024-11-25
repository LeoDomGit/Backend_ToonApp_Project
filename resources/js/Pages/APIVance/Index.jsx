import React, { useEffect, useState } from "react";
import Layout from "../../Components/Layout";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { ToastContainer, toast } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import axios from "axios";
import { Box } from "@mui/material";
import { DataGrid } from "@mui/x-data-grid";
import Swal from "sweetalert2";
import Checkbox from "@mui/material/Checkbox"; // If you are using Material-UI

function Index({ data }) {
    const [name, setName] = useState("");
    const [module, setModule] = useState("");
    const [entries, setEntries] = useState(data);
    const [modelName, setModelName] = useState("");
    const [prompt, setPrompt] = useState("");
    const [overwrite, setOverwrite] = useState(false);
    const [denoisingStrength, setDenoisingStrength] = useState(null);
    const [imageUid, setImageUid] = useState("");
    const [cnName, setCnName] = useState("");
    const [show, setShow] = useState(false);
    const [apiKey, setApiKey] = useState("");
    const [transId, setTransId] = useState(""); // New state for trans_id

    const handleClose = () => setShow(false);
    const handleShow = () => setShow(true);
    const formatCreatedAt = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleString();
    };
    const resetCreate = () => {
        setModelName("");
        setPrompt("");
        setOverwrite(false);
        setDenoisingStrength(null);
        setImageUid("");
        setCnName("");
        setApiKey(""); // Reset API Key too
        setShow(true);
    };
    const handleOverwriteChange = (event, params) => {
        setOverwrite(event.target.checked ? 1 : 0);
    };

    // Example of updating the `overwrite` field in state
    const updateOverwriteField = (id, newValue) => {
        const updatedEntries = entries.map((entry) => {
            if (entry.id === id) {
                return { ...entry, overwrite: newValue };
            }
            return entry;
        });
        setEntries(updatedEntries);
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
        { field: "apiKey", headerName: "API Key", width: 200, editable: true },
        { field: "name", headerName: "Name", width: 200, editable: true },
        { field: "module", headerName: "Module", width: 200, editable: true },
        {
            field: "model_name",
            headerName: "Model Name",
            width: 200,
            editable: true,
        },
        { field: "prompt", headerName: "Prompt", width: 200, editable: true },
        {
            field: "overwrite",
            headerName: "Overwrite",
            width: 200,
            editable: true,
            renderCell: (params) => {
                return (
                    <Checkbox
                        checked={params.value === 1}
                        onChange={(e) => handleOverwriteChange(e, params)}
                    />
                );
            },
        },
        {
            field: "denoising_strength",
            headerName: "Denoising Strength",
            width: 200,
            editable: true,
        },
        {
            field: "image_uid",
            headerName: "Image UID",
            width: 200,
            editable: true,
        },
        { field: "cn_name", headerName: "CN Name", width: 200, editable: true },
        {
            field: "trans_id", // New column for trans_id
            headerName: "Trans ID",
            width: 200,
            editable: true,
        },

        {
            field: "created_at",
            headerName: "Created at",
            width: 200,
            valueGetter: (params) => formatCreatedAt(params),
        },
    ];
    const submitEntry = () => {
        if (!modelName) {
            showAlert("error", "Model Name is required");
        } else if (!prompt) {
            showAlert("error", "Prompt is required");
        } else if (!apiKey) {
            showAlert("error", "API Key is required");
        } else {
            const overwriteBool = overwrite === 1;

            axios
                .post("/apivances", {
                    model_name: modelName,
                    prompt,
                    apiKey,
                    overwrite: overwriteBool,
                    denoising_strength: denoisingStrength,
                    image_uid: imageUid,
                    cn_name: cnName,
                    trans_id: transId, // Include trans_id here
                })
                .then((res) => {
                    if (res.data.check) {
                        toast.success("Created successfully!", {
                            position: "top-right",
                        });

                        const newEntry = res.data.data;
                        setEntries((prevEntries) => {
                            return [newEntry, ...prevEntries];
                        });

                        resetCreate();
                        handleClose();
                    } else {
                        showAlert(
                            "error",
                            res.data.msg || "Failed to create entry."
                        );
                    }
                })
                .catch((error) => {
                    showAlert("error", "An error occurred. Please try again.");
                    console.error(error);
                });
        }
    };

    const handleCellEditStop = (id, field, value) => {
        // Check if the edit is for the "name" field and if the value is empty (indicating a deletion request)
        if (field === "model_name" && value === "") {
            Swal.fire({
                icon: "question",
                text: "Bạn muốn xóa feature này?",
                showDenyButton: true,
                confirmButtonText: "Đúng",
                denyButtonText: "Không",
            }).then((result) => {
                if (result.isConfirmed) {
                    // If confirmed, call the delete API
                    axios.delete(`/apivances/${id}`).then((res) => {
                        if (res.data.check) {
                            // Show success alert and update the entries state
                            showAlert("success", "Deleted successfully!");
                            setEntries((prevEntries) =>
                                prevEntries.filter((entry) => entry.id !== id)
                            );
                        } else {
                            showAlert("error", res.data.msg);
                        }
                    });
                }
            });
        } else {
            // If it's not a deletion (i.e., field is not "name" or value is not empty), update the entry
            axios.put(`/apivances/${id}`, { field, value }).then((res) => {
                if (res.data.check) {
                    // Show success alert
                    showAlert("success", "Updated successfully!");

                    // Update the specific entry in the state by replacing the updated entry only
                    setEntries((prevEntries) =>
                        prevEntries.map((entry) =>
                            entry.id === id
                                ? { ...entry, [field]: value } // Update the entry with the new value for the specific field
                                : entry
                        )
                    );
                } else {
                    showAlert("error", res.data.msg);
                }
            });
        }
    };

    return (
        <Layout>
            <ToastContainer />
            <div className="row">
                <div className="col-md">
                    <button
                        className="btn btn-outline-primary mb-3"
                        onClick={resetCreate}
                    >
                        Create New
                    </button>
                </div>
                <Modal show={show} onHide={handleClose}>
                    <Modal.Header closeButton>
                        <Modal.Title>Add API</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <label>API Key</label>
                        <input
                            type="text"
                            value={apiKey}
                            className="form-control mb-3"
                            onChange={(e) => setApiKey(e.target.value)}
                        />
                        <label>Model Name</label>
                        <input
                            type="text"
                            value={modelName}
                            className="form-control mb-3"
                            onChange={(e) => setModelName(e.target.value)}
                        />
                        <label>Prompt</label>
                        <input
                            type="text"
                            value={prompt}
                            className="form-control mb-3"
                            onChange={(e) => setPrompt(e.target.value)}
                        />
                        <label>Overwrite</label>
                        <input
                            type="checkbox"
                            checked={overwrite === 1} // checkbox checked if overwrite is 1
                            onChange={handleOverwriteChange}
                        />
                        <label>Denoising Strength</label>
                        <input
                            type="number"
                            value={denoisingStrength}
                            className="form-control mb-3"
                            onChange={(e) =>
                                setDenoisingStrength(e.target.value)
                            }
                        />
                        <label>Image UID</label>
                        <input
                            type="text"
                            value={imageUid}
                            className="form-control mb-3"
                            onChange={(e) => setImageUid(e.target.value)}
                        />
                        <label>Trans ID</label> {/* New trans_id input */}
                        <input
                            type="text"
                            value={transId}
                            className="form-control mb-3"
                            onChange={(e) => setTransId(e.target.value)} // Set trans_id here
                        />
                        <label>CN Name</label>
                        <input
                            type="text"
                            value={cnName}
                            className="form-control mb-3"
                            onChange={(e) => setCnName(e.target.value)}
                        />
                    </Modal.Body>
                    <Modal.Footer>
                        <Button variant="secondary" onClick={handleClose}>
                            Close
                        </Button>
                        <Button variant="primary" onClick={submitEntry}>
                            Save
                        </Button>
                    </Modal.Footer>
                </Modal>
            </div>
            <div className="row">
                <div className="col-md">
                    <Box sx={{ width: "100%", height: 400, overflowX: "auto" }}>
                        <DataGrid
                            rows={entries}
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

export default Index;
