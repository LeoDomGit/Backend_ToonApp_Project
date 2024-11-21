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
        { field: "name", headerName: "Name", width: 200, editable: true }, // new column
        { field: "module", headerName: "Module", width: 200, editable: true }, // new column
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
            width: 150,
            editable: true,
            renderCell: (params) => {
                return (
                    <Checkbox
                        checked={params.value === 1} // If value is 1, checkbox is checked
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
        { field: "apiKey", headerName: "API Key", width: 200, editable: true },
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
            // Ensure overwrite is a boolean (true or false)
            const overwriteBool = overwrite === 1; // overwrite is true if 1, false if 0

            // Send the POST request to create a new entry
            axios
                .post("/apivances", {
                    model_name: modelName,
                    prompt,
                    apiKey,
                    overwrite: overwriteBool, // Send the boolean value
                    denoising_strength: denoisingStrength,
                    image_uid: imageUid,
                    cn_name: cnName,
                })
                .then((res) => {
                    if (res.data.check) {
                        // Show a success toast notification
                        toast.success("Created successfully!", {
                            position: "top-right",
                        });

                        // Assuming the response contains the new entry, append it to the existing entries
                        const newEntry = res.data.data;
                        setEntries((prevEntries) => {
                            // Append the new entry at the beginning of the list
                            return [newEntry, ...prevEntries]; // Adds the new entry to the state
                        });

                        // Reset form fields after submission
                        resetCreate();

                        // Close the modal or form after creation
                        handleClose();
                    } else {
                        // Show error message if the creation failed
                        showAlert(
                            "error",
                            res.data.msg || "Failed to create entry."
                        );
                    }
                })
                .catch((error) => {
                    // Show error message for failed request
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
            axios.put(`/apivances/${id}`, { [field]: value }).then((res) => {
                if (res.data.check) {
                    // Show success alert and update entries after editing
                    showAlert("success", "Entry updated successfully!");
                    setEntries(res.data.data); // Update entries after success
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
