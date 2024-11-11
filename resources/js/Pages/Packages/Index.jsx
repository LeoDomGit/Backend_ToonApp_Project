import React, { useEffect, useState } from 'react'
import Layout from '../../Components/Layout';
import Button from 'react-bootstrap/Button';
import Modal from 'react-bootstrap/Modal';
import JoditEditor from 'jodit-react';
function Index({data}) {
    const [packages,setPackages]= useState(data);
    const [name,setName]= useState('');
    const [price,setPrice]= useState(null);
    const [description,setDescription]= useState('');
    const [show, setShow] = useState(false);
    const handleClose = () => setShow(false);
    const handleShow = () => setShow(true);
    const resetCreate = () => {
        setName('');
        setPrice(null);
        setDescription('');
        setShow(true);
    }
    useEffect(()=>{

    },[])
  return (
    <Layout>
<>
    <div className="row">
        <div className="col-md">
            <button className='btn btn-outline-primary' onClick={(e)=>resetCreate()}>Create</button>
        </div>
        <Modal show={show} onHide={handleClose}>
        <Modal.Header closeButton>
          <Modal.Title>Thêm gói thành viên</Modal.Title>
        </Modal.Header>
        <Modal.Body>
            <input type="text" value={name} className='form-control mb-3' onChange={(e)=>setName(e.target.value)} name="" id="" />
            <input type="number" value={price} className='form-control mb-3 ' onChange={(e)=>setPrice(e.target.value)} name="" id="" />
            <JoditEditor
    value={description}
    config={{ readonly: false }}
    tabIndex={1} // tabIndex of textarea
    onBlur={newContent => setDescription(newContent)} // preferred for performance
    onChange={newContent => setDescription(newContent)} // optional: if you want real-time updates
/>
        </Modal.Body> 
        <Modal.Footer>
          <Button variant="secondary" onClick={handleClose}>
            Close
          </Button>
          <Button variant="primary" onClick={handleClose}>
            Save Changes
          </Button>
        </Modal.Footer>
      </Modal>
    </div>
    </>

    </Layout>
    
  )
}

export default Index