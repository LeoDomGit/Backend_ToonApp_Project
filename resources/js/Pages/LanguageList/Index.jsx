import React, { useState } from "react";
import Layout from "../../Components/Layout";
import Button from "react-bootstrap/Button";
import Modal from "react-bootstrap/Modal";
import { toast, ToastContainer } from "react-toastify";
import { DataGrid } from "@mui/x-data-grid";
import { Box, Select, Switch, Typography } from "@mui/material";
import "react-toastify/dist/ReactToastify.css";
import axios from "axios";

function Index({ languages }) {
    const [data, setData] = useState(languages);
    const [language, setLanguage] = useState("");
    const [key, setKey] = useState("");
    const [status, setStatus] = useState(1);
    const [show, setShow] = useState(false);

    const columns = [
        { field: "id", headerName: "#", width: 100 },
        { field: "language", headerName: "Language", width: 200, editable: true },
        { field: "key", headerName: "Key", width: 150, editable: true },
        {
            field: 'status',
            headerName: "Status",
            renderCell: (params) => (
                <Switch
                    checked={params.value == 1}
                    onChange={(e) => switchStatus(params,e.target.value)}
                    inputProps={{ 'aria-label': 'controlled' }}
                />
            )
        },
    ];
    const switchStatus=(params,value)=>{
        var status=0;
            if(params.row.status==0){
                status=1;
            }else{
                status=0;
            }
            axios.put('/language_lists/'+params.id,{
                status:status
            },
            ).then((res) => {
                if (res.data.check == false) {
                    if (res.data.msg) {
                        notyf.open({
                            type: 'error',
                            message: res.data.msg
                        });
                    }
                } else if (res.data.check == true) {
                    toast.success("Đã thay đổi thành công !", {
                        position: "top-right"
                      });
                    if (res.data.data) {
                        setData(res.data.data);
                    } else {
                        setData([]);
                    }
                }
            })
    }
    // Submit new language
    const handleSubmit = () => {
        axios
            .post("/language_lists", { language, key, status })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Added successfully");
                    setData(res.data.data);
                    setLanguage("");
                    setKey("");
                    setStatus(1);
                    setShow(false);
                } else {
                    toast.error(res.data.msg);
                }
            })
            .catch(() => {
                toast.error("An error occurred. Please try again.");
            });
    };

    // Handle inline editing
    const handleEdit = (id, field, value) => {
        axios
            .put(`/language_lists/${id}`, { [field]: value })
            .then((res) => {
                if (res.data.check) {
                    toast.success("Updated successfully");
                    setData(res.data.data)
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
                        placeholder="Enter language..."
                        value={language}
                        onChange={(e) => setLanguage(e.target.value)}
                    />
                    <input
                        type="text"
                        className="form-control mt-2"
                        placeholder="Enter key..."
                        value={key}
                        onChange={(e) => setKey(e.target.value)}
                    />
                    <select
                        className="form-control mt-2"
                        value={status}
                        onChange={(e) => setStatus(Number(e.target.value))}
                    >
                        <option value={1}>Active</option>
                        <option value={0}>Inactive</option>
                    </select>
                </Modal.Body>
                <Modal.Footer>
                    <Button variant="secondary" onClick={() => setShow(false)}>
                        Close
                    </Button>
                    <Button
                        variant="primary"
                        onClick={handleSubmit}
                        disabled={!language || !key}
                    >
                        Submit
                    </Button>
                </Modal.Footer>
            </Modal>

            <div className="mt-4">
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
