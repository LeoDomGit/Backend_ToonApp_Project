import React, { useEffect, useState } from 'react'
import Layout from '../../Components/Layout'
import Button from 'react-bootstrap/Button';
import Modal from 'react-bootstrap/Modal';
import { Box, Select, Switch, Typography } from "@mui/material";
import { DataGrid } from '@mui/x-data-grid';
import axios from 'axios';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import Swal from 'sweetalert2'
function Index({ sizes }) {
    const notify = () => {
      toast("Default Notification !");

      toast.success("Success Notification !", {
        position: "top-right"
      });

      toast.error("Error Notification !", {
        position: "top-right"
      });

      toast.warn("Warning Notification !", {
        position: "bottom-right"
      });

      toast.info("Info Notification !", {
        position: "bottom-right"
      });

      toast("Custom Style Notification with css class!", {
        position: "bottom-right",
        className: 'foo-bar'
      });
    };
  const [size, setSize] = useState('');
  const [data, setData] = useState(sizes)
  const [show, setShow] = useState(false);
  const handleClose = () => setShow(false);
  const handleShow = () => setShow(true);
  const api = 'http://localhost:8000/api/';
  const app = 'http://localhost:8000/';
  const formatCreatedAt = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleString(); 
};
const switchStatus=(params,value)=>{
    var status=0;
        if(params.row.status==0){
            status=1;
        }else{
            status=0;
        }
        axios.put('/sizes/'+params.id,{
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
  const columns = [
    { field: "id", headerName: "#", width: 100, renderCell: (params) => params.rowIndex },
    { field: 'size', headerName: "Size", width: 200, editable: true },
    {
        field: 'status',
        headerName: "Status",
        width: 70,
        renderCell: (params) => (
            <Switch
                checked={params.value == 1}
                onChange={(e) => switchStatus(params,e.target.value)}
                inputProps={{ 'aria-label': 'controlled' }}
            />
        )
    },
    {
      field: 'created_at', headerName: 'Created at', width: 200, valueGetter: (params) => formatCreatedAt(params)
    }
  ];
  const submitSize = () => {
    axios.post('/sizes', {
      size: size
    }).then((res) => {
      if (res.data.check == true) {
        toast.success("Đã thêm thành công !", {
            position: "top-right"
          });
        setData(res.data.data);
        setShow(false);
        setRole('')
      }else if(res.data.check==false){
        toast.error(res.data.msg, {
            position: "top-center"
          });
      }
    })
  }
  const resetCreate = () => {
setSize('');
    setShow(true)
  }
  const handleCellEditStop = (id, field, value) => {
    if(value==''){
      Swal.fire({
        icon:'question',
        text: "Bạn muốn xóa loại tài khoản này ?",
        showDenyButton: true,
        showCancelButton: false,
        confirmButtonText: "Đúng",
        denyButtonText: `Không`
      }).then((result) => {
        /* Read more about isConfirmed, isDenied below */
        if (result.isConfirmed) {
          axios.delete('/sizes/'+id).then((res)=>{
            if(res.data.check==true){
                toast.success("Đã xoá thành công !", {
                    position: "top-right"
                  });
              setData(res.data.data)
            }
          })
        } else if (result.isDenied) {
        }
      });
    }else{
      axios
      .put(
        `/sizes/${id}`,
        {
          size: value,
        },
        // {
        //     headers: {
        //         Authorization: `Bearer ${localStorage.getItem("token")}`,
        //         Accept: "application/json",
        //     },
        // }
      )
      .then((res) => {
        if (res.data.check == true) {
            toast.success("Đã chỉnh sửa thành công !", {
                position: "top-right"
              });
          setData(res.data.data);

        } else if (res.data.check == false) {
            toast.error(res.data.msg, {
                position: "top-right"
              });
        }
      });
    }
  
  };
  return (

    <Layout>
      <>
      <ToastContainer />
        <Modal show={show} onHide={handleClose}>
          <Modal.Header closeButton>
            <Modal.Title>Tạo size hình ảnh</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <input type="text" className='form-control' onChange={(e) => setSize(e.target.value)} />
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={handleClose}>
              Đóng
            </Button>
            <Button variant="primary text-light" disabled={size == '' ? true : false} onClick={(e) => submitSize()}>
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
            <div className="collapse navbar-collapse" id="navbarSupportedContent">
            <a className="btn btn-primary text-light" onClick={(e) => resetCreate()} aria-current="page" href="#">
                    Tạo mới
                  </a>
            </div>
          </div>
        </nav>
        <div className="row">
          <div className="col-md">
            {data && data.length > 0 && (
              <div
                class="card border-0 shadow"
              >
                <div class="card-body">
                <Box sx={{ height: 400, width: '100%' }}>
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
                    handleCellEditStop(params.row.id, params.field, e.target.value)
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

  )
}

export default Index