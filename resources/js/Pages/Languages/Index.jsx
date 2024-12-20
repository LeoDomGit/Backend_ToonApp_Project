import React, { useState } from "react";
import Layout from "../../Components/Layout";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { toast, ToastContainer } from "react-toastify";
import { Box } from "@mui/material";
import { DataGrid } from "@mui/x-data-grid";
import "react-toastify/dist/ReactToastify.css";
import axios from "axios";

function Index({ languages }) {
    const [data, setData] = useState(languages);
    const [key, setKey] = useState("");
    const [translations, setTranslations] = useState({
        en: "",
        vi: "",
        de: "",
        ksl: "",
        pl: "",
        nu: "",
    });
    const [show, setShow] = useState(false);

    const columns = [
        { field: "id", headerName: "#", width: 100 , editable: true },
        { field: "key", headerName: "Key", width: 200 , editable: true },
        { field: "en", headerName: "English", width: 150, editable: true },
        { field: "vi", headerName: "Vietnamese", width: 150, editable: true },
        { field: "de", headerName: "German", width: 150, editable: true },
        { field: "ksl", headerName: "Korean", width: 150, editable: true },
        { field: "pl", headerName: "Polish", width: 150, editable: true },
        { field: "nu", headerName: "Norwegian", width: 150, editable: true },
        { field: "api_slug", headerName: "API slug", width: 150, editable: true },
        { field: "subscription_id", headerName: "Subcription Id", width: 150, editable: true },
        { field: "attribute", headerName: "Attribute", width: 150, editable: true },

    ];

    const handleSubmit = () => {
        axios
            .post("/languages", { key, ...translations })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Added successfully");
                    setData(res.data.data)
                    setKey("");
                    setTranslations({
                        en: "",
                        vi: "",
                        de: "",
                        ksl: "",
                        pl: "",
                        nu: "",
                    });
                    setShow(false);
                } else {
                    toast.error(res.data.msg);
                }
            })
            .catch(() => {
                toast.error("An error occurred. Please try again.");
            });
    };

    const handleEdit = (id, field, value) => {
        axios
            .put(`/languages/${id}`, { [field]: value })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Updated successfully");
                    setData((prevData) =>
                        prevData.map((item) =>
                            item.id === id ? { ...item, [field]: value } : item
                        )
                    );
                } else {
                    toast.error(res.data.msg);
                }
            })
            .catch(() => {
                toast.error("An error occurred. Please try again.");
            });
    };

    return (
        <Layout>
            <Modal show={show} onHide={() => setShow(false)}>
                <Modal.Header closeButton>
                    <Modal.Title>Add Language</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <input
                        type="text"
                        className="form-control"
                        placeholder="Enter key..."
                        value={key}
                        onChange={(e) => setKey(e.target.value)}
                    />
                    {Object.keys(translations).map((lang) => (
                        <input
                            key={lang}
                            type="text"
                            className="form-control mt-2"
                            placeholder={`Enter ${lang} translation...`}
                            value={translations[lang]}
                            onChange={(e) =>
                                setTranslations({
                                    ...translations,
                                    [lang]: e.target.value,
                                })
                            }
                        />
                    ))}
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShow(false)}>
                        Close
                    </Button>
                    <Button
                        variant="primary"
                        onClick={handleSubmit}
                        disabled={!key || Object.values(translations).some((v) => !v)}
                    >
                        Submit
                    </Button>
                </Modal.Footer>
            </Modal>

            <div className=" mt-4">
                <Button
                    className="btn btn-primary mb-3"
                    onClick={() => setShow(true)}
                >
                    Add Language
                </Button>
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
            <ToastContainer />
        </Layout>
    );
}

export default Index;
