import Layout from "../../Components/Layout";
import { Box } from "@mui/material";
import { DataGrid } from "@mui/x-data-grid";
import axios from "axios";
import { useState } from "react";
import { toast } from "react-toastify";
import Swal from "sweetalert2";

function Index({ effects }) {
    const [data, setData] = useState(effects);

    const columns = [
        { field: "id", headerName: "ID", flex: 1 },
        { field: "name", headerName: "Name", editable: true, flex: 1 },
        { field: "created_at", headerName: "Created Time", flex: 1 },
        { field: "updated_at", headerName: "Updated Time", flex: 1 },
        { field: "deleted_at", headerName: "Deleted Time", flex: 1 },
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
            <div className="row">
                <div className="col mx-auto">
                    {effects && effects.length > 0 && (
                        <div className="card border-0 shadow">
                            <div className="card-body">
                                <Box sx={{}}>
                                    <DataGrid
                                        rows={data}
                                        columns={columns}
                                        pageSizeOptions={[5]}
                                        disableRowSelectionOnClick
                                        onCellEditStop={(params, event) => {
                                            handleOnCellEditStop(
                                                params.row.id,
                                                params.field,
                                                event.target.value
                                            );
                                        }}
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
